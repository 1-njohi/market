<?php

namespace Tests\Feature\Matchday;

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
use App\Models\Team;
use App\Models\User;
use App\Notifications\Matchday\MatchdayCreatedNotification;
use App\Notifications\Matchday\MatchdayLocksSoonNotification;
use App\Notifications\Matchday\MatchdaySettledNotification;
use App\Notifications\Matchday\SubLeagueJoinedNotification;
use App\Services\ContestScoringService;
use App\Services\Matchday\MatchdayEntryService;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MatchdayNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));

        League::firstOrCreate(
            ['id' => 39],
            [
                'id_on_api' => 39, 'sport_id' => 1, 'name' => 'Premier League',
                'country_id' => 1, 'country' => 'England', 'season' => 2026,
            ],
        );
    }

    private function matchdayContest(array $overrides = []): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create(array_merge([
            'host_id'           => $host->id,
            'name'              => 'MD1 EPL',
            'visibility'        => 'public',
            'status'            => 'open',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => 1,
            'locks_at'          => now()->addDays(3),
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(3),
            'ends_at'           => now()->addDays(4),
        ], $overrides));
    }

    private function leg(Contest $contest, string $status = 'pending'): ContestLeg
    {
        $home = Team::firstOrCreate(['id_on_api' => 900000 + random_int(1, 99999)], ['name' => 'H']);
        $away = Team::firstOrCreate(['id_on_api' => 910000 + random_int(1, 99999)], ['name' => 'A']);

        $fixture = Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 999999),
            'date'         => now()->addDays(3),
            'timestamp'    => now()->addDays(3)->timestamp,
            'status_short' => $status === 'pending' ? 'NS' : 'FT',
            'league_id'    => 39,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.50, 'status' => 'pending']);

        return ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'status'     => $status,
        ]);
    }

    private function entry(User $user, Contest $contest, int $score = 0): ContestEntry
    {
        return ContestEntry::create([
            'contest_id'    => $contest->id,
            'user_id'       => $user->id,
            'status'        => 'accepted',
            'score_correct' => $score,
            'score_units'   => 0,
            'joined_at'     => now(),
        ]);
    }

    private function subLeague(User $owner, string $name = 'Crew', string $season = '2026-27'): SubLeague
    {
        return app(SubLeagueService::class)->create($owner, $name, $season);
    }

    public function test_matchday_contest_created_notifies_all_entrants(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sub   = $this->subLeague($owner);

        $member = User::factory()->create();
        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id'       => $member->id,
            'joined_at'     => now(),
        ]);

        $contest = $this->matchdayContest();

        app(MatchdayEntryService::class)->enterAllEligibleUsers($contest);

        Notification::assertSentTo($owner,  MatchdayCreatedNotification::class);
        Notification::assertSentTo($member, MatchdayCreatedNotification::class);
    }

    public function test_matchday_locks_in_24h_notification_is_dispatched_to_users_without_full_picks(): void
    {
        Notification::fake();

        $contest = $this->matchdayContest(['locks_at' => now()->addHours(12)]);

        $fullPicks = User::factory()->create();
        $partial   = User::factory()->create();

        $legA = $this->leg($contest);
        $legB = $this->leg($contest);

        $entryFull    = $this->entry($fullPicks, $contest);
        $entryPartial = $this->entry($partial,   $contest);

        // Full picks: both legs.
        ContestPick::create(['contest_entry_id' => $entryFull->id, 'contest_leg_id' => $legA->id, 'selection' => 'Home', 'odds_at_pick' => 2.50, 'status' => 'pending']);
        ContestPick::create(['contest_entry_id' => $entryFull->id, 'contest_leg_id' => $legB->id, 'selection' => 'Home', 'odds_at_pick' => 2.50, 'status' => 'pending']);

        // Partial picks: only legA.
        ContestPick::create(['contest_entry_id' => $entryPartial->id, 'contest_leg_id' => $legA->id, 'selection' => 'Home', 'odds_at_pick' => 2.50, 'status' => 'pending']);

        $this->artisan('matchdays:remind')->assertSuccessful();

        Notification::assertSentTo($partial, MatchdayLocksSoonNotification::class);
        Notification::assertNotSentTo($fullPicks, MatchdayLocksSoonNotification::class);
    }

    public function test_matchday_settled_notification_is_sent_to_every_participant_with_their_points(): void
    {
        Notification::fake();

        $winner = User::factory()->create();
        $loser  = User::factory()->create();

        $contest = $this->matchdayContest(['status' => 'open']);

        $leg = $this->leg($contest);
        $entryW = $this->entry($winner, $contest);
        $entryL = $this->entry($loser,  $contest);

        ContestPick::create(['contest_entry_id' => $entryW->id, 'contest_leg_id' => $leg->id, 'selection' => 'Home', 'odds_at_pick' => 2.50, 'status' => 'pending']);
        ContestPick::create(['contest_entry_id' => $entryL->id, 'contest_leg_id' => $leg->id, 'selection' => 'Away', 'odds_at_pick' => 3.20, 'status' => 'pending']);

        // Resolve the leg as a Home win.
        $leg->update(['status' => 'won', 'result_selections' => ['Home']]);

        app(ContestScoringService::class)->scoreFinal($contest->fresh());

        Notification::assertSentTo($winner, MatchdaySettledNotification::class);
        Notification::assertSentTo($loser,  MatchdaySettledNotification::class);
    }

    public function test_sub_league_joined_notification_sent_to_owner(): void
    {
        Notification::fake();

        $owner  = User::factory()->create();
        $sub    = $this->subLeague($owner);
        $joiner = User::factory()->create();

        $this->actingAs($joiner)->postJson(route('sub-leagues.join'), [
            'invite_code' => $sub->invite_code,
        ])->assertOk();

        Notification::assertSentTo($owner, SubLeagueJoinedNotification::class);
    }

    public function test_top_three_notification_sent_to_sub_league_members_after_settlement(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $sub   = $this->subLeague($owner);

        $m2 = User::factory()->create();
        $m3 = User::factory()->create();
        $m4 = User::factory()->create();   // 4th place — should NOT be notified

        foreach ([$m2, $m3, $m4] as $m) {
            SubLeagueMember::create([
                'sub_league_id' => $sub->id,
                'user_id'       => $m->id,
                'joined_at'     => now(),
            ]);
        }

        $contest = $this->matchdayContest();

        $leg = $this->leg($contest);

        // Four entries; settle them with descending scores.
        foreach ([[$owner, 'Home', 1], [$m2, 'Home', 1], [$m3, 'Home', 1], [$m4, 'Away', 1]] as [$u, $sel, $n]) {
            $e = $this->entry($u, $contest);
            for ($i = 0; $i < $n; $i++) {
                ContestPick::create([
                    'contest_entry_id' => $e->id,
                    'contest_leg_id'   => $leg->id,
                    'selection'        => $sel,
                    'odds_at_pick'     => 2.50,
                    'status'           => 'pending',
                ]);
            }
        }

        $leg->update(['status' => 'won', 'result_selections' => ['Home']]);

        app(ContestScoringService::class)->scoreFinal($contest->fresh());

        // Top three of the sub-league get the sub-league recap; fourth doesn't.
        Notification::assertSentTo($owner, \App\Notifications\Matchday\MatchdayTopThreeNotification::class);
        Notification::assertSentTo($m2,    \App\Notifications\Matchday\MatchdayTopThreeNotification::class);
        Notification::assertSentTo($m3,    \App\Notifications\Matchday\MatchdayTopThreeNotification::class);
        Notification::assertNotSentTo($m4, \App\Notifications\Matchday\MatchdayTopThreeNotification::class);
    }
}