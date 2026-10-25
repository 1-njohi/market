<?php

namespace Tests\Feature\Integration;

use App\Models\Betslip;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ContestSettledNotification;
use App\Services\PlatformAccount;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class FullPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_API_ID      = 1001;
    private const MARKET_MATCH_WINNER = 1;

    private const HOME_TEAM_ID = 33;
    private const AWAY_TEAM_ID = 34;

    private WalletService $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wallet = app(WalletService::class);

        $platform = User::factory()->create(['name' => 'Platform']);
        $mock = Mockery::mock(PlatformAccount::class);
        $mock->shouldReceive('user')->andReturn($platform);
        $this->app->instance(PlatformAccount::class, $mock);

        Notification::fake();

        $this->resetMockApi();
    }

    public function test_full_pipeline_from_sync_to_settlement(): void
    {
        // -------------------------------------------------------------
        // 1. SYNC fixtures from mock API into local DB.
        // -------------------------------------------------------------
        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $fixture = Fixture::where('id_on_api', self::FIXTURE_API_ID)->first();
        $this->assertNotNull($fixture, 'Fixture 1001 was not synced.');
        $this->assertSame('NS', $fixture->status_short);

        // The mock advances state (NS -> 1H -> ...) but not wall-clock time.
        // In production, a fixture going live is inherently "now" because
        // real time has passed since it was synced days earlier. Here we
        // compress the lifecycle into milliseconds, so we set the local
        // date to now to make it a candidate for fixtures:sync-live.
        Fixture::where('id_on_api', self::FIXTURE_API_ID)->update(['date' => now()]);
        $fixture->refresh();

        // -------------------------------------------------------------
        // 2. SYNC odds for the synced fixture.
        // -------------------------------------------------------------
        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $awayOdd = Odd::where('fixture_id', $fixture->id)
            ->where('market_id', self::MARKET_MATCH_WINNER)
            ->where('value', 'Away')
            ->first();
        $this->assertNotNull($awayOdd, 'Away odd was not synced.');

        // -------------------------------------------------------------
        // 3. USER ACTIVITY - betslip purchase + contest entry tied to that odd.
        // -------------------------------------------------------------
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $betslip = Betslip::forceCreate([
            'user_id'    => $seller->id,
            'total_odds' => 2.50,
            'price'      => 100.00,
            'status'     => 'pending',
            'remaining'  => 1,
            'is_winner'  => false,
            'code'       => 'E2E-' . Str::random(8),
        ]);

        DB::table('betslip_odd')->insert([
            'betslip_id'        => $betslip->id,
            'odd_id'            => $awayOdd->id,
            'status'            => 'pending',
            'odd_value_at_time' => 2.50,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('betslip_user_purchases')->insert([
            'betslip_id'     => $betslip->id,
            'buyer_id'       => $buyer->id,
            'seller_id'      => $seller->id,
            'purchase_price' => 100.00,
            'total_odds'     => 2.50,
            'status'         => 'pending',
            'purchased_at'   => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->wallet->credit($buyer, 100.00, Transaction::TYPE_DEPOSIT);
        $this->wallet->hold($buyer, 100.00);

        $host   = User::factory()->create();
        $player = User::factory()->create();

        $contest = Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid'              => (string) Str::uuid(),
            'host_id'           => $host->id,
            'name'              => 'E2E Contest',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at'         => now(),
            'ends_at'           => now()->addHours(2),
        ]));

        $leg = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => self::MARKET_MATCH_WINNER,
            'status'     => 'pending',
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id'    => $player->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Away',
            'odds_at_pick'     => 2.50,
            'status'           => 'pending',
        ]);

        // -------------------------------------------------------------
        // 4. ADVANCE through live states, syncing after each.
        // -------------------------------------------------------------
        $this->advanceMockApi();
        $this->artisan('fixtures:sync-live')->assertSuccessful();
        $this->assertSame('1H', $fixture->fresh()->status_short);

        $this->advanceMockApi();
        $this->artisan('fixtures:sync-live')->assertSuccessful();
        $this->assertSame('HT', $fixture->fresh()->status_short);

        $this->advanceMockApi();
        $this->artisan('fixtures:sync-live')->assertSuccessful();
        $this->assertSame('2H', $fixture->fresh()->status_short);

        // Force FT with a specific score: Away wins 0-2.
        $this->setMockFixtureScore(self::FIXTURE_API_ID, 0, 2);
        $this->artisan('fixtures:sync-live')->assertSuccessful();

        $fixture->refresh();
        $this->assertSame('FT', $fixture->status_short);
        $this->assertSame(0, $fixture->goals_home);
        $this->assertSame(2, $fixture->goals_away);

        // -------------------------------------------------------------
        // 5. SETTLE - one command, cascade through everything.
        // -------------------------------------------------------------
        $this->artisan('fixtures:settle')->assertSuccessful();

        // -------------------------------------------------------------
        // 6. VERIFY the cascade.
        // -------------------------------------------------------------
        $this->assertTrue($fixture->fresh()->settled);
        $this->assertSame('won', $awayOdd->fresh()->status);

        $betslip->refresh();
        $this->assertSame('settled', $betslip->status);
        $this->assertTrue($betslip->is_winner);

        $purchaseStatus = DB::table('betslip_user_purchases')
            ->where('betslip_id', $betslip->id)
            ->value('status');
        $this->assertSame('won', $purchaseStatus);

        $sellerWallet = $this->wallet->getWallet($seller->fresh());
        $this->assertGreaterThan(0.0, (float) $sellerWallet->balance);

        $buyerWallet = $this->wallet->getWallet($buyer->fresh());
        $this->assertSame(0.0, (float) $buyerWallet->escrow_balance);

        $contest->refresh();
        $this->assertSame('settled', $contest->status);

        $leg->refresh();
        $this->assertSame('won', $leg->status);
        $this->assertSame(['Away'], $leg->result_selections);

        $entry->refresh();
        $this->assertSame(1, $entry->score_correct);
        $this->assertSame(1, $entry->rank_current);

        Notification::assertSentTo($player, ContestSettledNotification::class);
    }

    public function test_probe_mock_odds_payload(): void
    {
        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        Fixture::where('id_on_api', self::FIXTURE_API_ID)->update(['date' => now()]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $fixture = Fixture::where('id_on_api', self::FIXTURE_API_ID)->first();

        $byMarket = Odd::where('fixture_id', $fixture->id)
            ->selectRaw('market_id, count(*) as n')
            ->groupBy('market_id')
            ->orderBy('market_id')
            ->get()
            ->map(fn ($r) => "{$r->market_id}={$r->n}")
            ->implode(' ');

        // fwrite(STDERR, "\n=== PROBE: odds by market_id ===\n{$byMarket}\n");

        $sample = Odd::where('fixture_id', $fixture->id)
            ->where('market_id', 12)
            ->pluck('value')
            ->implode(' | ');

        // fwrite(STDERR, "=== PROBE: market 12 values ===\n{$sample}\n");

        $this->assertTrue(true);
    }
    public function test_all_32_settleable_markets_are_synced(): void
    {
        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        Fixture::where('id_on_api', self::FIXTURE_API_ID)->update(['date' => now()]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $fixture = Fixture::where('id_on_api', self::FIXTURE_API_ID)->first();
        $this->assertNotNull($fixture);

        $distinctMarkets = DB::table('odds')
            ->where('fixture_id', $fixture->id)
            ->distinct()
            ->count('market_id');

        $this->assertSame(
            32,
            $distinctMarkets,
            'Expected exactly the 32 settleable markets to be written.',
        );

        $totalOdds = Odd::where('fixture_id', $fixture->id)->count();
        $this->assertSame(150, $totalOdds);

        // Spot-check the market IDs the sync must have accepted — includes
        // the sparse ones (5, 12, 24, 25) that were dropped before the filter.
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 1)->count() > 0 ? 1 : 0);
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 5)->count() > 0 ? 1 : 0);
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 12)->count() > 0 ? 1 : 0);
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 24)->count() > 0 ? 1 : 0);
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 25)->count() > 0 ? 1 : 0);
        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)->where('market_id', 46)->count() > 0 ? 1 : 0);

        // Market 4 is a real API-Football market but has no resolver — the
        // filter must drop it. The mock doesn't emit it, so we assert the
        // negative: nothing outside the settleable set is present.
        $unknownCount = DB::table('odds')
            ->where('fixture_id', $fixture->id)
            ->whereNotIn('market_id', \App\Services\MarketSettlementService::SETTLEABLE_MARKET_IDS)
            ->count();

        $this->assertSame(0, $unknownCount);
    }
    public function test_multi_market_settlement_flows_through_betslips_and_contests(): void
    {
        // -------------------------------------------------------------
        // 1. SYNC fixtures + odds from the mock.
        // -------------------------------------------------------------
        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        Fixture::where('id_on_api', self::FIXTURE_API_ID)->update(['date' => now()]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $fixture = Fixture::where('id_on_api', self::FIXTURE_API_ID)->firstOrFail();

        // -------------------------------------------------------------
        // 2. Set the fixture to a finished, scored state directly.
        //    We don't drive the mock's `advance` here because we need
        //    deterministic HT + FT values and goal events, which the
        //    mock's admin API doesn't fully parametrise.
        // -------------------------------------------------------------
        $fixture->update([
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'goals_home'    => 2,
            'goals_away'    => 1,
            'halftime_home' => 1,
            'halftime_away' => 0,
            'fulltime_home' => 2,
            'fulltime_away' => 1,
        ]);

        // Home scores first (20'), away equalises (55'), home scores last (80').
        $this->insertGoalEvent($fixture, self::HOME_TEAM_ID, 20);
        $this->insertGoalEvent($fixture, self::AWAY_TEAM_ID, 55);
        $this->insertGoalEvent($fixture, self::HOME_TEAM_ID, 80);

        $fixture->refresh();

        // -------------------------------------------------------------
        // 3. Reference the odds we'll be exercising.
        // -------------------------------------------------------------
        $homeWin       = $this->odd($fixture, 1, 'Home');
        $awayWin       = $this->odd($fixture, 1, 'Away');
        $over25        = $this->odd($fixture, 5, 'Over 2.5');
        $under25       = $this->odd($fixture, 5, 'Under 2.5');
        $bttsYes       = $this->odd($fixture, 8, 'Yes');
        $dcHomeDraw    = $this->odd($fixture, 12, 'Home/Draw');
        $dcHomeAway    = $this->odd($fixture, 12, 'Home/Away');
        $dcDrawAway    = $this->odd($fixture, 12, 'Draw/Away');
        $exact2_1      = $this->odd($fixture, 10, '2:1');
        $homeOver25    = $this->odd($fixture, 25, 'Home/Over 2.5');
        $homeUnder25   = $this->odd($fixture, 25, 'Home/Under 2.5');

        // -------------------------------------------------------------
        // 4. User activity.
        //
        //    Betslip A: winning DC leg (Home/Draw).
        //    Betslip B: losing DC leg (Draw/Away).
        //    Contest leg on market 12 (DC, multi-winner).
        //    Contest leg on market 1  (single-winner control).
        // -------------------------------------------------------------
        [$betslipA, $buyerA, $sellerA] = $this->makeBetslipWithOdd($dcHomeDraw);
        [$betslipB, $buyerB, $sellerB] = $this->makeBetslipWithOdd($dcDrawAway);

        $host   = User::factory()->create();
        $player = User::factory()->create();
        
        $contest = Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid'              => (string) Str::uuid(),
            'host_id'           => $host->id,
            'name'              => 'Multi-Market E2E',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at'         => now(),
            'ends_at'           => now()->addHours(2),
        ]));

        $dcLeg = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => 12,
            'status'     => 'pending',
        ]);
        $mwLeg = ContestLeg::forceCreate([
            'contest_id' => $contest->id,
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'status'     => 'pending',
        ]);

        $entry = ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id'    => $player->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        // Player picks the DC selection AND the market-1 winner — both should score.
        ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $dcLeg->id,
            'selection'        => 'Home/Draw',
            'odds_at_pick'     => 1.10,
            'status'           => 'pending',
        ]);
        ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $mwLeg->id,
            'selection'        => 'Home',
            'odds_at_pick'     => 1.80,
            'status'           => 'pending',
        ]);

        // -------------------------------------------------------------
        // 5. SETTLE.
        // -------------------------------------------------------------
        $this->artisan('fixtures:settle')->assertSuccessful();

        // -------------------------------------------------------------
        // 6. VERIFY odds settlement across several markets.
        // -------------------------------------------------------------
        $expectations = [
            [$homeWin,    'won',  'Match Winner / Home'],
            [$awayWin,    'lost', 'Match Winner / Away'],
            [$over25,     'won',  'O/U / Over 2.5'],
            [$under25,    'lost', 'O/U / Under 2.5'],
            [$bttsYes,    'won',  'BTTS / Yes'],
            [$dcHomeDraw, 'won',  'DC / Home/Draw'],
            [$dcHomeAway, 'won',  'DC / Home/Away'],
            [$dcDrawAway, 'lost', 'DC / Draw/Away'],
            [$exact2_1,   'won',  'Exact Score / 2:1'],
            [$homeOver25, 'won',  'Result/Total / Home/Over 2.5'],
            [$homeUnder25,'lost', 'Result/Total / Home/Under 2.5'],
        ];

        $failures = [];
        foreach ($expectations as [$odd, $expected, $label]) {
            $actual = $odd->fresh()->status;
            if ($actual !== $expected) {
                $failures[] = "{$label}: expected {$expected}, got {$actual}";
            }
        }
        $this->assertSame([], $failures, implode("\n", $failures));

        // -------------------------------------------------------------
        // 7. VERIFY betslip cascade.
        // -------------------------------------------------------------
        $betslipA->refresh();
        $this->assertSame('settled', $betslipA->status);
        $this->assertTrue($betslipA->is_winner, 'Betslip with winning DC selection should win.');

        $betslipB->refresh();
        $this->assertSame('settled', $betslipB->status);
        $this->assertFalse($betslipB->is_winner, 'Betslip with losing DC selection should lose.');

        // Seller A paid; Buyer B refunded from escrow.
        $sellerABalance = (float) $this->wallet->getWallet($sellerA->fresh())->balance;
        $this->assertGreaterThan(0.0, $sellerABalance);

        $buyerBWallet = $this->wallet->getWallet($buyerB->fresh());
        $this->assertSame(100.0, (float) $buyerBWallet->balance);
        $this->assertSame(0.0, (float) $buyerBWallet->escrow_balance);

        // -------------------------------------------------------------
        // 8. VERIFY contest cascade — the multi-winner payoff.
        // -------------------------------------------------------------
        $dcLeg->refresh();
        $this->assertSame('won', $dcLeg->status);
        $this->assertEqualsCanonicalizing(
            ['Home/Draw', 'Home/Away'],
            $dcLeg->result_selections,
            'DC leg must hold BOTH winning selections.',
        );

        $mwLeg->refresh();
        $this->assertSame('won', $mwLeg->status);
        $this->assertSame(['Home'], $mwLeg->result_selections);

        $entry->refresh();
        $this->assertSame(2, $entry->score_correct, 'Both the DC and Match Winner picks should score.');
        $this->assertSame(1, $entry->rank_current);

        $contest->refresh();
        $this->assertSame('settled', $contest->status);

        Notification::assertSentTo($player, ContestSettledNotification::class);
    }
    // ------------------------------------------------------------------
    // Mock admin helpers - real HTTP against the mock server.
    //
    // The mock server owns both the fake API-Sports routes (fixtures,
    // odds, ...) and its own admin routes (/reset, /advance, ...). It
    // lives at services.api_sports.base_url. In production that config
    // points at the real API-Sports, which has no admin routes - and we
    // never call them from production code.
    // ------------------------------------------------------------------

    private function mockAdminUrl(string $path): string
    {
        $base   = rtrim((string) config('services.api_sports.base_url'), '/');
        $secret = (string) config('services.mock_api.secret');

        return "{$base}/dev/mock-api/{$secret}{$path}";
    }

    private function resetMockApi(): void
    {
        $response = Http::post($this->mockAdminUrl('/reset'));

        $this->assertTrue(
            $response->successful(),
            "Mock reset failed: HTTP {$response->status()} - {$response->body()}"
        );
    }

    private function advanceMockApi(): void
    {
        $response = Http::post($this->mockAdminUrl('/advance'));

        $this->assertTrue(
            $response->successful(),
            "Mock advance failed: HTTP {$response->status()} - {$response->body()}"
        );
    }

    private function setMockFixtureScore(int $apiId, int $home, int $away): void
    {
        $response = Http::post($this->mockAdminUrl("/fixtures/{$apiId}/score"), [
            'home' => $home,
            'away' => $away,
        ]);

        $this->assertTrue(
            $response->successful(),
            "Mock score failed: HTTP {$response->status()} - {$response->body()}"
        );
    }

        /**
     * Build a pending betslip + attached odd + a purchase, with escrow held.
     *
     * @return array{0: Betslip, 1: User, 2: User}
     */
    private function makeBetslipWithOdd(Odd $odd): array
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $betslip = Betslip::forceCreate([
            'user_id'    => $seller->id,
            'total_odds' => (float) $odd->odd,
            'price'      => 100.00,
            'status'     => 'pending',
            'remaining'  => 1,
            'is_winner'  => false,
            'code'       => 'E2E-' . Str::random(8),
        ]);

        DB::table('betslip_odd')->insert([
            'betslip_id'        => $betslip->id,
            'odd_id'            => $odd->id,
            'status'            => 'pending',
            'odd_value_at_time' => (float) $odd->odd,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('betslip_user_purchases')->insert([
            'betslip_id'     => $betslip->id,
            'buyer_id'       => $buyer->id,
            'seller_id'      => $seller->id,
            'purchase_price' => 100.00,
            'total_odds'     => (float) $odd->odd,
            'status'         => 'pending',
            'purchased_at'   => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->wallet->credit($buyer, 100.00, Transaction::TYPE_DEPOSIT);
        $this->wallet->hold($buyer, 100.00);

        return [$betslip, $buyer, $seller];
    }

    /**
     * Look up an odd that the sync wrote.
     */
    private function odd(Fixture $fixture, int $marketId, string $value): Odd
    {
        return Odd::where('fixture_id', $fixture->id)
            ->where('market_id', $marketId)
            ->where('value', $value)
            ->firstOrFail();
    }

    /**
     * Insert a goal event so `home_scored_first` / `home_scored_last`
     * etc. resolve correctly. Uses the app's `fixture_events` table.
     */
    private function insertGoalEvent(Fixture $fixture, int $teamId, int $minute): void
    {
        DB::table('fixture_events')->insert([
            'fixture_id'   => $fixture->id,
            'team_id'      => $teamId,
            'type'         => 'Goal',
            'detail'       => 'Normal Goal',
            'time_elapsed' => $minute,
            'time_extra'   => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}