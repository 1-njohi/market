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
use App\Models\SubLeague;
use App\Models\User;
use App\Services\Matchday\MatchdayContestBuilder;
use App\Services\Matchday\MatchdayEntryService;
use App\Services\Matchday\SubLeagueService;
use App\Services\PredictionRecorderService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SimulateDemoCommand extends Command
{
    protected $signature = 'demo:simulate
                            {--slips=78 : Number of betslips to create}
                            {--users=20 : Non-admin users to draw from (10 sellers, 10 buyers)}
                            {--season=2026-27 : Season label}
                            {--league=EPL : League code for matchday}
                            {--skip-sync : Skip the fixtures:sync and odds:sync commands}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'One-shot demo builder: wipes data, seeds fixtures, creates betslips, matchday, and jackpot.';

    protected array $fixedResults = [
        'Manchester United vs Newcastle' => 'Away',
        'Liverpool vs Manchester City' => 'Home',
        'Arsenal vs Tottenham' => 'Home',
        'Chelsea vs Brighton' => 'Draw',
        'Aston Villa vs Brentford' => 'Draw',
        'Fulham vs Everton' => 'Home',
        'West Ham vs Crystal Palace' => 'Away',
        'Leicester vs Wolves' => 'Home',
        'Southampton vs Bournemouth' => 'Home',
        'Ipswich vs Nottingham Forest' => 'Draw',
        'Barcelona vs Real Madrid' => 'Draw',
        'Atletico Madrid vs Sevilla' => 'Home',
        'Valencia vs Villarreal' => 'Away',
        'Athletic Club vs Real Betis' => 'Home',
        'Real Sociedad vs Celta Vigo' => 'Home',
        'Espanyol vs Getafe' => 'Draw',
        'Girona vs Leganes' => 'Draw',
        'Osasuna vs Rayo Vallecano' => 'Home',
        'Mallorca vs Alaves' => 'Away',
        'Valladolid vs Almeria' => 'Home',
    ];

    public function handle(
        SubLeagueService $subLeagueService,
        MatchdayContestBuilder $contestBuilder,
        MatchdayEntryService $entryService,
        WalletService $walletService,
        PredictionRecorderService $predictionRecorder,
    ): int {
        // if (
        //     !$this->option('force') && !$this->confirm(
        //         'This will DELETE all fixtures, contests, odds, transactions, and derived data. Continue?',
        //         false,
        //     )
        // ) {
        //     $this->warn('Aborted.');
        //     return self::FAILURE;
        // }

        $season = $this->option('season');
        $leagueCode = strtoupper($this->option('league'));
        $slipCount = (int) $this->option('slips');
        $userCount = (int) $this->option('users');

        // ════════════════════════════════════════════════════════════
        // STEP 1: Wipe
        // ════════════════════════════════════════════════════════════
        $this->step(1, 'Wiping all fixtures, contests, odds, transactions, and predictions');
        $this->wipeAll();

        // ════════════════════════════════════════════════════════════
        // STEP 2: Ensure league row exists
        // ════════════════════════════════════════════════════════════
        $this->step(2, 'Ensuring league row exists');

        $supportedCodes = config('marketplace.matchday.supported_league_codes', []);
        $leagueId = $supportedCodes[$leagueCode] ?? null;

        if (!$leagueId) {
            $this->error("League code {$leagueCode} not in marketplace.matchday.supported_league_codes.");
            return self::FAILURE;
        }

        League::firstOrCreate(
            ['id' => $leagueId],
            [
                'id_on_api' => $leagueId,
                'sport_id' => 1,
                'name' => $leagueCode,
                'country_id' => 1,
                'country' => 'Test',
                'season' => (int) substr($season, 0, 4),
            ],
        );

        $this->line("  League {$leagueCode} (id={$leagueId}) ready.");

        // ════════════════════════════════════════════════════════════
        // STEP 3: Sync fixtures
        // ════════════════════════════════════════════════════════════
        if (!$this->option('skip-sync')) {
            $this->step(3, 'Syncing fixtures from API');
            Artisan::call('fixtures:sync-upcoming');
            $this->line('  ' . trim(Artisan::output()));
        } else {
            $this->step(3, 'Skipping fixtures sync (--skip-sync)');
        }

        $fixtureCount = Fixture::count();
        if ($fixtureCount < 15) {
            $this->error("Not enough fixtures in DB ({$fixtureCount}). Need at least 15.");
            return self::FAILURE;
        }
        $this->line("  Fixtures in DB: {$fixtureCount}");

        // ════════════════════════════════════════════════════════════
        // STEP 4: Sync odds
        // ════════════════════════════════════════════════════════════
        if (!$this->option('skip-sync')) {
            $this->step(4, 'Syncing odds from API');
            Artisan::call('odds:sync-upcoming');
            $this->line('  ' . trim(Artisan::output()));
        } else {
            $this->step(4, 'Skipping odds sync (--skip-sync)');
        }

        $oddCount = Odd::count();
        if ($oddCount < 30) {
            $this->error("Not enough odds in DB ({$oddCount}). Need at least 30.");
            return self::FAILURE;
        }
        $this->line("  Odds in DB: {$oddCount}");

        // ════════════════════════════════════════════════════════════
        // STEP 5: Create users
        // ════════════════════════════════════════════════════════════
        $this->step(5, "Creating {$userCount} users");
        $users = User::factory()->count($userCount)->create(['is_admin' => false]);
        $this->line("  Created {$users->count()} users.");

        $half = (int) floor($users->count() / 2);
        $sellers = $users->take($half);
        $buyers = $users->slice($half);

        // ════════════════════════════════════════════════════════════
        // STEP 6: Fund wallets + seed reputation
        // ════════════════════════════════════════════════════════════
        $this->step(6, 'Funding wallets and seeding reputation scores');
        foreach ($users as $u) {
            $walletAmount = rand(10000, 25000);
            $walletService->credit(
                user: $u,
                amount: $walletAmount,
                type: 'deposit',
                reference: 'SIM-' . Str::random(10),
                description: 'Simulation seed deposit',
            );

            // Seed reputation. Distribution mirrors the "persona" spread:
            //  - 20% oracles:   1,000 – 5,000 pts (high weight in crowd)
            //  - 50% average:     100 –   800 pts
            //  - 30% novices:       0 –    80 pts (low weight)
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
        $this->line("  Funded wallets (KES 10k–25k) and seeded reputation points per user.");
        $this->line("    - ~20% oracles  (1,000–5,000 pts)");
        $this->line("    - ~50% average  (100–800 pts)");
        $this->line("    - ~30% novices  (0–80 pts)");

        // ════════════════════════════════════════════════════════════
        // STEP 7: Create sub-leagues
        // ════════════════════════════════════════════════════════════
        $this->step(7, 'Creating sub-leagues and distributing users');

        $subLeagues = [];
        $assigned = [];

        for ($i = 1; $i <= 5; $i++) {
            $owner = $users->random();
            $sub = $subLeagueService->create($owner, "Sim League {$i}", $season, [$leagueCode]);
            $subLeagues[] = $sub;
            $assigned[$sub->id] = [$owner->id];
        }

        foreach ($users as $user) {
            $toJoin = rand(1, 2);
            $joined = 0;
            $candidates = collect($subLeagues)
                ->filter(fn($s) => !in_array($user->id, $assigned[$s->id]) && count($assigned[$s->id]) < 20)
                ->shuffle();

            foreach ($candidates as $sub) {
                if ($joined >= $toJoin)
                    break;
                try {
                    $subLeagueService->join($user, $sub->invite_code);
                    $assigned[$sub->id][] = $user->id;
                    $joined++;
                } catch (\Exception) {
                    // skip
                }
            }
        }

        $this->line("  Created " . count($subLeagues) . " sub-leagues.");

        // ════════════════════════════════════════════════════════════
        // STEP 8: Create matchday contest
        // ════════════════════════════════════════════════════════════
        $this->step(8, 'Creating matchday contest');

        $host = User::where('is_admin', true)->first()
            ?? User::factory()->create(['is_admin' => true]);

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
            'host_id' => $host->id,
            'name' => "{$leagueCode} MATCHDAY {$mdNumber}",
            'description' => 'Simulated matchday for demo.',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'type' => 'matchday',
            'season' => $season,
            'league_code' => $leagueCode,
            'matchday_number' => $mdNumber,
            'auto_entry' => true,
            'starts_at' => $mdFixtures->min('date'),
            'ends_at' => $mdFixtures->max('date'),
        ]);

        $contestBuilder->hydrate($matchday, $mdFixtures->pluck('id')->all());
        $matchday->save();

        $matchWinnerMarket = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        foreach ($mdFixtures as $fx) {
            ContestLeg::create([
                'contest_id' => $matchday->id,
                'fixture_id' => $fx->id,
                'market_id' => $matchWinnerMarket->id,
                'status' => 'pending',
            ]);
        }

        $this->line("  Matchday contest: {$matchday->name} ({$matchday->uuid})");

        // ════════════════════════════════════════════════════════════
        // STEP 9: Auto-enter users into matchday + seed picks
        // ════════════════════════════════════════════════════════════
        $this->step(9, 'Auto-entering users into matchday and seeding picks');

        $entriesCreated = $entryService->enterAllEligibleUsers($matchday);
        $this->line("  Matchday entries: {$entriesCreated}");

        $this->seedBiasedPicks($matchday, $predictionRecorder, 'matchday', $matchday->id);

        // ════════════════════════════════════════════════════════════
        // STEP 10: Create jackpot contest
        // ════════════════════════════════════════════════════════════
        $this->step(10, 'Creating jackpot contest');

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

        $lockCalc = app(\App\Services\Matchday\MatchdayLockCalculator::class);
        $locksAt = $lockCalc->lockTime($jpFixtures);

        $jackpot = Contest::create([
            'host_id' => $host->id,
            'name' => 'Weekend Mega Jackpot',
            'description' => 'Pick 13 outcomes. See what the crowd thinks.',
            'visibility' => 'public',
            'type' => 'jackpot',
            'status' => 'open',
            'entry_deadline_at' => $locksAt,
            'locks_at' => $locksAt,
            'starts_at' => $jpFixtures->min('date'),
            'ends_at' => $jpFixtures->max('date'),
        ]);

        foreach ($jpFixtures as $fx) {
            ContestLeg::create([
                'contest_id' => $jackpot->id,
                'fixture_id' => $fx->id,
                'market_id' => $matchWinnerMarket->id,
                'status' => 'pending',
            ]);
        }

        $this->line("  Jackpot: {$jackpot->name} ({$jackpot->uuid}), {$jpFixtures->count()} legs");

        // ════════════════════════════════════════════════════════════
        // STEP 11: Enter users into jackpot + seed picks
        // ════════════════════════════════════════════════════════════
        $this->step(11, 'Entering users into jackpot and seeding picks');

        $crowdCount = rand(99, 120);
        $crowdUsers = User::where('is_admin', false)
            ->inRandomOrder()
            ->limit($crowdCount)
            ->get();

        // Seed reputation on the crowd users too, so their weights matter.
        foreach ($crowdUsers as $cu) {
            if ($cu->prediction_points === 0) {
                $roll = rand(1, 100);
                $pts = $roll <= 20 ? rand(1000, 5000) : ($roll <= 70 ? rand(100, 800) : rand(0, 80));
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
                    if ($pick === null)
                        continue;

                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id' => $leg->id,
                        'selection' => $pick['value'],
                        'odds_at_pick' => $pick['odd'],
                        'status' => 'pending',
                    ]);

                    $predictionRecorder->record(
                        userId: $u->id,
                        fixtureId: $leg->fixture_id,
                        marketId: $leg->market_id,
                        selection: $pick['value'],
                        odds: (float) $pick['odd'],
                        confidence: $this->confidenceForTier($tier),
                        sourceType: 'jackpot',
                        sourceId: (string) $jackpot->id,
                    );
                }
            }
        });

        $this->line("  Jackpot entries: " . ContestEntry::where('contest_id', $jackpot->id)->count());

        // ════════════════════════════════════════════════════════════
        // STEP 12: Create betslips — ALL from the same fixture
        // ════════════════════════════════════════════════════════════
        $this->step(12, "Creating {$slipCount} betslips (all from the same fixture)");

        $this->createBetslips($sellers, $buyers, $slipCount, $walletService, $predictionRecorder);

        // ════════════════════════════════════════════════════════════
        // STEP 13: Print demo accounts + top oracles
        // ════════════════════════════════════════════════════════════
        $this->step(13, 'Preparing demo accounts');

        $sellerDemo = $sellers->first();
        $buyerDemo = $buyers->first();
        $balanced = $users->first();

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
        $this->line(' Balanced perspective (both roles, in sub-leagues):');
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
        $this->line(" Matchday dashboard: /matchday");
        $this->line(" Jackpot detail:     /jackpots/{$jackpot->uuid}");
        $this->line(" Betslip marketplace: /marketplace");
        $this->line(" Portfolio:          /portfolio");
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

            if (!$this->option('skip-sync')) {
                DB::table('odds')->delete();
                DB::table('fixture_events')->delete();
                DB::table('fixtures')->delete();
            }

            $walletColumns = Schema::getColumnListing('wallets');
            $resetColumns = array_values(array_filter(
                ['balance', 'pending_balance', 'escrow_balance', 'held_balance', 'locked_balance'],
                fn($col) => in_array($col, $walletColumns, true),
            ));

            if (!empty($resetColumns)) {
                $update = array_fill_keys($resetColumns, 0);
                DB::table('wallets')->update($update);
            }

            DB::table('users')->update(['prediction_points' => 0]);
        });

        $this->line('  All fixtures, contests, odds, transactions, and reputation data wiped.');
    }

    private function seedBiasedPicks(
        Contest $matchday,
        PredictionRecorderService $predictionRecorder,
        string $sourceType,
        int $sourceId,
    ): void {
        $entries = ContestEntry::where('contest_id', $matchday->id)->get();
        $legs = ContestLeg::where('contest_id', $matchday->id)
            ->with('fixture.homeTeam', 'fixture.awayTeam')
            ->get();

        ContestPick::withoutMatchdayLock(function () use ($entries, $legs, $predictionRecorder, $sourceType, $sourceId) {
            foreach ($entries as $entry) {
                $tier = $this->assignSkillTier();

                foreach ($legs as $leg) {
                    $pick = $this->biasedPick($leg, $tier);
                    if ($pick === null)
                        continue;

                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id' => $leg->id,
                        'selection' => $pick['value'],
                        'odds_at_pick' => $pick['odd'],
                        'status' => 'pending',
                    ]);

                    $predictionRecorder->record(
                        userId: $entry->user_id,
                        fixtureId: $leg->fixture_id,
                        marketId: $leg->market_id,
                        selection: $pick['value'],
                        odds: (float) $pick['odd'],
                        confidence: $this->confidenceForTier($tier),
                        sourceType: $sourceType,
                        sourceId: (string) $sourceId,
                    );
                }
            }
        });
    }

    private function confidenceForTier(array $tier): int
    {
        return match ($tier['name']) {
            'sharp' => rand(4, 5),
            'average' => rand(2, 3),
            'unlucky' => rand(1, 2),
            default => 3,
        };
    }

    private function assignSkillTier(): array
    {
        $roll = rand(1, 100);
        if ($roll <= 20) {
            return ['name' => 'sharp', 'probability_correct' => 80];
        }
        if ($roll <= 70) {
            return ['name' => 'average', 'probability_correct' => 50];
        }
        return ['name' => 'unlucky', 'probability_correct' => 20];
    }

    private function weightedLegCount(): int
    {
        $r = rand(1, 100);
        if ($r <= 35)
            return 1;
        if ($r <= 60)
            return 2;
        if ($r <= 80)
            return 3;
        if ($r <= 90)
            return 4;
        if ($r <= 95)
            return 5;
        return 6;
    }

    /**
     * Creates betslips — every slip uses markets from the SAME fixture.
     *
     * The fixture is picked once up front, then every slip samples
     * 1-6 markets from that fixture's available markets. If the fixture
     * has fewer markets than the leg count, the slip uses all available
     * markets; the "selection" per leg is a random outcome from that
     * market's options.
     *
     * Confidence ratings on each leg are derived from the seller's
     * persona (reputation) so the crowd-wisdom weight is realistic.
     */
    private function createBetslips(
        $sellers,
        $buyers,
        int $slipCount,
        WalletService $walletService,
        PredictionRecorderService $predictionRecorder,
    ): void {
        // ── Pick the single fixture all slips will use ────────────
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

        // Load the fixture with its teams and leagues for the message.
        $targetFixture->load(['homeTeam', 'awayTeam', 'league']);

        $homeName = $targetFixture->homeTeam?->name ?? 'Home';
        $awayName = $targetFixture->awayTeam?->name ?? 'Away';
        $matchName = "{$homeName} vs {$awayName}";

        $this->line("  Target fixture: {$matchName}");

        // Group this fixture's odds by market. Each market becomes a
        // candidate leg. Slips can only use markets from THIS fixture.
        $byMarket = $targetFixture->Odds()
            ->where('status', 'pending')
            ->get()
            ->groupBy('market_id');

        // Build a leg pool scoped to this fixture.
        $legPool = [];
        foreach ($byMarket as $marketId => $odds) {
            $values = $odds->pluck('value')->unique()->values();
            if ($values->count() < 2) {
                continue;   // single-outcome markets aren't useful
            }

            $legPool[] = [
                'fixture_id' => $targetFixture->id,
                'market_id' => (int) $marketId,
                'options' => $values->map(fn($v) => [
                    'value' => $v,
                    'odd' => (float) $odds->firstWhere('value', $v)->odd,
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
            $seller = $sellers->random();
            $sellerTier = $this->assignSkillTier();

            // A slip wants 1-6 legs, capped by the number of markets
            // available on the target fixture. If the fixture has 3
            // markets and the slip wants 5, the slip gets 3.
            $wantLegs = $this->weightedLegCount();
            $legCount = min($wantLegs, count($legPool));

            $legs = collect($legPool)
                ->shuffle()
                ->take($legCount)
                ->values();

            $totalOdds = 1.0;
            $legData = [];
            foreach ($legs as $leg) {
                $pick = $leg['options'][array_rand($leg['options'])];
                $totalOdds *= $pick['odd'];
                $legData[] = [
                    'fixture_id' => $leg['fixture_id'],
                    'market_id' => $leg['market_id'],
                    'odd_value' => $pick['value'],
                    'odd' => $pick['odd'],
                ];
            }

            $price = rand(50, 300);

            $slip = Betslip::create([
                'user_id' => $seller->id,
                'code' => strtoupper(Str::random(10)),
                'total_odds' => round($totalOdds, 2),
                'price' => $price,
                'status' => 'pending',
                'remaining' => count($legData),
                'is_winner' => false,
                'is_sellable' => true,
                'is_delisted' => false,
                'priority_score' => rand(1, 100),
            ]);

            foreach ($legData as $leg) {
                $oddId = Odd::where('fixture_id', $leg['fixture_id'])
                    ->where('market_id', $leg['market_id'])
                    ->where('value', $leg['odd_value'])
                    ->value('id');

                if (!$oddId)
                    continue;

                DB::table('betslip_odd')->insert([
                    'betslip_id' => $slip->id,
                    'odd_id' => $oddId,
                    'status' => 'pending',
                    'odd_value_at_time' => $leg['odd'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $predictionRecorder->record(
                    userId: $seller->id,
                    fixtureId: $leg['fixture_id'],
                    marketId: $leg['market_id'],
                    selection: $leg['odd_value'],
                    odds: (float) $leg['odd'],
                    confidence: $this->confidenceForTier($sellerTier),
                    sourceType: 'betslip',
                    sourceId: (string) $slip->id,
                );
            }

            $purchaseCount = rand(5, 13);
            $pool = $buyers->merge($sellers->where('id', '!=', $seller->id));
            $chosen = $pool->shuffle()->take($purchaseCount);

            foreach ($chosen as $buyer) {
                if (!$walletService->hasSufficientBalance($buyer, $price)) {
                    continue;
                }

                DB::transaction(function () use ($buyer, $slip, $price, $walletService) {
                    BetslipUserPurchase::create([
                        'betslip_id' => $slip->id,
                        'buyer_id' => $buyer->id,
                        'seller_id' => $slip->user_id,
                        'purchase_price' => $price,
                        'total_odds' => $slip->total_odds,
                        'status' => 'pending',
                        'payment_method' => 'wallet',
                        'payment_reference' => 'SIM-' . Str::random(10),
                        'purchased_at' => now(),
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

        $fixture = $leg->fixture;
        $matchName = null;
        $actualWinner = null;

        if ($fixture && $fixture->homeTeam && $fixture->awayTeam) {
            $matchName = $fixture->homeTeam->name . ' vs ' . $fixture->awayTeam->name;
            $actualWinner = $this->fixedResults[$matchName] ?? null;
        }

        if ($actualWinner === null) {
            if (rand(1, 100) <= 60) {
                $favorite = $odds->sortBy('odd')->first();
                return ['value' => $favorite->value, 'odd' => (float) $favorite->odd];
            }
            $pick = $odds->random();
            return ['value' => $pick->value, 'odd' => (float) $pick->odd];
        }

        if ($skillTier === null) {
            $roll = rand(1, 100);
            $probabilityCorrect = $roll <= 20 ? 80 : ($roll <= 70 ? 50 : 20);
        } else {
            $probabilityCorrect = $skillTier['probability_correct'];
        }

        $wantsToBeCorrect = rand(1, 100) <= $probabilityCorrect;

        if ($wantsToBeCorrect) {
            $winningOdd = $odds->firstWhere('value', $actualWinner);
            if ($winningOdd) {
                return ['value' => $winningOdd->value, 'odd' => (float) $winningOdd->odd];
            }
            $pick = $odds->random();
            return ['value' => $pick->value, 'odd' => (float) $pick->odd];
        }

        $losingOdds = $odds->filter(fn($o) => $o->value !== $actualWinner);
        if ($losingOdds->isEmpty()) {
            $pick = $odds->random();
            return ['value' => $pick->value, 'odd' => (float) $pick->odd];
        }

        $pick = $losingOdds->random();
        return ['value' => $pick->value, 'odd' => (float) $pick->odd];
    }
}