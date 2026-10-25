<?php

namespace App\Console\Commands;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\MatchdayContestBuilder;
use App\Services\Matchday\MatchdayEntryService;
use App\Services\Matchday\SubLeagueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateMatchdayCommand extends Command
{
    protected $signature = 'matchday:simulate 
                            {--users=100 : Number of users to create}
                            {--sub-leagues=5 : Number of sub-leagues to create}
                            {--members-per-league=20 : Max members per sub-league}
                            {--season=2026-27 : Season label}
                            {--league=EPL : League code to simulate}';

    protected $description = 'Seeds users, sub-leagues, and picks for a matchday contest to test the frontend.';

    public function handle(
        SubLeagueService $subLeagueService,
        MatchdayContestBuilder $contestBuilder,
        MatchdayEntryService $entryService
    ): int {
        $season = $this->option('season');
        $userCount = (int) $this->option('users');
        $subCount = (int) $this->option('sub-leagues');
        $maxMembers = (int) $this->option('members-per-league');
        $leagueCode = strtoupper($this->option('league'));

        // 1. Ensure config is loaded
        $supportedCodes = config('marketplace.matchday.supported_league_codes', []);
        $leagueId = $supportedCodes[$leagueCode] ?? null;

        if (!$leagueId) {
            $this->error("League code {$leagueCode} is not configured in marketplace.matchday.supported_league_codes.");
            $this->line("Available: " . implode(', ', array_keys($supportedCodes)));
            return self::FAILURE;
        }

        // Ensure the League row exists (some models have FK constraints)
        $league = League::firstOrCreate(
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

        // 2. Create Users
        $this->info("Creating {$userCount} users...");
        $users = User::factory()->count($userCount)->create([
            'is_admin' => false,
        ]);

        // 3. Create Sub-Leagues
        $this->info("Creating {$subCount} sub-leagues for league {$leagueCode}...");

        // Explicit single-league array. This bypasses any fallback in the service.
        $leagueCodes = [$leagueCode];

        $subLeagues = [];
        $assignedUsers = [];

        for ($i = 1; $i <= $subCount; $i++) {
            $owner = $users->random();
            $sub = $subLeagueService->create($owner, "Sim League {$i}", $season, $leagueCodes);

            // Verify the league_codes were saved correctly.
            if ($sub->fresh()->league_codes !== $leagueCodes) {
                $this->warn("Warning: SubLeague {$sub->id} league_codes = " . json_encode($sub->fresh()->league_codes) . " (expected " . json_encode($leagueCodes) . ")");
            }

            $subLeagues[] = $sub;
            $assignedUsers[$sub->id][] = $owner->id;
        }

        // Distribute users to sub-leagues. Every user MUST be in at least one.
        $this->info("Distributing users into sub-leagues...");

        foreach ($users as $user) {
            // Always join at least one. Optionally join a second.
            $countToJoin = rand(1, 2);

            // Prefer sub-leagues the user isn't already in.
            $candidates = collect($subLeagues)
                ->filter(fn ($sub) => !in_array($user->id, $assignedUsers[$sub->id]))
                ->filter(fn ($sub) => count($assignedUsers[$sub->id]) < $maxMembers)
                ->shuffle();

            $joined = 0;
            foreach ($candidates as $sub) {
                if ($joined >= $countToJoin) {
                    break;
                }

                try {
                    $subLeagueService->join($user, $sub->invite_code);
                    $assignedUsers[$sub->id][] = $user->id;
                    $joined++;
                } catch (\Exception $e) {
                    // Sub-league full or user already member — skip.
                }
            }

            if ($joined === 0) {
                $this->warn("User {$user->id} could not join any sub-league. They will not be auto-entered.");
            }
        }

        // Verify: count sub-league memberships.
        $totalMemberships = SubLeagueMember::whereIn('sub_league_id', collect($subLeagues)->pluck('id'))->count();
        $this->line("Total memberships across all sub-leagues: {$totalMemberships}");

        // 4. Create Matchday Contest
        $this->info("Setting up matchday contest...");

        $fixtures = Fixture::where('league_id', $leagueId)
            ->where('status_short', 'NS')
            ->where('date', '>', now())
            ->orderBy('date')
            ->limit(10)
            ->get();

        if ($fixtures->count() < 5) {
            $this->error("Not enough upcoming fixtures found for {$leagueCode}. Need at least 5, found {$fixtures->count()}.");
            $this->line("Run `php artisan fixtures:sync-upcoming` first.");
            return self::FAILURE;
        }

        $host = User::where('is_admin', true)->first();
        if (!$host) {
            $host = User::factory()->create(['is_admin' => true]);
        }

        $nextMdNumber = (Contest::where('is_matchday', true)
            ->where('season', $season)
            ->where('league_code', $leagueCode)
            ->max('matchday_number') ?? 0) + 1;

        $contest = new Contest([
            'host_id' => $host->id,
            'name' => "{$leagueCode} MATCHDAY {$nextMdNumber}",
            'description' => 'Simulated matchday for testing.',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => $season,
            'league_code' => $leagueCode,
            'matchday_number' => $nextMdNumber,
            'auto_entry' => true,
            'starts_at' => $fixtures->min('date'),
            'ends_at' => $fixtures->max('date'),
        ]);

        // Hydrate lock times and validate via the builder
        $contestBuilder->hydrate($contest, $fixtures->pluck('id')->all());
        $contest->save();

        // Create Legs
        $market = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        foreach ($fixtures as $fixture) {
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fixture->id,
                'market_id' => $market->id,
                'status' => 'pending',
            ]);
        }

        // 5. Auto-Enter Users
        $this->info("Entering users into the contest...");
        $entriesCreated = $entryService->enterAllEligibleUsers($contest);
        $this->line("Created {$entriesCreated} entries.");

        if ($entriesCreated === 0) {
            $this->error("No entries were created. Sub-league league_codes probably don't match the contest league_code.");
            $this->line("Check: " . json_encode(SubLeague::whereIn('id', collect($subLeagues)->pluck('id'))->pluck('league_codes', 'id')->toArray()));
            return self::FAILURE;
        }

        // 6. Make Picks
        $this->info("Generating random picks...");

        $entries = ContestEntry::where('contest_id', $contest->id)->get();
        $legs = ContestLeg::where('contest_id', $contest->id)->get();
        $pickCount = 0;

        // We must bypass the matchday lock because we might be simulating a past contest
        ContestPick::withoutMatchdayLock(function () use ($entries, $legs, &$pickCount) {
            foreach ($entries as $entry) {
                foreach ($legs as $leg) {
                    // Get valid odds for this fixture/market
                    $odds = Odd::where('fixture_id', $leg->fixture_id)
                        ->where('market_id', $leg->market_id)
                        ->get();

                    if ($odds->isEmpty()) {
                        continue;
                    }

                    $selectedOdd = $odds->random();

                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id' => $leg->id,
                        'selection' => $selectedOdd->value,
                        'odds_at_pick' => $selectedOdd->odd,
                        'status' => 'pending',
                    ]);
                    $pickCount++;
                }
            }
        });

        $this->info("Created {$pickCount} picks.");
        $this->newLine();
        $this->info("Simulation ready.");
        $this->line("Contest: {$contest->name} (UUID: {$contest->uuid})");
        $this->line("Locks at: {$contest->locks_at}");
        $this->line("Entries: {$entries->count()}, Picks: {$pickCount}");

        return self::SUCCESS;
    }
}