<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\User;
use App\Notifications\ContestSettledNotification;
use App\Services\MarketSettlementService;
use App\Services\PlatformAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ContestCascadeTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;
    private const MARKET_MATCH_WINNER = 1;

    private static int $apiIdCounter = 700000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketSettlementService::class);

        $platform = User::factory()->create(['name' => 'Platform']);
        $mock = Mockery::mock(PlatformAccount::class);
        $mock->shouldReceive('user')->andReturn($platform);
        $this->app->instance(PlatformAccount::class, $mock);

        Notification::fake();
    }

    public function test_resolves_leg_and_ranks_entries_when_all_odds_are_known(): void
    {
        // Fixture: Away wins 0-2. Only the Away odd exists for this market
        // (keeps this test focused on scoring/ranking, not multi-odd resolution).
        $fixture = $this->makeFixture([
            'goals_home' => 0,
            'goals_away' => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $host = User::factory()->create();
        $winner = User::factory()->create(['name' => 'Winner']);
        $loser = User::factory()->create(['name' => 'Loser']);

        $contest = $this->makeContest($host);
        $leg = $this->makeLeg($contest, $fixture, self::MARKET_MATCH_WINNER);

        $winnerEntry = $this->makeEntry($contest, $winner, order: 1);
        $loserEntry = $this->makeEntry($contest, $loser, order: 2);

        $this->makePick($winnerEntry, $leg, 'Away', 2.50);
        $this->makePick($loserEntry, $leg, 'Home', 3.20);

        $this->service->settleFixture($fixture);

        $leg->refresh();
        $this->assertSame('won', $leg->status);
        $this->assertSame(['Away'], $leg->result_selections);

        $winnerEntry->refresh();
        $loserEntry->refresh();
        $this->assertSame(1, $winnerEntry->score_correct);
        +$this->assertSame(1, $winnerEntry->rank_current);
        +$this->assertSame(0, $loserEntry->score_correct);
        +$this->assertSame(2, $loserEntry->rank_current);

        $contest->refresh();
        $this->assertSame('settled', $contest->status);
        $this->assertNotNull($contest->settled_at);

        Notification::assertSentTo($winner, ContestSettledNotification::class);
        Notification::assertSentTo($loser, ContestSettledNotification::class);
    }

    public function test_resolves_leg_to_winning_selection_when_market_has_multiple_odds(): void
    {
        // Fixture: Away wins. Market 1 has all three odds: Home, Draw, Away.
        // Away's odd is the winner; the others are losers.
        $fixture = $this->makeFixture([
            'goals_home' => 0,
            'goals_away' => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');   // will lose
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Draw');   // will lose
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');   // will win

        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest($host);
        $leg = $this->makeLeg($contest, $fixture, self::MARKET_MATCH_WINNER);
        $entry = $this->makeEntry($contest, $player, order: 1);
        $this->makePick($entry, $leg, 'Away', 2.50);

        $this->service->settleFixture($fixture);

        $leg->refresh();
        $this->assertSame('won', $leg->status);
        $this->assertSame(['Away'], $leg->result_selections);

        $entry->refresh();
        $this->assertSame(1, $entry->score_correct);
    }

    public function test_contest_remains_open_until_all_legs_resolve(): void
    {
        $fixtureA = $this->makeFixture([
            'goals_home' => 0,
            'goals_away' => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);
        $this->makeOdd($fixtureA, self::MARKET_MATCH_WINNER, 'Away');

        $fixtureB = $this->makeFixture(['status_short' => 'NS']);

        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest($host);
        $legA = $this->makeLeg($contest, $fixtureA, self::MARKET_MATCH_WINNER);
        $legB = $this->makeLeg($contest, $fixtureB, self::MARKET_MATCH_WINNER);
        $entry = $this->makeEntry($contest, $player, order: 1);

        $this->makePick($entry, $legA, 'Away', 2.50);
        $this->makePick($entry, $legB, 'Away', 2.50);

        $this->service->settleFixture($fixtureA);

        $contest->refresh();
        $this->assertSame('open', $contest->status);
        $this->assertNull($contest->settled_at);

        $legA->refresh();
        $legB->refresh();
        $this->assertSame('won', $legA->status);
        $this->assertSame('pending', $legB->status);

        Notification::assertNothingSent();
    }

    public function test_void_leg_contributes_nothing_to_scores(): void
    {
        $fixture = $this->makeFixture(['status_short' => 'CANC', 'settled' => false]);
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest($host);
        $leg = $this->makeLeg($contest, $fixture, self::MARKET_MATCH_WINNER);
        $entry = $this->makeEntry($contest, $player, order: 1);
        $pick = $this->makePick($entry, $leg, 'Away', 2.50);

        $this->service->settleFixture($fixture);

        $leg->refresh();
        $this->assertSame('void', $leg->status);
        $this->assertSame([], $leg->result_selections);

        $pick->refresh();
        $this->assertSame('void', $pick->status);
        $this->assertSame('0.00', $pick->points);

        $entry->refresh();
        $this->assertSame(0, $entry->score_correct);
        $this->assertSame('0.00', $entry->score_units);

        $contest->refresh();
        $this->assertSame('settled', $contest->status);
    }

    public function test_settled_contest_does_not_notify_twice_on_second_run(): void
    {
        $fixture = $this->makeFixture([
            'goals_home' => 0,
            'goals_away' => 2,
            'halftime_home' => 0,
            'halftime_away' => 1,
            'fulltime_home' => 0,
            'fulltime_away' => 2,
        ]);
        $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $host = User::factory()->create();
        $player = User::factory()->create();

        $contest = $this->makeContest($host);
        $leg = $this->makeLeg($contest, $fixture, self::MARKET_MATCH_WINNER);
        $entry = $this->makeEntry($contest, $player, order: 1);
        $this->makePick($entry, $leg, 'Away', 2.50);

        $this->service->settleFixture($fixture);
        $this->service->settleFixture($fixture->fresh()); // no-op via settled guard

        Notification::assertSentToTimes($player, ContestSettledNotification::class, 1);
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
            'status_short' => 'FT',
            'status_long' => 'Match Finished',
            'home_team_id' => self::HOME_TEAM_ID,
            'away_team_id' => self::AWAY_TEAM_ID,
            'goals_home' => 0,
            'goals_away' => 0,
            'halftime_home' => 0,
            'halftime_away' => 0,
            'fulltime_home' => 0,
            'fulltime_away' => 0,
            'home_winner' => false,
            'settled' => false,
        ], $attributes));
    }

    private function makeOdd(
        Fixture $fixture,
        int $marketId,
        string $value,
        string $status = 'pending',
    ): Odd {
        $id = DB::table('odds')->insertGetId([
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'value' => $value,
            'odd' => 2.50,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }

    private function makeContest(User $host): Contest
    {
        return Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid' => (string) Str::uuid(),
            'host_id' => $host->id,
            'name' => 'Test Contest',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now(),
            'ends_at' => now()->addHours(2),
        ]));
    }

    private function makeLeg(Contest $contest, Fixture $fixture, int $marketId): ContestLeg
    {
        return ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'status' => 'pending',
        ]);
    }

    private function makeEntry(Contest $contest, User $user, int $order = 1): ContestEntry
    {
        return ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'joined_at' => now()->addSeconds($order),
        ]);
    }

    private function makePick(
        ContestEntry $entry,
        ContestLeg $leg,
        string $selection,
        float $odds,
    ): ContestPick {
        return ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id' => $leg->id,
            'selection' => $selection,
            'odds_at_pick' => $odds,
            'status' => 'pending',
        ]);
    }
}