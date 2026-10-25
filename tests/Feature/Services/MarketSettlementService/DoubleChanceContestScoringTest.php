<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\User;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoubleChanceContestScoringTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;
    private const MARKET_DOUBLE_CHANCE = 12;

    private static int $apiIdCounter = 970000;

    private MarketSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketSettlementService::class);
    }

    public function test_both_correct_double_chance_picks_score_on_a_home_win(): void
    {
        // Home wins 2-0 → "Home/Draw" and "Home/Away" both win.
        $fixture = Fixture::forceCreate([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'goals_home'    => 2, 'goals_away' => 0,
            'halftime_home' => 1, 'halftime_away' => 0,
            'fulltime_home' => 2, 'fulltime_away' => 0,
            'home_winner'   => true,
            'settled'       => false,
        ]);

        $this->makeOdd($fixture, self::MARKET_DOUBLE_CHANCE, 'Home/Draw');
        $this->makeOdd($fixture, self::MARKET_DOUBLE_CHANCE, 'Home/Away');
        $this->makeOdd($fixture, self::MARKET_DOUBLE_CHANCE, 'Draw/Away');

        $host    = User::factory()->create();
        $p1      = User::factory()->create();
        $p2      = User::factory()->create();
        $p3      = User::factory()->create();

        $contest = $this->makeContest($host);
        $leg     = $this->makeLeg($contest, $fixture, self::MARKET_DOUBLE_CHANCE);

        $e1 = $this->makeEntry($contest, $p1, 1);
        $e2 = $this->makeEntry($contest, $p2, 2);
        $e3 = $this->makeEntry($contest, $p3, 3);

        $this->makePick($e1, $leg, 'Home/Draw', 1.10);
        $this->makePick($e2, $leg, 'Home/Away', 1.20);
        $this->makePick($e3, $leg, 'Draw/Away', 2.50);

        $this->service->settleFixture($fixture);

        $leg->refresh();
        $this->assertSame('won', $leg->status);
        $this->assertEqualsCanonicalizing(
            ['Home/Draw', 'Home/Away'],
            $leg->result_selections,
        );

        $e1->refresh();
        $e2->refresh();
        $e3->refresh();

        $this->assertSame(1, $e1->score_correct);
        $this->assertSame(1, $e2->score_correct);
        $this->assertSame(0, $e3->score_correct);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function makeOdd(Fixture $fixture, int $marketId, string $value): Odd
    {
        $id = DB::table('odds')->insertGetId([
            'fixture_id' => $fixture->id,
            'market_id'  => $marketId,
            'value'      => $value,
            'odd'        => 1.50,
            'status'     => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }

    private function makeContest(User $host): Contest
    {
        return Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid'              => (string) Str::uuid(),
            'host_id'           => $host->id,
            'name'              => 'DC Test',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at'         => now(),
            'ends_at'           => now()->addHours(2),
        ]));
    }

    private function makeLeg(Contest $contest, Fixture $fixture, int $marketId): ContestLeg
    {
        return ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => $marketId,
            'status'     => 'pending',
        ]);
    }

    private function makeEntry(Contest $contest, User $user, int $order): ContestEntry
    {
        return ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'accepted',
            'joined_at'  => now()->addSeconds($order),
        ]);
    }

    private function makePick(ContestEntry $entry, ContestLeg $leg, string $selection, float $odds): ContestPick
    {
        return ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => $selection,
            'odds_at_pick'     => $odds,
            'status'           => 'pending',
        ]);
    }
}