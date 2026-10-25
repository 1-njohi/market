<?php

namespace Tests\Unit\Services\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\ContestLeg;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\MatchdayLeaderboardService;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchdayLeaderboardServiceTest extends TestCase
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
    }

    private function service(): MatchdayLeaderboardService
    {
        return app(MatchdayLeaderboardService::class);
    }

    private function settledMatchday(int $number, string $season = '2026-27', string $code = 'EPL'): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number} {$code}",
            'visibility' => 'public',
            'status' => 'settled',
            'is_matchday' => true,
            'season' => $season,
            'league_code' => $code,
            'matchday_number' => $number,
            'locks_at' => now()->subDays(2),
            'entry_deadline_at' => now()->subDays(2),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(1),
            'settled_at' => now()->subDay(),
        ]);
    }

    private function openMatchday(int $number, string $season = '2026-27'): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number} OPEN",
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => $season,
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->addDays(3),
            'entry_deadline_at' => now()->addDays(3),
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(4),
        ]);
    }

    private function entry(User $user, Contest $contest, int $score, string $status = 'accepted', ?\Carbon\CarbonInterface $joinedAt = null): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => $status,
            'score_correct' => $score,
            'score_units' => 0,
            'joined_at' => $joinedAt ?? now(),
        ]);
    }

    private function subLeagueWith(User $owner, array $users, string $season = '2026-27'): SubLeague
    {
        $sub = app(SubLeagueService::class)->create($owner, 'Crew-' . uniqid(), $season);

        foreach ($users as $u) {
            if ($u->id === $owner->id)
                continue;
            SubLeagueMember::create([
                'sub_league_id' => $sub->id,
                'user_id' => $u->id,
                'joined_at' => now(),
            ]);
        }

        return $sub;
    }

    public function test_empty_when_no_matchdays_settled(): void
    {
        $this->assertSame([], $this->service()->global('2026-27'));
    }

    public function test_sums_score_correct_across_settled_matchdays_for_each_user(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        $md3 = $this->settledMatchday(3);

        $this->entry($user, $md1, 3);
        $this->entry($user, $md2, 2);
        $this->entry($user, $md3, 1);

        $rows = $this->service()->global('2026-27');

        $this->assertCount(1, $rows);
        $this->assertSame(6, $rows[0]['points']);
        $this->assertSame(3, $rows[0]['matchdays_played']);
    }

    public function test_ranks_users_by_total_points_descending(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        $md1 = $this->settledMatchday(1);
        $this->entry($alice, $md1, 5);
        $this->entry($bob, $md1, 2);

        $rows = $this->service()->global('2026-27');

        $this->assertSame([$alice->id, $bob->id], array_column($rows, 'user_id'));
        $this->assertSame([1, 2], array_column($rows, 'rank'));
    }

    public function test_ties_are_broken_by_earliest_join_to_sub_league(): void
    {
        $early = User::factory()->create();
        $late = User::factory()->create();
        $owner = User::factory()->create();

        $sub = app(SubLeagueService::class)->create($owner, 'TieBreak', '2026-27');

        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id' => $early->id,
            'joined_at' => now()->subDays(5),
        ]);
        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id' => $late->id,
            'joined_at' => now()->subDays(1),
        ]);

        $md1 = $this->settledMatchday(1);
        $this->entry($early, $md1, 4);
        $this->entry($late, $md1, 4);

        $rows = $this->service()->forSubLeague($sub);

        $this->assertSame([$early->id, $late->id], array_column($rows, 'user_id'));
        $this->assertSame([1, 2], array_column($rows, 'rank'));
    }

    public function test_form_guide_returns_empty_when_no_settled_matchdays(): void
    {
        $user = User::factory()->create();

        $guide = $this->service()->formGuideForUser($user, '2026-27');

        $this->assertSame([], $guide);
    }

    public function test_form_guide_returns_last_five_settled_matchdays_in_descending_order(): void
    {
        $user = User::factory()->create();

        // Create 6 settled matchdays. The guide should only show the last 5 (MD6 down to MD2).
        for ($i = 1; $i <= 6; $i++) {
            $md = $this->settledMatchday($i);
            $this->entry($user, $md, $i);   // Score matches MD number for easy assertions
        }

        $guide = $this->service()->formGuideForUser($user, '2026-27');

        $this->assertCount(5, $guide);
        $this->assertSame(6, $guide[0]['matchday_number']);
        $this->assertSame(5, $guide[1]['matchday_number']);
        $this->assertSame(2, $guide[4]['matchday_number']);
    }

    public function test_form_guide_calculates_quartiles_correctly(): void
    {
        $user = User::factory()->create();
        $md = $this->settledMatchday(1);

        // Create 3 other users with varying scores to establish quartiles for MD1.
        // Scores: 10, 8, 6, 4, 2. User score is 8 (2nd of 5 = top quartile).
        $u1 = User::factory()->create();
        $this->entry($u1, $md, 10);
        $u2 = User::factory()->create();
        $this->entry($u2, $md, 6);
        $u3 = User::factory()->create();
        $this->entry($u3, $md, 4);
        $u4 = User::factory()->create();
        $this->entry($u4, $md, 2);

        $this->entry($user, $md, 8);

        $guide = $this->service()->formGuideForUser($user, '2026-27');

        $this->assertSame('green', $guide[0]['quartile']);
        $this->assertSame(8, $guide[0]['score_correct']);
    }

    public function test_form_guide_handles_missing_matchdays_as_not_entered(): void
    {
        $user = User::factory()->create();

        // Settle MD1, MD2, and MD3. User enters MD1 and MD3 but sits out MD2.
        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        $md3 = $this->settledMatchday(3);

        $this->entry($user, $md1, 5);
        $this->entry($user, $md3, 5);

        // Another user enters MD2 so the contest is meaningful.
        $other = User::factory()->create();
        $this->entry($other, $md2, 4);

        $guide = $this->service()->formGuideForUser($user, '2026-27');

        // Should return 3 entries: MD3, MD2 (not entered), MD1.
        $this->assertCount(3, $guide);
        $this->assertSame(3, $guide[0]['matchday_number']);
        $this->assertTrue($guide[0]['entered']);
        $this->assertSame(2, $guide[1]['matchday_number']);
        $this->assertFalse($guide[1]['entered']);
        $this->assertSame(1, $guide[2]['matchday_number']);
        $this->assertTrue($guide[2]['entered']);
    }

    public function test_from_matchday_filter_excludes_earlier_matchdays(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        $md3 = $this->settledMatchday(3);

        $this->entry($user, $md1, 9);   // excluded by from=2
        $this->entry($user, $md2, 3);
        $this->entry($user, $md3, 2);

        $rows = $this->service()->global('2026-27', fromMatchday: 2);

        $this->assertSame(5, $rows[0]['points']);
        $this->assertSame(2, $rows[0]['matchdays_played']);
    }

    public function test_from_matchday_filter_defaults_to_1(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $this->entry($user, $md1, 7);

        $rows = $this->service()->global('2026-27');

        $this->assertSame(7, $rows[0]['points']);
    }

    public function test_from_matchday_beyond_last_settled_matchday_returns_empty(): void
    {
        $user = User::factory()->create();
        $md1 = $this->settledMatchday(1);
        $this->entry($user, $md1, 3);

        $this->assertSame([], $this->service()->global('2026-27', fromMatchday: 99));
    }

    public function test_only_settled_contests_count(): void
    {
        $user = User::factory()->create();

        $settled = $this->settledMatchday(1);
        $open = $this->openMatchday(2);

        $this->entry($user, $settled, 5);
        $this->entry($user, $open, 99);   // must be ignored

        $rows = $this->service()->global('2026-27');

        $this->assertSame(5, $rows[0]['points']);
        $this->assertSame(1, $rows[0]['matchdays_played']);
    }

    public function test_points_by_matchday_returns_series_for_user(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);

        $this->entry($user, $md1, 3);
        $this->entry($user, $md2, 4);

        $series = $this->service()->pointsByMatchdayForUser($user, '2026-27');

        $this->assertSame([1 => 3, 2 => 4], $series);
    }

    public function test_sub_league_leaderboard_scopes_to_sub_league_leagues(): void
    {
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create(
            $owner,
            'EPL-only',
            '2026-27',
            ['EPL'],
        );

        $member = User::factory()->create();
        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id' => $member->id,
            'joined_at' => now(),
        ]);

        $eplMd = $this->settledMatchday(1, '2026-27', 'EPL');
        $uclMd = $this->settledMatchday(1, '2026-27', 'UCL');

        $this->entry($member, $eplMd, 3);
        $this->entry($member, $uclMd, 9);   // must be excluded

        $rows = $this->service()->forSubLeague($sub);

        $this->assertCount(1, $rows);
        $this->assertSame(3, $rows[0]['points']);
    }

    public function test_summary_for_user_returns_nulls_when_no_settled_matchdays(): void
    {
        $user = User::factory()->create();

        $summary = $this->service()->summaryForUser($user, '2026-27');

        $this->assertNull($summary['pick_accuracy']);
        $this->assertNull($summary['points_per_matchday']);
        $this->assertSame(0, $summary['matchdays_played']);
        $this->assertSame(0, $summary['picks_submitted']);
        $this->assertNull($summary['avg_picks_per_matchday']);
        $this->assertNull($summary['completion_pct']);
        $this->assertSame(0, $summary['current_streak']);
        $this->assertSame(0, $summary['matchdays_settled']);
    }

    public function test_summary_pick_accuracy_is_correct_over_picks_submitted(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);

        // 3 correct of 5 picks on MD1, 2 correct of 4 picks on MD2.
        $this->entry($user, $md1, 3);
        $this->entry($user, $md2, 2);

        // Give the entries known pick counts so picks_submitted is real.
        ContestPick::where('contest_entry_id', ContestEntry::where('contest_id', $md1->id)->value('id'))
            ->count(); // noop — see note below

        // Simpler: create picks directly.
        $entry1 = ContestEntry::where('contest_id', $md1->id)->where('user_id', $user->id)->first();
        $entry2 = ContestEntry::where('contest_id', $md2->id)->where('user_id', $user->id)->first();

        ContestPick::withoutMatchdayLock(function () use ($md1, $md2, $entry1, $entry2) {
            foreach (range(1, 5) as $i) {
                ContestPick::create([
                    'contest_entry_id' => $entry1->id,
                    'contest_leg_id' => $this->leg($md1)->id,
                    'selection' => 'Home',
                    'odds_at_pick' => 2.00,
                    'status' => $i <= 3 ? 'correct' : 'incorrect',
                ]);
            }

            foreach (range(1, 4) as $i) {
                ContestPick::create([
                    'contest_entry_id' => $entry2->id,
                    'contest_leg_id' => $this->leg($md2)->id,
                    'selection' => 'Home',
                    'odds_at_pick' => 2.00,
                    'status' => $i <= 2 ? 'correct' : 'incorrect',
                ]);
            }
        });

        $summary = $this->service()->summaryForUser($user, '2026-27');

        // (3 + 2) / (5 + 4) = 5 / 9 = 55.6%
        $this->assertSame(55.6, $summary['pick_accuracy']);
        $this->assertSame(9, $summary['picks_submitted']);
    }

    public function test_summary_points_per_matchday_is_average_score_correct(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        $md3 = $this->settledMatchday(3);

        $this->entry($user, $md1, 4);
        $this->entry($user, $md2, 2);
        $this->entry($user, $md3, 6);

        $summary = $this->service()->summaryForUser($user, '2026-27');

        // (4 + 2 + 6) / 3 = 4.00
        $this->assertSame(4.0, $summary['points_per_matchday']);
        $this->assertSame(3, $summary['matchdays_played']);
    }

    public function test_summary_matchdays_played_counts_only_settled(): void
    {
        $user = User::factory()->create();

        $settled = $this->settledMatchday(1);
        $open = $this->openMatchday(2);

        $this->entry($user, $settled, 3);
        $this->entry($user, $open, 99);   // must be ignored

        $summary = $this->service()->summaryForUser($user, '2026-27');

        $this->assertSame(1, $summary['matchdays_played']);
    }

    public function test_summary_avg_picks_per_matchday_and_completion_use_required_legs(): void
    {
        $user = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        ContestPick::withoutMatchdayLock(function () use ($md1, $md2, $user) {
            foreach ([[$md1, 3], [$md2, 4]] as [$md, $submitted]) {
                for ($i = 0; $i < 4; $i++) {
                    $this->leg($md);
                }

                $entry = $this->entry($user, $md, 0);
                foreach (range(1, $submitted) as $i) {
                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id' => ContestLeg::where('contest_id', $md->id)->skip($i - 1)->first()->id,
                        'selection' => 'Home',
                        'odds_at_pick' => 2.00,
                        'status' => 'pending',
                    ]);
                }
            }
        });
        $summary = $this->service()->summaryForUser($user, '2026-27');

        // Avg = (3 + 4) / 2 = 3.50 picks per matchday.
        $this->assertSame(3.5, $summary['avg_picks_per_matchday']);

        // Completion = 7 submitted / 8 required = 87.5%.
        $this->assertSame(87.5, $summary['completion_pct']);
    }

    public function test_summary_current_streak_is_consecutive_settled_matchdays_with_a_correct_pick(): void
    {
        $user = User::factory()->create();

        // Reverse-chronological settlement: MD4, MD3, MD2, MD1.
        // Streak: 2 correct, 3 correct, 0 correct, 4 correct.
        // The current streak is 2 (MD4 and MD3), because MD2 broke it.
        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);
        $md3 = $this->settledMatchday(3);
        $md4 = $this->settledMatchday(4);

        $this->entry($user, $md1, 4);
        $this->entry($user, $md2, 0);
        $this->entry($user, $md3, 3);
        $this->entry($user, $md4, 2);

        $summary = $this->service()->summaryForUser($user, '2026-27');

        $this->assertSame(2, $summary['current_streak']);
    }

    private function leg(Contest $contest, string $status = 'pending'): ContestLeg
    {
        $home = \App\Models\Team::firstOrCreate(['id_on_api' => 900000 + random_int(1, 99999)], ['name' => 'H']);
        $away = \App\Models\Team::firstOrCreate(['id_on_api' => 910000 + random_int(1, 99999)], ['name' => 'A']);

        $fixture = \App\Models\Fixture::create([
            'id_on_api' => 900000 + random_int(1, 999999),
            'date' => now()->addDays(3),
            'timestamp' => now()->addDays(3)->timestamp,
            'status_short' => $status === 'pending' ? 'NS' : 'FT',
            'league_id' => 39,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        \App\Models\Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        return ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'status' => $status,
        ]);
    }

    public function test_live_for_user_returns_empty_when_no_active_matchdays(): void
    {
        $user = User::factory()->create();

        $this->assertSame([], $this->service()->liveForUser($user, '2026-27'));
    }

    public function test_live_for_user_reports_correct_incorrect_pending_counts(): void
    {
        $user = User::factory()->create();

        // Underway matchday — status not settled, locks_at in the past.
        $contest = $this->underwayMatchday(1);
        $entry = $this->entry($user, $contest, 0);   // score_correct written by scorer

        // Three legs on the contest.
        $legA = $this->leg($contest, 'won');
        $legB = $this->leg($contest, 'lost');
        $legC = $this->leg($contest, 'pending');

        // Picks: correct, incorrect, pending.
        ContestPick::withoutMatchdayLock(function () use ($entry, $legA, $legB, $legC) {
            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legA->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'status' => 'correct',
            ]);
            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legB->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'status' => 'incorrect',
            ]);
            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $legC->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.00,
                'status' => 'pending',
            ]);
        });

        $rows = $this->service()->liveForUser($user, '2026-27');

        $this->assertCount(1, $rows);
        $this->assertSame($contest->uuid, $rows[0]['uuid']);
        $this->assertSame(1, $rows[0]['correct']);
        $this->assertSame(1, $rows[0]['incorrect']);
        $this->assertSame(1, $rows[0]['pending']);
        $this->assertSame(3, $rows[0]['legs_total']);
        $this->assertSame(2, $rows[0]['legs_resolved']);
    }

    public function test_live_for_user_includes_rank_delta(): void
    {
        $user = User::factory()->create();

        $contest = $this->underwayMatchday(1);
        $entry = $this->entry($user, $contest, 0);

        // Simulate: rank moved from 5 → 3 since the last scoring pass.
        $entry->update([
            'rank_previous' => 5,
            'rank_current' => 3,
        ]);

        $rows = $this->service()->liveForUser($user, '2026-27');

        $this->assertSame(3, $rows[0]['rank_current']);
        $this->assertSame(5, $rows[0]['rank_previous']);
        $this->assertSame(2, $rows[0]['rank_delta']);   // moved up 2 places
    }

    public function test_live_for_user_excludes_users_not_entered(): void
    {
        $user = User::factory()->create();

        $this->underwayMatchday(1);   // active matchday, no entry for $user

        $rows = $this->service()->liveForUser($user, '2026-27');

        $this->assertSame([], $rows);
    }

    public function test_live_for_user_excludes_settled_contests(): void
    {
        $user = User::factory()->create();

        $this->settledMatchday(1);
        $this->entry($user, $this->settledMatchday(2), 5);   // wrong contest, but tests the filter

        $rows = $this->service()->liveForUser($user, '2026-27');

        $this->assertSame([], $rows);
    }

    private function underwayMatchday(int $number, string $season = '2026-27', string $code = 'EPL'): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number} LIVE",
            'visibility' => 'public',
            'status' => 'underway',
            'is_matchday' => true,
            'season' => $season,
            'league_code' => $code,
            'matchday_number' => $number,
            'locks_at' => now()->subHours(2),
            'entry_deadline_at' => now()->subHours(2),
            'starts_at' => now()->subHours(1),
            'ends_at' => now()->addHours(3),
        ]);
    }

}