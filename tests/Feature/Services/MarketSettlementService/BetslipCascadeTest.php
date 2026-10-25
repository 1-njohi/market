<?php

namespace Tests\Feature\Services\MarketSettlementService;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MarketSettlementService;
use App\Services\PlatformAccount;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BetslipCascadeTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;
    private const MARKET_MATCH_WINNER = 1;

    private static int $apiIdCounter = 800000;

    private MarketSettlementService $service;
    private WalletService $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketSettlementService::class);
        $this->wallet  = app(WalletService::class);

        // Deterministic platform user for fee routing.
        $platform = User::factory()->create(['name' => 'Platform']);

        $platformAccount = \Mockery::mock(PlatformAccount::class);
        $platformAccount->shouldReceive('user')->andReturn($platform);
        $this->app->instance(PlatformAccount::class, $platformAccount);
    }

    public function test_pays_seller_when_all_betslip_odds_win(): void
    {
        [$fixture, $odd] = $this->fixtureWithWinningOdd();   // Away wins 0-2

        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $betslip = $this->makeBetslip($seller, price: 100.00);
        $this->attachOddToBetslip($betslip, $odd);
        $this->makePurchase($betslip, $buyer, $seller, price: 100.00);
        $this->escrowForBuyer($buyer, 100.00);

        $this->service->settleFixture($fixture);

        $betslip->refresh();
        $this->assertSame('settled', $betslip->status);
        $this->assertTrue($betslip->is_winner);

        // Seller got paid: balance > 0
        $sellerBalance = (float) $this->wallet->getWallet($seller->fresh())->balance;
        $this->assertGreaterThan(0.0, $sellerBalance);

        // Buyer's escrow drained
        $buyerEscrow = (float) $this->wallet->getWallet($buyer->fresh())->escrow_balance;
        $this->assertSame(0.0, $buyerEscrow);
    }

    public function test_refunds_buyer_when_any_betslip_odd_loses(): void
    {
        // Fixture ends 0-2 (Away wins). We pick Home → loses.
        $fixture = $this->makeFixture([
            'goals_home' => 0, 'goals_away' => 2,
            'halftime_home' => 0, 'halftime_away' => 1,
            'fulltime_home' => 0, 'fulltime_away' => 2,
        ]);
        $losingOdd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');

        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $betslip = $this->makeBetslip($seller, price: 100.00);
        $this->attachOddToBetslip($betslip, $losingOdd);
        $this->makePurchase($betslip, $buyer, $seller, price: 100.00);
        $this->escrowForBuyer($buyer, 100.00);

        $this->service->settleFixture($fixture);

        $betslip->refresh();
        $this->assertSame('settled', $betslip->status);
        $this->assertFalse($betslip->is_winner);

        // Buyer refunded: available balance = original 100 (minus deposit noise)
        $buyerWallet = $this->wallet->getWallet($buyer->fresh());
        $this->assertSame(100.0, (float) $buyerWallet->balance);
        $this->assertSame(0.0, (float) $buyerWallet->escrow_balance);
    }

    public function test_refunds_buyer_and_marks_betslip_voided_when_all_odds_are_void(): void
    {
        $fixture = $this->makeFixture(['status_short' => 'CANC', 'settled' => false]);
        $oddA = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Home');
        $oddB = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $betslip = $this->makeBetslip($seller, price: 100.00);
        $this->attachOddToBetslip($betslip, $oddA);
        $this->attachOddToBetslip($betslip, $oddB);
        $this->makePurchase($betslip, $buyer, $seller, price: 100.00);
        $this->escrowForBuyer($buyer, 100.00);

        $this->service->settleFixture($fixture);

        $betslip->refresh();
        $this->assertSame('voided', $betslip->status);
        $this->assertFalse($betslip->is_winner);

        // Buyer refunded from escrow
        $buyerWallet = $this->wallet->getWallet($buyer->fresh());
        $this->assertSame(100.0, (float) $buyerWallet->balance);
        $this->assertSame(0.0, (float) $buyerWallet->escrow_balance);

        // Purchase row status = 'voided'
        $purchaseStatus = DB::table('betslip_user_purchases')
            ->where('betslip_id', $betslip->id)
            ->value('status');
        $this->assertSame('voided', $purchaseStatus);
    }

    public function test_marks_betslip_underway_when_some_odds_still_pending(): void
    {
        // Fixture A settles (Away wins), fixture B still NS.
        [$fixtureA, $winningOdd] = $this->fixtureWithWinningOdd();

        $fixtureB = $this->makeFixture([
            'status_short'  => 'NS',
            'goals_home'    => 0, 'goals_away' => 0,
            'halftime_home' => 0, 'halftime_away' => 0,
            'fulltime_home' => 0, 'fulltime_away' => 0,
        ]);
        $pendingOdd = $this->makeOdd($fixtureB, self::MARKET_MATCH_WINNER, 'Away', 'pending');

        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $betslip = $this->makeBetslip($seller, price: 100.00);
        $this->attachOddToBetslip($betslip, $winningOdd);
        $this->attachOddToBetslip($betslip, $pendingOdd);
        $this->makePurchase($betslip, $buyer, $seller, price: 100.00);
        $this->escrowForBuyer($buyer, 100.00);

        $this->service->settleFixture($fixtureA);

        $betslip->refresh();
        $this->assertSame('underway', $betslip->status);
        $this->assertFalse($betslip->is_winner);

        // Buyer still escrowed: no money moved yet
        $buyerWallet = $this->wallet->getWallet($buyer->fresh());
        $this->assertSame(100.0, (float) $buyerWallet->escrow_balance);
    }

    public function test_is_idempotent_and_does_not_double_pay_seller(): void
    {
        [$fixture, $odd] = $this->fixtureWithWinningOdd();

        $seller  = User::factory()->create();
        $buyer   = User::factory()->create();
        $betslip = $this->makeBetslip($seller, price: 100.00);
        $this->attachOddToBetslip($betslip, $odd);
        $this->makePurchase($betslip, $buyer, $seller, price: 100.00);
        $this->escrowForBuyer($buyer, 100.00);

        $this->service->settleFixture($fixture);
        $balanceAfterFirst = (float) $this->wallet->getWallet($seller->fresh())->balance;

        // Second call is a no-op (fixture.settled guard).
        $this->service->settleFixture($fixture->fresh());

        $balanceAfterSecond = (float) $this->wallet->getWallet($seller->fresh())->balance;
        $this->assertSame($balanceAfterFirst, $balanceAfterSecond);
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    /** @return array{0: Fixture, 1: Odd} */
    private function fixtureWithWinningOdd(): array
    {
        $fixture = $this->makeFixture([
            'goals_home'    => 0, 'goals_away' => 2,
            'halftime_home' => 0, 'halftime_away' => 1,
            'fulltime_home' => 0, 'fulltime_away' => 2,
        ]);
        $odd = $this->makeOdd($fixture, self::MARKET_MATCH_WINNER, 'Away');

        return [$fixture, $odd];
    }

    private function makeFixture(array $attributes = []): Fixture
    {
        return Fixture::forceCreate(array_merge([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'goals_home'    => 0, 'goals_away' => 0,
            'halftime_home' => 0, 'halftime_away' => 0,
            'fulltime_home' => 0, 'fulltime_away' => 0,
            'home_winner'   => false,
            'settled'       => false,
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
            'market_id'  => $marketId,
            'value'      => $value,
            'odd'        => 2.50,
            'status'     => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Odd::find($id);
    }

    private function makeBetslip(User $seller, float $price = 100.00, float $totalOdds = 2.50): Betslip
    {
        return Betslip::forceCreate([
            'user_id'        => $seller->id,
            'total_odds'     => $totalOdds,
            'price'          => $price,
            'status'         => 'pending',
            'remaining'      => 1,
            'is_winner'      => false,
            'code'           => 'TST-' . uniqid(),
            'priority_score' => 0,
            'caption'        => null,
        ]);
    }

    private function attachOddToBetslip(Betslip $betslip, Odd $odd): void
    {
        DB::table('betslip_odd')->insert([
            'betslip_id'        => $betslip->id,
            'odd_id'            => $odd->id,
            'status'            => 'pending',
            'odd_value_at_time' => 2.50,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    private function makePurchase(Betslip $betslip, User $buyer, User $seller, float $price): void
    {
        DB::table('betslip_user_purchases')->insert([
            'betslip_id'    => $betslip->id,
            'buyer_id'      => $buyer->id,
            'seller_id'     => $seller->id,
            'purchase_price' => $price,
            'total_odds'    => 2.50,
            'status'        => 'pending',
            'purchased_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /** Credit buyer's available, then hold into escrow — matches the real purchase flow. */
    private function escrowForBuyer(User $buyer, float $amount): void
    {
        $this->wallet->credit($buyer, $amount, Transaction::TYPE_DEPOSIT);
        $this->wallet->hold($buyer, $amount);
    }
}