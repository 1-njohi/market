<?php

namespace App\Console\Commands;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\User;
use App\Services\Matchday\MatchdayContestBuilder;
use App\Services\Matchday\MatchdayEntryService;
use App\Services\Matchday\MatchdayLockCalculator;
use App\Services\Matchday\SubLeagueService;
use App\Services\PredictionRecorderService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulateDemoCommand extends Command
{
    protected $signature = 'demo:simulate
                            {--slips=78 : Number of betslips to create}
                            {--users=20 : Non-admin users to draw from (split sellers/buyers)}
                            {--season=2026-27 : Season label}
                            {--league=EPL : League code for matchday}';

    protected $description = 'Non-interactive demo builder: truncates all derived data, syncs fixtures + odds, then rebuilds demo state.';

    public function handle(
        SubLeagueService $subLeagueService,
        MatchdayContestBuilder $contestBuilder,
        MatchdayEntryService $entryService,
        WalletService $walletService,
        PredictionRecorderService $predictionRecorder,
    ): int {
        $season     = (string) $this->option('season');
        $leagueCode = strtoupper((string) $this->option('league'));
        $slipCount  = max(0, (int) $this->option('slips'));
        $userCount  = max(2, (int) $this->option('users'));

        // ── STEP 1: Wipe EVERYTHING (fixtures, odds, contests, betslips, …) ──
        $this->step(1, 'Truncating derived + fixture/odds data');
        $this->wipeAll();

        // ── STEP 2: Sync fixtures ───────────────────────────────
        $this->step(2, 'Running fixtures:sync-upcoming');
        Artisan::call('fixtures:sync-upcoming');
        $this->line('  ' . trim(Artisan::output()));

        // ── STEP 3: Sync odds ───────────────────────────────────
        $this->step(3, 'Running odds:sync-upcoming');
        Artisan::call('odds:sync-upcoming');
        $this->line('  ' . trim(Artisan::output()));

        // ── STEP 4: Verify fixtures in DB ───────────────────────
        $this->step(4, 'Verifying fixtures in DB');
        $fixtureCount = Fixture::count();
        if ($fixtureCount < 15) {
            $this->error("Not enough fixtures in DB ({$fixtureCount}). Need at least 15.");
            return self::FAILURE;
        }
        $this->line("  Fixtures in DB: {$fixtureCount}");

        // ── STEP 5: Verify odds in DB ───────────────────────────
        $this->step(5, 'Verifying odds in DB');
        $oddCount = Odd::where('status', 'pending')->count();
        if ($oddCount < 30) {
            $this->error("Not enough pending odds in DB ({$oddCount}). Need at least 30.");
            return self::FAILURE;
        }
        $this->line("  Pending odds in DB: {$oddCount}");

        // ── STEP 6: Resolve league ──────────────────────────────
        $this->step(6, 'Resolving league');
        $leagueId = 1; // hardcoded
        $existingLeague = League::find($leagueId);
        if (!$existingLeague) {
            $this->error("League id={$leagueId} missing from DB even after fixtures sync.");
            return self::FAILURE;
        }
        $this->line("  League {$leagueCode} (id={$leagueId}) OK.");

        // ── STEP 7: Reuse or create non-admin users ─────────────
        $this->step(7, "Selecting {$userCount} non-admin users");
        $users = User::where('is_admin', false)
            ->inRandomOrder()
            ->limit($userCount)
            ->get();

        if ($users->count() < $userCount) {
            $need = $userCount - $users->count();
            $this->line("  Only {$users->count()} non-admin users found; creating {$need} more.");

            $created = collect();
            for ($i = 0; $i < $need; $i++) {
                $created->push($this->createDemoUser(admin: false));
            }
            $users = $users->merge($created);
        }
        $this->line("  Selected {$users->count()} users.");

        $half    = (int) floor($users->count() / 2);
        $sellers = $users->take($half);
        $buyers  = $users->slice($half);
        if ($sellers->isEmpty() || $buyers->isEmpty()) {
            $this->error('Need at least one seller and one buyer.');
            return self::FAILURE;
        }

        // ── STEP 8: Fund wallets + seed reputation ──────────────
        $this->step(8, 'Funding wallets and seeding reputation');
        foreach ($users as $u) {
            $walletAmount = rand(10000, 25000);
            $walletService->credit(
                user:        $u,
                amount:      $walletAmount,
                type:        'deposit',
                reference:   'SIM-' . Str::random(10),
                description: 'Simulation seed deposit',
            );

            $roll = rand(1, 100);
            if ($roll <= 20) {
                $points = rand(1000, 5000);
            } elseif ($roll <= 70) {
                $points = rand(100, 800);
            } else {
                $points = rand(0, 80);
            }
            $u->update(['prediction_points' => $points]);
        }
        $this->line('  Funded wallets + seeded reputation.');

        // ── STEP 9: Create sub-leagues ──────────────────────────
        $this->step(9, 'Creating sub-leagues');
        $subLeagues = [];
        $assigned   = [];
        for ($i = 1; $i <= 5; $i++) {
            $owner = $users->random();
            $sub   = $subLeagueService->create($owner, "Sim League {$i}", $season, [$leagueCode]);
            $subLeagues[]      = $sub;
            $assigned[$sub->id] = [$owner->id];
        }
        foreach ($users as $user) {
            $toJoin = rand(1, 2);
            $joined = 0;
            $candidates = collect($subLeagues)
                ->filter(fn($s) => !in_array($user->id, $assigned[$s->id]) && count($assigned[$s->id]) < 20)
                ->shuffle();
            foreach ($candidates as $sub) {
                if ($joined >= $toJoin) {
                    break;
                }
                try {
                    $subLeagueService->join($user, $sub->invite_code);
                    $assigned[$sub->id][] = $user->id;
                    $joined++;
                } catch (\Exception) {
                    // skip
                }
            }
        }
        $this->line('  Created ' . count($subLeagues) . ' sub-leagues.');

        // ── STEP 10: Create matchday contest ────────────────────
        $this->step(10, 'Creating matchday contest');
        $host = User::where('is_admin', true)->first();
        if (!$host) {
            $host = $this->createDemoUser(admin: true);
            $this->line('  No admin found; created admin id=' . $host->id);
        }

        $mdFixtures = Fixture::where('league_id', $leagueId)
            ->where('status_short', 'NS')
            ->where('date', '>', now())
            ->orderBy('date')
            ->limit(10)
            ->get();

        if ($mdFixtures->count() < 5) {
            $this->error("Not enough upcoming {$leagueCode} fixtures. Found {$mdFixtures->count()}.");
            return self::FAILURE;
        }

        $mdNumber = (Contest::where('is_matchday', true)
            ->where('season', $season)
            ->where('league_code', $leagueCode)
            ->max('matchday_number') ?? 0) + 1;

        $matchday = new Contest([
            'host_id'         => $host->id,
            'name'            => "{$leagueCode} MATCHDAY {$mdNumber}",
            'description'     => 'Simulated matchday for demo.',
            'visibility'      => 'public',
            'status'          => 'open',
            'is_matchday'     => true,
            'type'            => 'matchday',
            'season'          => $season,
            'league_code'     => $leagueCode,
            'matchday_number' => $mdNumber,
            'auto_entry'      => true,
            'starts_at'       => $mdFixtures->min('date'),
            'ends_at'         => $mdFixtures->max('date'),
        ]);

        $contestBuilder->hydrate($matchday, $mdFixtures->pluck('id')->all());
        $matchday->save();

        $matchWinnerMarket = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        foreach ($mdFixtures as $fx) {
            ContestLeg::create([
                'contest_id' => $matchday->id,
                'fixture_id' => $fx->id,
                'market_id'  => $matchWinnerMarket->id,
                'status'     => 'pending',
            ]);
        }
        $this->line("  Matchday: {$matchday->name} ({$matchday->uuid})");

        // ── STEP 11: Auto-enter users + seed picks ──────────────
        $this->step(11, 'Auto-entering users into matchday and seeding picks');
        $entriesCreated = $entryService->enterAllEligibleUsers($matchday);
        $this->line("  Matchday entries: {$entriesCreated}");

        $this->seedBiasedPicks($matchday, $predictionRecorder, 'matchday', $matchday->id);

        // ── STEP 12: Create jackpot contest ─────────────────────
        $this->step(12, 'Creating jackpot contest');
        $jackpotLegsCount = 20;
        $jpFixtures = Fixture::where('status_short', 'NS')
            ->where('date', '>', now())
            ->inRandomOrder()
            ->limit($jackpotLegsCount)
            ->get();

        if ($jpFixtures->count() < 7) {
            $this->error("Not enough fixtures for jackpot. Found {$jpFixtures->count()}, need 7.");
            return self::FAILURE;
        }

        $lockCalc = app(MatchdayLockCalculator::class);
        $locksAt  = $lockCalc->lockTime($jpFixtures);

        $jackpot = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Weekend Mega Jackpot',
            'description'       => 'Pick 13 outcomes. See what the crowd thinks.',
            'visibility'        => 'public',
            'type'              => 'jackpot',
            'status'            => 'open',
            'entry_deadline_at' => $locksAt,
            'locks_at'          => $locksAt,
            'starts_at'         => $jpFixtures->min('date'),
            'ends_at'           => $jpFixtures->max('date'),
        ]);

        foreach ($jpFixtures as $fx) {
            ContestLeg::create([
                'contest_id' => $jackpot->id,
                'fixture_id' => $fx->id,
                'market_id'  => $matchWinnerMarket->id,
                'status'     => 'pending',
            ]);
        }
        $this->line("  Jackpot: {$jackpot->name} ({$jackpot->uuid}), {$jpFixtures->count()} legs");

        // ── STEP 13: Jackpot entries + picks ────────────────────
        $this->step(13, 'Entering users into jackpot and seeding picks');
        $crowdCount = rand(99, 120);
        $crowdUsers = User::where('is_admin', false)
            ->inRandomOrder()
            ->limit($crowdCount)
            ->get();

        foreach ($crowdUsers as $cu) {
            if ((int) $cu->prediction_points === 0) {
                $roll = rand(1, 100);
                $pts  = $roll <= 20 ? rand(1000, 5000) : ($roll <= 70 ? rand(100, 800) : rand(0, 80));
                $cu->update(['prediction_points' => $pts]);
            }
        }
        $allJackpotUsers = $users->merge($crowdUsers)->unique('id');

        ContestPick::withoutMatchdayLock(function () use ($allJackpotUsers, $jackpot, $predictionRecorder) {
            $legs = ContestLeg::where('contest_id', $jackpot->id)
                ->with('fixture.homeTeam', 'fixture.awayTeam')
                ->get();

            foreach ($allJackpotUsers as $u) {
                $entry = ContestEntry::firstOrCreate(
                    ['contest_id' => $jackpot->id, 'user_id' => $u->id],
                    ['status' => 'accepted', 'joined_at' => now()],
                );

                $tier = $this->assignSkillTier();

                foreach ($legs as $leg) {
                    $pick = $this->biasedPick($leg, $tier);
                    if ($pick === null) {
                        continue;
                    }

                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id'   => $leg->id,
                        'selection'        => $pick['value'],
                        'odds_at_pick'     => $pick['odd'],
                        'status'           => 'pending',
                    ]);

                    $predictionRecorder->record(
                        userId:     $u->id,
                        fixtureId:  $leg->fixture_id,
                        marketId:   $leg->market_id,
                        selection:  $pick['value'],
                        odds:       (float) $pick['odd'],
                        confidence: $this->confidenceForTier($tier),
                        sourceType: 'jackpot',
                        sourceId:   (string) $jackpot->id,
                    );
                }
            }
        });
        $this->line('  Jackpot entries: ' . ContestEntry::where('contest_id', $jackpot->id)->count());

        // ── STEP 14: Betslips from a single fixture ─────────────
        $this->step(14, "Creating {$slipCount} betslips from a single existing fixture");
        $this->createBetslips($sellers, $buyers, $slipCount, $walletService, $predictionRecorder);

        // ── STEP 15: Print demo accounts + top oracles ──────────
        $this->step(15, 'Preparing demo accounts');
        $sellerDemo = $sellers->first();
        $buyerDemo  = $buyers->first();
        $balanced   = $users->first();
        $topOracles = User::query()
            ->where('is_admin', false)
            ->orderByDesc('prediction_points')
            ->limit(5)
            ->get(['id', 'name', 'email', 'prediction_points']);

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info(' DEMO LOGIN CREDENTIALS');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line(' All seeded users share password: password');
        $this->newLine();
        $this->line(' Seller perspective:');
        $this->line("   Email: {$sellerDemo->email}");
        $this->line("   ID:    {$sellerDemo->id}  ·  " . $sellerDemo->fresh()->prediction_points . ' pts');
        $this->newLine();
        $this->line(' Buyer perspective:');
        $this->line("   Email: {$buyerDemo->email}");
        $this->line("   ID:    {$buyerDemo->id}  ·  " . $buyerDemo->fresh()->prediction_points . ' pts');
        $this->newLine();
        $this->line(' Balanced perspective:');
        $this->line("   Email: {$balanced->email}");
        $this->line("   ID:    {$balanced->id}  ·  " . $balanced->fresh()->prediction_points . ' pts');
        $this->newLine();

        $this->info(' TOP ORACLES (highest reputation)');
        $this->info('═══════════════════════════════════════════════════════════');
        foreach ($topOracles as $i => $o) {
            $rank = $i + 1;
            $this->line(" #{$rank}  ID {$o->id}  {$o->email}  ·  {$o->prediction_points} pts");
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info(' DEMO URLS');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line(' Matchday dashboard: /matchday');
        $this->line(" Jackpot detail:     /jackpots/{$jackpot->uuid}");
        $this->line(' Betslip marketplace: /marketplace');
        $this->line(' Portfolio:          /portfolio');
        $this->newLine();

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function step(int $num, string $label): void
    {
        $this->newLine();
        $this->info("[STEP {$num}] {$label}");
    }

    /**
     * Create a demo user without relying on UserFactory (which needs Faker,
     * a dev-only dependency). Populates every NOT-NULL column the users
     * table requires.
     */
    private function createDemoUser(bool $admin): User
    {
        $suffix = Str::lower(Str::random(8));
        $prefix = $admin ? 'sim_admin' : 'sim';

        $attributes = [
            'name'              => ($admin ? 'Sim Admin ' : 'Sim User ') . $suffix,
            'email'             => "{$prefix}_{$suffix}@example.test",
            'country_code'      => '+254',
            'phone'             => $this->uniqueDemoPhone(),
            'password'          => bcrypt('password'),
            'code'              => Str::random(7),
            'is_admin'          => $admin,
            'prediction_points' => 0,
            'email_verified_at' => now(),
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return User::create($attributes);
            } catch (QueryException $e) {
                if ($attempt === 4) {
                    throw $e;
                }
                $suffix              = Str::lower(Str::random(8));
                $attributes['email'] = "{$prefix}_{$suffix}@example.test";
                $attributes['phone'] = $this->uniqueDemoPhone();
            }
        }

        throw new \RuntimeException('Failed to create demo user after retries.');
    }

    private function uniqueDemoPhone(): string
    {
        return str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Truncate ALL derived + fixture/odds data. Users and wallets are
     * preserved — they're identities, not demo content, and recreating
     * them would invalidate real logins.
     */
    private function wipeAll(): void
    {
        DB::transaction(function () {
            DB::table('betslip_user_purchases')->delete();
            DB::table('betslip_odd')->delete();
            DB::table('betslip_watches')->delete();
            DB::table('betslips')->delete();
            DB::table('transactions')->delete();

            DB::table('prediction_point_logs')->delete();
            DB::table('predictions')->delete();

            DB::table('contest_picks')->delete();
            DB::table('contest_entries')->delete();
            DB::table('contest_legs')->delete();
            DB::table('contests')->delete();

            DB::table('sub_league_members')->delete();
            DB::table('sub_leagues')->delete();

            // Fixtures + odds + events: full wipe so the sync steps repopulate.
            DB::table('odds')->delete();
            DB::table('fixture_events')->delete();
            DB::table('fixtures')->delete();
        });

        $this->line('  All fixtures, odds, contests, betslips, predictions, sub-leagues and transactions wiped.');
    }

    private function seedBiasedPicks(
        Contest $contest,
        PredictionRecorderService $predictionRecorder,
        string $sourceType,
        int $sourceId,
    ): void {
        $entries = ContestEntry::where('contest_id', $contest->id)->get();
        $legs    = ContestLeg::where('contest_id', $contest->id)
            ->with('fixture.homeTeam', 'fixture.awayTeam')
            ->get();

        ContestPick::withoutMatchdayLock(function () use ($entries, $legs, $predictionRecorder, $sourceType, $sourceId) {
            foreach ($entries as $entry) {
                $tier = $this->assignSkillTier();
                foreach ($legs as $leg) {
                    $pick = $this->biasedPick($leg, $tier);
                    if ($pick === null) {
                        continue;
                    }
                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id'   => $leg->id,
                        'selection'        => $pick['value'],
                        'odds_at_pick'     => $pick['odd'],
                        'status'           => 'pending',
                    ]);
                    $predictionRecorder->record(
                        userId:     $entry->user_id,
                        fixtureId:  $leg->fixture_id,
                        marketId:   $leg->market_id,
                        selection:  $pick['value'],
                        odds:       (float) $pick['odd'],
                        confidence: $this->confidenceForTier($tier),
                        sourceType: $sourceType,
                        sourceId:   (string) $sourceId,
                    );
                }
            }
        });
    }

    private function confidenceForTier(array $tier): int
    {
        return match ($tier['name']) {
            'sharp'   => rand(4, 5),
            'average' => rand(2, 3),
            'unlucky' => rand(1, 2),
            default   => 3,
        };
    }

    private function assignSkillTier(): array
    {
        $roll = rand(1, 100);
        if ($roll <= 20) {
            return ['name' => 'sharp',   'probability_correct' => 80];
        }
        if ($roll <= 70) {
            return ['name' => 'average', 'probability_correct' => 50];
        }
        return ['name' => 'unlucky', 'probability_correct' => 20];
    }

    private function weightedLegCount(): int
    {
        $r = rand(1, 100);
        if ($r <= 35) return 1;
        if ($r <= 60) return 2;
        if ($r <= 80) return 3;
        if ($r <= 90) return 4;
        if ($r <= 95) return 5;
        return 6;
    }

    private function createBetslips(
        $sellers,
        $buyers,
        int $slipCount,
        WalletService $walletService,
        PredictionRecorderService $predictionRecorder,
    ): void {
        $targetFixture = Fixture::query()
            ->where('status_short', 'NS')
            ->where('date', '>', now())
            ->whereHas('Odds', fn($q) => $q->where('status', 'pending'))
            ->inRandomOrder()
            ->first();

        if (!$targetFixture) {
            $this->error('No available fixture with odds to build betslips from.');
            return;
        }

        $targetFixture->load(['homeTeam', 'awayTeam', 'league']);
        $homeName  = $targetFixture->homeTeam?->name ?? 'Home';
        $awayName  = $targetFixture->awayTeam?->name ?? 'Away';
        $matchName = "{$homeName} vs {$awayName}";
        $this->line("  Target fixture: {$matchName}");

        $byMarket = $targetFixture->Odds()
            ->where('status', 'pending')
            ->get()
            ->groupBy('market_id');

        $legPool = [];
        foreach ($byMarket as $marketId => $odds) {
            $values = $odds->pluck('value')->unique()->values();
            if ($values->count() < 2) {
                continue;
            }
            $legPool[] = [
                'fixture_id' => $targetFixture->id,
                'market_id'  => (int) $marketId,
                'options'    => $values->map(fn($v) => [
                    'value' => $v,
                    'odd'   => (float) $odds->firstWhere('value', $v)->odd,
                ])->all(),
            ];
        }

        if (count($legPool) < 1) {
            $this->error('Target fixture has no multi-outcome markets. Cannot create betslips.');
            return;
        }
        $this->line('  Available markets on target fixture: ' . count($legPool));

        $purchasesMade = 0;

        for ($i = 0; $i < $slipCount; $i++) {
            $seller     = $sellers->random();
            $sellerTier = $this->assignSkillTier();

            $wantLegs = $this->weightedLegCount();
            $legCount = min($wantLegs, count($legPool));

            $legs = collect($legPool)->shuffle()->take($legCount)->values();

            $totalOdds = 1.0;
            $legData   = [];
            foreach ($legs as $leg) {
                $pick = $this->pickFromOptions($leg['options'], $sellerTier);
                $totalOdds *= $pick['odd'];
                $legData[] = [
                    'fixture_id' => $leg['fixture_id'],
                    'market_id'  => $leg['market_id'],
                    'odd_value'  => $pick['value'],
                    'odd'        => $pick['odd'],
                ];
            }

            $price = rand(50, 300);

            $slip = Betslip::create([
                'user_id'        => $seller->id,
                'code'           => strtoupper(Str::random(10)),
                'total_odds'     => round($totalOdds, 2),
                'price'          => $price,
                'status'         => 'pending',
                'remaining'      => count($legData),
                'is_winner'      => false,
                'is_sellable'    => true,
                'is_delisted'    => false,
                'priority_score' => rand(1, 100),
            ]);

            foreach ($legData as $leg) {
                $oddId = Odd::where('fixture_id', $leg['fixture_id'])
                    ->where('market_id', $leg['market_id'])
                    ->where('value', $leg['odd_value'])
                    ->value('id');
                if (!$oddId) {
                    continue;
                }
                DB::table('betslip_odd')->insert([
                    'betslip_id'        => $slip->id,
                    'odd_id'            => $oddId,
                    'status'            => 'pending',
                    'odd_value_at_time' => $leg['odd'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $predictionRecorder->record(
                    userId:     $seller->id,
                    fixtureId:  $leg['fixture_id'],
                    marketId:   $leg['market_id'],
                    selection:  $leg['odd_value'],
                    odds:       (float) $leg['odd'],
                    confidence: $this->confidenceForTier($sellerTier),
                    sourceType: 'betslip',
                    sourceId:   (string) $slip->id,
                );
            }

            $purchaseCount = rand(5, 13);
            $pool          = $buyers->merge($sellers->where('id', '!=', $seller->id));
            $chosen        = $pool->shuffle()->take($purchaseCount);

            foreach ($chosen as $buyer) {
                if (!$walletService->hasSufficientBalance($buyer, $price)) {
                    continue;
                }
                DB::transaction(function () use ($buyer, $slip, $price, $walletService) {
                    BetslipUserPurchase::create([
                        'betslip_id'        => $slip->id,
                        'buyer_id'          => $buyer->id,
                        'seller_id'         => $slip->user_id,
                        'purchase_price'    => $price,
                        'total_odds'        => $slip->total_odds,
                        'status'            => 'pending',
                        'payment_method'    => 'wallet',
                        'payment_reference' => 'SIM-' . Str::random(10),
                        'purchased_at'      => now(),
                    ]);
                    $walletService->hold(
                        user: $buyer,
                        amount: $price,
                        type: 'betslip_purchase',
                        description: "Purchase of betslip #{$slip->code}",
                    );
                });
                $purchasesMade++;
            }
        }

        $this->line("  Created {$slipCount} betslips with {$purchasesMade} purchases — all on {$matchName}.");
    }

    private function biasedPick(ContestLeg $leg, ?array $skillTier = null): ?array
    {
        $odds = Odd::where('fixture_id', $leg->fixture_id)
            ->where('market_id', $leg->market_id)
            ->where('status', 'pending')
            ->get();

        if ($odds->isEmpty()) {
            return null;
        }

        $options = $odds->map(fn($o) => ['value' => $o->value, 'odd' => (float) $o->odd])->all();

        if ($skillTier === null) {
            $roll = rand(1, 100);
            $probabilityCorrect = $roll <= 20 ? 80 : ($roll <= 70 ? 50 : 20);
            $skillTier = ['name' => 'inline', 'probability_correct' => $probabilityCorrect];
        }

        return $this->pickFromOptions($options, $skillTier);
    }

    /**
     * @param array<int, array{value:string, odd:float}> $options
     */
    private function pickFromOptions(array $options, array $skillTier): array
    {
        usort($options, fn($a, $b) => $a['odd'] <=> $b['odd']);

        $favorite     = $options[0];
        $nonFavorites = array_slice($options, 1);

        $wantsFavorite = rand(1, 100) <= $skillTier['probability_correct'];

        if ($wantsFavorite || empty($nonFavorites)) {
            return $favorite;
        }

        return $nonFavorites[array_rand($nonFavorites)];
    }
}