<?php

namespace Tests\Feature\Console;

use App\Models\Fixture;
use App\Models\BetslipUserPurchase;
use App\Models\Betslip;
use App\Models\User;
use App\Models\Odd;
use App\Models\Contest;
use App\Models\ContestLeg;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncLiveFixturesCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    private static int $apiIdCounter = 400000;

    public function test_skips_api_call_entirely_when_no_candidates(): void
    {
        // FT fixture — not in candidate statuses.
        $this->makeFixture(['status_short' => 'FT', 'date' => now()]);

        Http::fake(['*' => Http::response('should not be called', 500)]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_skips_fixtures_outside_candidate_window(): void
    {
        // Too old (4h past) and too far (30m future).
        $this->makeFixture(['status_short' => 'NS', 'date' => now()->subHours(4)]);
        $this->makeFixture(['status_short' => 'NS', 'date' => now()->addMinutes(30)]);

        Http::fake(['*' => Http::response('should not be called', 500)]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_updates_live_fixture_status_and_scores(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(
                    id: 1001,
                    statusShort: '1H',
                    statusElapsed: 30,
                    goalsHome: 1,
                    goalsAway: 0,
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $fixture->refresh();
        $this->assertSame('1H', $fixture->status_short);
        $this->assertSame(30, $fixture->status_elapsed);
        $this->assertSame(1, $fixture->goals_home);
        $this->assertSame(0, $fixture->goals_away);
    }

    public function test_writes_fulltime_scores_and_winner_when_status_is_ft(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => '2H',
            'date' => now()->subMinutes(5),
        ]);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(
                    id: 1001,
                    statusShort: 'FT',
                    statusElapsed: 90,
                    goalsHome: 2,
                    goalsAway: 1,
                    halftimeHome: 1,
                    halftimeAway: 0,
                    fulltimeHome: 2,
                    fulltimeAway: 1,
                    homeWinner: true,
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $fixture->refresh();
        $this->assertSame('FT', $fixture->status_short);
        $this->assertSame(2, $fixture->goals_home);
        $this->assertSame(1, $fixture->goals_away);
        $this->assertSame(1, $fixture->halftime_home);
        $this->assertSame(0, $fixture->halftime_away);
        $this->assertSame(2, $fixture->fulltime_home);
        $this->assertSame(1, $fixture->fulltime_away);
        $this->assertTrue($fixture->home_winner);
    }

    public function test_does_not_overwrite_home_winner_while_match_is_running(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => '2H',
            'date' => now()->subMinutes(5),
            'home_winner' => false,
        ]);

        // API sends `null` for winner while live. We must not clobber.
        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(
                    id: 1001,
                    statusShort: '2H',
                    statusElapsed: 70,
                    goalsHome: 1,
                    goalsAway: 1,
                    homeWinner: null,
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $fixture->refresh();
        $this->assertFalse($fixture->home_winner);
    }

    public function test_writes_fixture_events_from_api_goals(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => '2H',
            'date' => now()->subMinutes(5),
        ]);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(
                    id: 1001,
                    statusShort: 'FT',
                    statusElapsed: 90,
                    goalsHome: 2,
                    goalsAway: 1,
                    halftimeHome: 1,
                    halftimeAway: 0,
                    fulltimeHome: 2,
                    fulltimeAway: 1,
                    homeWinner: true,
                    events: [
                        ['elapsed' => 20, 'extra' => null, 'teamId' => self::HOME_TEAM_ID, 'playerId' => 501, 'assistId' => null, 'type' => 'Goal', 'detail' => 'Normal Goal'],
                        ['elapsed' => 45, 'extra' => null, 'teamId' => self::AWAY_TEAM_ID, 'playerId' => 502, 'assistId' => null, 'type' => 'Goal', 'detail' => 'Normal Goal'],
                        ['elapsed' => 75, 'extra' => null, 'teamId' => self::HOME_TEAM_ID, 'playerId' => 503, 'assistId' => null, 'type' => 'Goal', 'detail' => 'Normal Goal'],
                    ],
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $events = DB::table('fixture_events')
            ->where('fixture_id', $fixture->id)
            ->orderBy('time_elapsed')
            ->get();

        $this->assertCount(3, $events);
        $this->assertSame(20, $events[0]->time_elapsed);
        $this->assertSame(self::HOME_TEAM_ID, $events[0]->team_id);
        $this->assertSame('Goal', $events[0]->type);
        $this->assertSame(75, $events[2]->time_elapsed);
    }

    public function test_does_not_duplicate_fixture_events_on_rerun(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => '2H',
            'date' => now()->subMinutes(5),
        ]);

        $payload = $this->livePayload(
            id: 1001,
            statusShort: 'FT',
            statusElapsed: 90,
            goalsHome: 1,
            goalsAway: 0,
            halftimeHome: 1,
            halftimeAway: 0,
            fulltimeHome: 1,
            fulltimeAway: 0,
            homeWinner: true,
            events: [
                ['elapsed' => 30, 'extra' => null, 'teamId' => self::HOME_TEAM_ID, 'playerId' => 501, 'assistId' => null, 'type' => 'Goal', 'detail' => 'Normal Goal'],
            ],
        );

        Http::fake(['*/fixtures?ids=*' => Http::response($this->envelope([$payload]), 200)]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();
        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame(1, DB::table('fixture_events')->where('fixture_id', $fixture->id)->count());
    }

    public function test_batches_candidates_into_a_single_api_call(): void
    {
        $this->makeFixture(['id_on_api' => 1001, 'date' => now()]);
        $this->makeFixture(['id_on_api' => 1002, 'date' => now()]);
        $this->makeFixture(['id_on_api' => 1003, 'date' => now()]);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 10),
                $this->livePayload(id: 1002, statusShort: 'HT', statusElapsed: 45),
                $this->livePayload(id: 1003, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        Http::assertSentCount(1);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ids=1001-1002-1003');
        });
    }

    public function test_logs_error_on_api_failure_but_returns_success(): void
    {
        $this->makeFixture(['id_on_api' => 1001, 'date' => now()]);

        Http::fake(['*/fixtures?ids=*' => Http::response('boom', 500)]);

        Log::spy();

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        Log::shouldHaveReceived('error')
            ->withArgs(fn($msg) => is_string($msg) && str_contains($msg, 'Failed to sync live fixtures'))
            ->once();
    }



    // ────────────── Betslip transition: NS → live ───────────────────────────

    public function test_ns_to_1h_transition_marks_pending_betslips_underway(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $betslip->fresh()->status);
    }

    public function test_ns_to_ht_transition_also_marks_pending_betslips_underway(): void
    {
        // Late-sync case: the first payload we see has the fixture
        // already at HT. That still counts as a "first time live".
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: 'HT', statusElapsed: 45),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $betslip->fresh()->status);
    }

    public function test_ns_to_ns_does_not_touch_pending_betslips(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('pending', $betslip->fresh()->status);
    }

    public function test_second_live_sync_does_not_change_already_underway_betslip(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);

        // First sync: NS → 1H. Betslip flips.
        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);
        $this->artisan('fixtures:sync-live')->assertSuccessful();
        $this->assertSame('underway', $betslip->fresh()->status);

        // Second sync: 1H → 1H. No transition, so no write. Even if the
        // transition check let it through, the status='pending' filter
        // would make the update a no-op.
        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 10),
            ]), 200),
        ]);
        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $betslip->fresh()->status);
    }

    public function test_settled_betslips_are_not_touched_by_transition(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture, status: 'settled');

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('settled', $betslip->fresh()->status);
    }

    public function test_betslips_with_no_odds_on_the_live_fixture_are_not_touched(): void
    {
        $liveFixture = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $otherFixture = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        // A betslip on a *different* fixture.
        $otherBetslip = $this->makeBetslipWithOdd($otherFixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('pending', $otherBetslip->fresh()->status);
    }

    public function test_multi_fixture_betslip_flips_when_any_leg_goes_live(): void
    {
        $fx1 = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $fx2 = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        $betslip = $this->makeBetslipWithOdds([$fx1, $fx2]);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $betslip->fresh()->status);
    }

    // ────────────── BetslipUserPurchase transition: NS → live ───────────────

    public function test_ns_to_1h_transition_marks_pending_purchases_underway(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);
        $purchase = $this->makePurchaseFor($betslip);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $betslip->fresh()->status);
        $this->assertSame('underway', $purchase->fresh()->status);
    }

    public function test_ns_to_ns_does_not_touch_pending_purchases(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);
        $purchase = $this->makePurchaseFor($betslip);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('pending', $betslip->fresh()->status);
        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_settled_purchases_are_not_touched_by_transition(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $betslip = $this->makeBetslipWithOdd($fixture);
        $purchase = $this->makePurchaseFor($betslip, status: 'won');

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        // Betslip flips — it was pending.
        $this->assertSame('underway', $betslip->fresh()->status);
        // Purchase does not — it was already terminal.
        $this->assertSame('won', $purchase->fresh()->status);
    }

    public function test_purchases_on_other_betslips_are_not_touched(): void
    {
        $liveFixture = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $otherFixture = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        $liveBetslip = $this->makeBetslipWithOdd($liveFixture);
        $otherBetslip = $this->makeBetslipWithOdd($otherFixture);

        $livePurchase = $this->makePurchaseFor($liveBetslip);
        $otherPurchase = $this->makePurchaseFor($otherBetslip);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $livePurchase->fresh()->status);
        $this->assertSame('pending', $otherPurchase->fresh()->status);
        $this->assertSame('pending', $otherBetslip->fresh()->status);
    }

    public function test_multi_fixture_betslip_flips_all_its_purchases(): void
    {
        $fx1 = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $fx2 = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        $betslip = $this->makeBetslipWithOdds([$fx1, $fx2]);
        $purchaseA = $this->makePurchaseFor($betslip, buyerSuffix: 'A');
        $purchaseB = $this->makePurchaseFor($betslip, buyerSuffix: 'B');

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $purchaseA->fresh()->status);
        $this->assertSame('underway', $purchaseB->fresh()->status);
    }

    // ────────────── Contest transition: locked → underway ──────────────────

    public function test_locked_contest_transitions_to_underway_when_its_fixture_goes_live(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contest, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);
    }

    public function test_open_contest_transitions_to_underway_defensively_and_logs_warning(): void
    {
        // Defensive path: the lock command lagged, or the deadline was
        // misconfigured, so the contest is still 'open' when a fixture
        // kicks off. Transition anyway — user-facing reality wins — and
        // log a warning so the scheduler issue is visible.
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'open');
        $this->makeContestLeg($contest, $fixture);

        Log::spy();

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn($msg) => is_string($msg)
                && str_contains($msg, 'Contest entering underway'))
            ->once();
    }

    public function test_underway_contest_is_not_touched_by_fixture_kickoff(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'underway');
        $this->makeContestLeg($contest, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);
    }

    public function test_settled_contest_is_not_touched_by_fixture_kickoff(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'settled');
        $this->makeContestLeg($contest, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('settled', $contest->fresh()->status);
    }

    public function test_cancelled_contest_is_not_touched_by_fixture_kickoff(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'cancelled');
        $this->makeContestLeg($contest, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('cancelled', $contest->fresh()->status);
    }

    public function test_contest_with_no_legs_on_live_fixture_is_not_touched(): void
    {
        $liveFixture = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $otherFixture = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        $contest = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contest, $otherFixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_multi_fixture_contest_transitions_when_any_fixture_goes_live(): void
    {
        $fx1 = $this->makeFixture(['id_on_api' => 1001, 'status_short' => 'NS', 'date' => now()]);
        $fx2 = $this->makeFixture(['id_on_api' => 1002, 'status_short' => 'NS', 'date' => now()]);

        $contest = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contest, $fx1, marketId: 1);
        $this->makeContestLeg($contest, $fx2, marketId: 5);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
                $this->livePayload(id: 1002, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);
    }

    public function test_two_contests_on_same_fixture_both_transition(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contestA = $this->makeContest(status: 'locked');
        $contestB = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contestA, $fixture);
        $this->makeContestLeg($contestB, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contestA->fresh()->status);
        $this->assertSame('underway', $contestB->fresh()->status);
    }

    public function test_locked_contest_whose_fixture_stays_ns_is_not_touched(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contest, $fixture);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: 'NS'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('locked', $contest->fresh()->status);
    }

    public function test_multi_leg_contest_on_same_fixture_transitions_once(): void
    {
        // Two legs on the same fixture (different markets). The contest
        // should transition once, not twice — the ->distinct() pluck on
        // contest_id covers this, but the test pins the behaviour.
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'status_short' => 'NS',
            'date' => now(),
        ]);

        $contest = $this->makeContest(status: 'locked');
        $this->makeContestLeg($contest, $fixture, marketId: 1);
        $this->makeContestLeg($contest, $fixture, marketId: 5);

        Http::fake([
            '*/fixtures?ids=*' => Http::response($this->envelope([
                $this->livePayload(id: 1001, statusShort: '1H', statusElapsed: 5),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $this->assertSame('underway', $contest->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    private function makeFixture(array $attributes = []): Fixture
    {
        return Fixture::forceCreate(array_merge([
            'id_on_api' => self::$apiIdCounter++,
            'date' => now(),
            'timestamp' => now()->timestamp,
            'status_short' => 'NS',
            'status_long' => 'Not Started',
            'home_team_id' => self::HOME_TEAM_ID,
            'away_team_id' => self::AWAY_TEAM_ID,
            'home_winner' => false,
            'settled' => false,
        ], $attributes));
    }

    /** @param array<int, array<string, mixed>> $fixtures */
    private function envelope(array $fixtures): array
    {
        return [
            'get' => 'fixtures',
            'results' => count($fixtures),
            'response' => $fixtures,
        ];
    }

    /**
     * @param  array<int, array{elapsed:int, extra:?int, teamId:int, playerId:?int, assistId:?int, type:string, detail:?string}>  $events
     */
    private function livePayload(
        int $id,
        string $statusShort,
        ?int $statusElapsed = null,
        ?int $goalsHome = null,
        ?int $goalsAway = null,
        ?int $halftimeHome = null,
        ?int $halftimeAway = null,
        ?int $fulltimeHome = null,
        ?int $fulltimeAway = null,
        ?bool $homeWinner = null,
        array $events = [],
    ): array {
        return [
            'fixture' => [
                'id' => $id,
                'referee' => null,
                'timezone' => 'UTC',
                'date' => now()->toIso8601String(),
                'timestamp' => now()->timestamp,
                'periods' => ['first' => null, 'second' => null],
                'venue' => ['id' => 1, 'name' => 'Mock', 'city' => 'Testville'],
                'status' => ['long' => $statusShort, 'short' => $statusShort, 'elapsed' => $statusElapsed, 'extra' => null],
            ],
            'league' => ['id' => 39, 'name' => 'Premier League', 'season' => 2026],
            'teams' => [
                'home' => ['id' => self::HOME_TEAM_ID, 'name' => 'Home', 'winner' => $homeWinner],
                'away' => ['id' => self::AWAY_TEAM_ID, 'name' => 'Away', 'winner' => $homeWinner === null ? null : !$homeWinner],
            ],
            'goals' => ['home' => $goalsHome, 'away' => $goalsAway],
            'score' => [
                'halftime' => ['home' => $halftimeHome, 'away' => $halftimeAway],
                'fulltime' => ['home' => $fulltimeHome, 'away' => $fulltimeAway],
                'extratime' => ['home' => null, 'away' => null],
                'penalty' => ['home' => null, 'away' => null],
            ],
            'events' => array_map(function ($e) {
                return [
                    'time' => ['elapsed' => $e['elapsed'], 'extra' => $e['extra']],
                    'team' => ['id' => $e['teamId'], 'name' => 'Team'],
                    'player' => $e['playerId'] !== null ? ['id' => $e['playerId'], 'name' => 'Player'] : null,
                    'assist' => $e['assistId'] !== null ? ['id' => $e['assistId'], 'name' => 'Assist'] : null,
                    'type' => $e['type'],
                    'detail' => $e['detail'],
                ];
            }, $events),
        ];
    }

    /**
     * Create a betslip with one odd attached, on the given fixture.
     */
    private function makeBetslipWithOdd(Fixture $fixture, string $status = 'pending'): Betslip
    {
        return $this->makeBetslipWithOdds([$fixture], $status);
    }

    /**
     * Create a betslip with one odd attached per given fixture.
     *
     * @param  array<int, Fixture>  $fixtures
     */
    private function makeBetslipWithOdds(array $fixtures, string $status = 'pending'): Betslip
    {
        $seller = User::factory()->create();

        $betslip = Betslip::forceCreate([
            'user_id' => $seller->id,
            'total_odds' => 2.50,
            'price' => 100.00,
            'status' => $status,
            'remaining' => count($fixtures),
            'is_winner' => false,
            'code' => 'TST-' . uniqid(),
        ]);

        foreach ($fixtures as $fixture) {
            $odd = $this->makeOddFor($fixture);

            DB::table('betslip_odd')->insert([
                'betslip_id' => $betslip->id,
                'odd_id' => $odd->id,
                'status' => 'pending',
                'odd_value_at_time' => 2.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $betslip;
    }

    /**
     *
     * @param  string  $buyerSuffix  Differentiates buyer/seller pairs when
     *                               a test needs multiple purchases on one
     *                               betslip (e.g. 'A', 'B').
     */
    private function makePurchaseFor(
        Betslip $betslip,
        string $status = 'pending',
        string $buyerSuffix = '',
    ): BetslipUserPurchase {
        $buyer = User::factory()->create(['name' => "Buyer {$buyerSuffix}"]);
        $seller = User::find($betslip->user_id);

        return BetslipUserPurchase::forceCreate([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 100.00,
            'total_odds' => 2.50,
            'status' => $status,
            'purchased_at' => now(),
        ]);
    }


    /**
     * Create a pending odd on the given fixture.
     */
    private function makeOddFor(Fixture $fixture): Odd
    {
        $id = DB::table('odds')->insertGetId([
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'value' => 'Home',
            'odd' => 2.50,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }

    /**
     * Create a Contest with sensible defaults for the sync-live test.
     * The deadline is in the past because we only ever test the fixture
     * kickoff path here — deadline handling is covered by
     * LockDueContestsCommandTest.
     */
    private function makeContest(string $status = 'locked'): Contest
    {
        $host = User::factory()->create();

        return Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid' => (string) Str::uuid(),
            'host_id' => $host->id,
            'name' => 'Sync Live Contest Test',
            'visibility' => 'private',
            'status' => $status,
            'entry_deadline_at' => now()->subHour(),
            'starts_at' => now(),
            'ends_at' => now()->addHours(2),
        ]));
    }

    private function makeContestLeg(Contest $contest, Fixture $fixture, int $marketId = 1): ContestLeg
    {
        return ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'status' => 'pending',
        ]);
    }
}