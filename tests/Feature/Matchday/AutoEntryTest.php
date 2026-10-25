<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\SubLeague;
use App\Models\User;
use App\Services\Matchday\MatchdayEntryService;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
    }

    private function makeMatchdayContest(array $overrides = []): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create(array_merge([
            'host_id' => $host->id,
            'name' => 'MD1 EPL',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'locks_at' => now()->addDays(3),
            'entry_deadline_at' => now()->addDays(3),
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3),
        ], $overrides));
    }

    /** @return array{0: SubLeague, 1: array<int, User>} */
    private function makeSubLeagueWithMembers(string $season, int $count = 3): array
    {
        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create($owner, 'Crew-' . uniqid(), $season);

        $members = [$owner];
        for ($i = 1; $i < $count; $i++) {
            $m = User::factory()->create();
            app(SubLeagueService::class)->join($m, $sub->invite_code);
            $members[] = $m;
        }

        return [$sub, $members];
    }

    public function test_creating_matchday_contest_creates_entries_for_all_members_of_all_sub_leagues_in_that_season(): void
    {
        [, $membersA] = $this->makeSubLeagueWithMembers('2026-27', 3);
        [, $membersB] = $this->makeSubLeagueWithMembers('2026-27', 2);

        $contest = $this->makeMatchdayContest();

        $created = app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        $this->assertSame(5, $created);

        $expected = collect($membersA)->merge($membersB)->pluck('id')->unique()->sort()->values()->all();
        $actual = ContestEntry::where('contest_id', $contest->id)->pluck('user_id')->sort()->values()->all();

        $this->assertSame($expected, $actual);
    }

    public function test_creating_matchday_contest_does_not_create_entries_for_members_of_other_seasons(): void
    {
        $this->makeSubLeagueWithMembers('2027-28', 3);

        $contest = $this->makeMatchdayContest(['season' => '2026-27']);

        $created = app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        $this->assertSame(0, $created);
        $this->assertSame(0, ContestEntry::where('contest_id', $contest->id)->count());
    }

    public function test_creating_matchday_contest_does_not_create_duplicate_entries_for_users_in_multiple_sub_leagues(): void
    {
        $owner = User::factory()->create();
        app(SubLeagueService::class)->create($owner, 'A', '2026-27');
        app(SubLeagueService::class)->create($owner, 'B', '2026-27');

        $contest = $this->makeMatchdayContest();

        $created = app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        $this->assertSame(1, $created);
        $this->assertSame(1, ContestEntry::where('contest_id', $contest->id)->count());
    }

    public function test_user_joining_sub_league_after_contest_is_created_gets_entry_if_contest_not_locked(): void
    {
        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create($owner, 'Crew', '2026-27');

        $contest = $this->makeMatchdayContest(['locks_at' => now()->addDays(3)]);

        $joiner = User::factory()->create();

        $this->actingAs($joiner)
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code])
            ->assertOk();

        $this->assertTrue(
            ContestEntry::where('contest_id', $contest->id)
                ->where('user_id', $joiner->id)
                ->exists()
        );
    }

    public function test_user_joining_sub_league_after_contest_locks_does_not_get_entry(): void
    {
        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create($owner, 'Crew', '2026-27');

        $contest = $this->makeMatchdayContest(['locks_at' => now()->subMinute()]);

        $joiner = User::factory()->create();

        $this->actingAs($joiner)
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code])
            ->assertOk();

        $this->assertFalse(
            ContestEntry::where('contest_id', $contest->id)
                ->where('user_id', $joiner->id)
                ->exists()
        );
    }

    public function test_entries_are_created_with_status_accepted(): void
    {
        $this->makeSubLeagueWithMembers('2026-27', 2);

        $contest = $this->makeMatchdayContest();

        app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        $statuses = ContestEntry::where('contest_id', $contest->id)
            ->pluck('status')
            ->unique()
            ->values()
            ->all();

        $this->assertSame(['accepted'], $statuses);
    }

    public function test_non_matchday_contest_does_not_auto_create_entries(): void
    {
        $this->makeSubLeagueWithMembers('2026-27', 3);

        $host = User::factory()->create(['is_admin' => true]);
        $contest = Contest::create([
            'host_id' => $host->id,
            'name' => 'Regular',
            'visibility' => 'private',
            'status' => 'open',
            'is_matchday' => false,
            'entry_deadline_at' => now()->addDays(3),
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(4),
        ]);

        $created = app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        $this->assertSame(0, $created);
        $this->assertSame(0, ContestEntry::where('contest_id', $contest->id)->count());
    }

    public function test_auto_entry_runs_inside_a_transaction_so_partial_failure_rolls_back(): void
    {
        $this->makeSubLeagueWithMembers('2026-27', 3);

        $contest = $this->makeMatchdayContest();

        $service = new class extends MatchdayEntryService {
            public int $calls = 0;

            protected function createEntryFor(User $user, Contest $contest): ContestEntry
            {
                $this->calls++;
                if ($this->calls === 2) {
                    throw new \RuntimeException('Simulated failure');
                }
                return parent::createEntryFor($user, $contest);
            }
        };

        try {
            $service->enterAllEligibleUsers($contest);
            $this->fail('Expected simulated failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        }

        $this->assertSame(0, ContestEntry::where('contest_id', $contest->id)->count());
    }

    public function test_auto_entry_only_targets_contests_in_sub_league_leagues(): void
    {
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create(
            $owner,
            'EPL Crew',
            '2026-27',
            ['EPL'],
        );

        $member = User::factory()->create();
        app(SubLeagueService::class)->join($member, $sub->invite_code);

        $eplContest = $this->makeMatchdayContest(['league_code' => 'EPL']);
        $uclContest = $this->makeMatchdayContest([
            'league_code' => 'UCL',
            'matchday_number' => 1,   // must differ to avoid unique clash
        ]);

        $entryService = app(MatchdayEntryService::class);

        $entryService->enterAllEligibleUsers($eplContest);
        $entryService->enterAllEligibleUsers($uclContest);

        $this->assertTrue(
            ContestEntry::where('contest_id', $eplContest->id)
                ->where('user_id', $member->id)
                ->exists()
        );
        $this->assertFalse(
            ContestEntry::where('contest_id', $uclContest->id)
                ->where('user_id', $member->id)
                ->exists()
        );
    }

    public function test_sub_league_with_multiple_leagues_gets_entries_for_all_of_them(): void
    {
        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create(
            $owner,
            'Mixed Crew',
            '2026-27',
            ['EPL', 'UCL'],
        );

        $member = User::factory()->create();
        app(SubLeagueService::class)->join($member, $sub->invite_code);

        $epl = $this->makeMatchdayContest(['league_code' => 'EPL', 'matchday_number' => 1]);
        $ucl = $this->makeMatchdayContest(['league_code' => 'UCL', 'matchday_number' => 1]);

        $entryService = app(MatchdayEntryService::class);
        $entryService->enterAllEligibleUsers($epl);
        $entryService->enterAllEligibleUsers($ucl);

        $this->assertSame(
            2,
            ContestEntry::where('user_id', $member->id)->count(),
        );
    }
}