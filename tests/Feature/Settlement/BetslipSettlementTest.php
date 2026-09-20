<?php

namespace Tests\Feature\Settlement;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Services\BetslipSettlementService;
use App\Services\SellerDashboardService;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class BetslipSettlementTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Flat 10% fee so assertions are predictable regardless of tiers.
        config([
            'services.betslip_pirates.fee_tiers' => [
                ['max' => null, 'percentage' => 0.10],
            ],
        ]);
    }

    public function test_win_settlement_pays_seller_gross_and_credits_platform_fee(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(BetslipSettlementService::class)->settle($betslip);

        $this->assertSame(0.00, (float) Wallet::where('user_id', $buyer->id)->firstOrFail()->escrow_balance);
        $this->assertSame(180.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(20.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);

        $this->assertSame('won', BetslipUserPurchase::where('betslip_id', $betslip->id)->firstOrFail()->status);
        $this->assertSame('settled', $betslip->fresh()->status);
    }

    public function test_loss_settlement_refunds_buyer_and_leaves_seller_untouched(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['lost'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(BetslipSettlementService::class)->settle($betslip);

        $buyerWallet = Wallet::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame(200.00, (float) $buyerWallet->balance);
        $this->assertSame(0.00, (float) $buyerWallet->escrow_balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);

        $this->assertSame('refunded', BetslipUserPurchase::where('betslip_id', $betslip->id)->firstOrFail()->status);
    }

    public function test_settlement_is_idempotent(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        $service = app(BetslipSettlementService::class);
        $service->settle($betslip);

        // Second call must not move any more money, regardless of whether
        // it returns silently or throws. The current guard is broken (checks
        // for 'won'/'voided' but writes 'settled'), so it may fail here until
        // we fix it as part of the rewrite.
        try {
            $service->settle($betslip->fresh());
        } catch (\Throwable) {
            // Swallow — the assertion below is what matters.
        }

        $this->assertSame(180.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(20.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);
    }

    public function test_all_void_betslip_refunds_buyer_with_voided_pivot_status(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['void', 'void'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(BetslipSettlementService::class)->settle($betslip);

        $buyerWallet = Wallet::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame(200.00, (float) $buyerWallet->balance);
        $this->assertSame(0.00, (float) $buyerWallet->escrow_balance);

        $this->assertSame(0.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);

        $this->assertSame('voided', BetslipUserPurchase::where('betslip_id', $betslip->id)->firstOrFail()->status);
        $this->assertSame('voided', $betslip->fresh()->status);
    }

    public function test_mixed_win_and_void_betslip_pays_seller(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        // One leg won, one voided, none lost → winner.
        $betslip = $this->makeBetslipWithLegs($seller, ['won', 'void'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(BetslipSettlementService::class)->settle($betslip);

        $this->assertSame(180.00, (float) Wallet::where('user_id', $seller->id)->firstOrFail()->balance);
        $this->assertSame(20.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);
        $this->assertSame(0.00, (float) Wallet::where('user_id', $buyer->id)->firstOrFail()->escrow_balance);

        $this->assertSame('won', BetslipUserPurchase::where('betslip_id', $betslip->id)->firstOrFail()->status);
    }
    public function test_platform_account_is_cached_across_settlements(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 300.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();

        app(\App\Services\PlatformAccount::class)->setUser($platform);

        // Two settlements. Then we change config to a bogus email. If the
        // platform user is re-resolved from config on each settlement, the
        // second one throws. If it's cached, both succeed.
        for ($i = 0; $i < 2; $i++) {
            $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 100.00);
            $this->makePurchase($buyer, $seller, $betslip, 100.00);
            app(BetslipSettlementService::class)->settle($betslip);
        }

        config(['services.betslip_pirates.platform_user_email' => 'nobody@example.com']);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 100.00);
        $this->makePurchase($buyer, $seller, $betslip, 100.00);
        app(BetslipSettlementService::class)->settle($betslip);

        // All three settlements paid out. Platform received 3 × 10% of 100.
        $this->assertSame(30.00, (float) Wallet::where('user_id', $platform->id)->firstOrFail()->balance);
    }

    public function test_platform_account_throws_a_clear_error_when_config_is_missing(): void
    {
        config(['services.betslip_pirates.platform_user_email' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/platform user/i');

        app(\App\Services\PlatformAccount::class)->user();
    }

    public function test_platform_account_throws_when_configured_email_does_not_exist(): void
    {
        config(['services.betslip_pirates.platform_user_email' => 'ghost@example.com']);

        $this->expectException(\RuntimeException::class);

        app(\App\Services\PlatformAccount::class)->user();
    }

    public function test_settlement_invalidates_buyer_and_seller_dashboard_cache(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00);
        $this->makePurchase($buyer, $seller, $betslip, 200.00);

        // Seed the cache with sentinel values so we can prove they're evicted.
        $buyerKey = \App\Http\Controllers\DashboardController::cacheKey($buyer);
        $sellerKey = \App\Http\Controllers\DashboardController::cacheKey($seller);
        \Cache::put($buyerKey, 'stale-buyer-data', 60);
        \Cache::put($sellerKey, 'stale-seller-data', 60);

        $this->assertTrue(\Cache::has($buyerKey));
        $this->assertTrue(\Cache::has($sellerKey));

        app(BetslipSettlementService::class)->settle($betslip);

        $this->assertFalse(\Cache::has($buyerKey), 'Buyer dashboard cache should be evicted.');
        $this->assertFalse(\Cache::has($sellerKey), 'Seller dashboard cache should be evicted.');
    }
    public function test_end_to_end_deposit_purchase_settle_and_withdraw(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00);

        // 1. Buyer purchases. Pivot is created by the purchase service.
        app(\App\Services\BetslipPurchaseService::class)->purchase($buyer, $betslip);

        $buyerWallet = Wallet::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame(800.00, (float) $buyerWallet->balance);
        $this->assertSame(200.00, (float) $buyerWallet->escrow_balance);

        // 2. Settle as a win.
        app(\App\Services\BetslipSettlementService::class)->settle($betslip);

        $buyerWallet->refresh();
        $sellerWallet = Wallet::where('user_id', $seller->id)->firstOrFail();
        $platformWallet = Wallet::where('user_id', $platform->id)->firstOrFail();

        $this->assertSame(800.00, (float) $buyerWallet->balance);
        $this->assertSame(0.00, (float) $buyerWallet->escrow_balance);
        $this->assertSame(180.00, (float) $sellerWallet->balance);
        $this->assertSame(20.00, (float) $platformWallet->balance);

        // 3. Seller withdraws.
        app(\App\Services\WalletService::class)->debit(
            $seller,
            180.00,
            \App\Models\Transaction::TYPE_WITHDRAWAL,
            'WD-E2E-001',
            'Seller withdrawal'
        );

        $sellerWallet->refresh();
        $this->assertSame(0.00, (float) $sellerWallet->balance);
        $this->assertSame(180.00, (float) $sellerWallet->total_withdrawn);
    }

    public function test_purchase_transaction_is_linked_to_pivot(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        $betslip = $this->makeBetslipWithLegs($seller, ['pending'], price: 200.00, remaining: 1);

        $purchase = app(\App\Services\BetslipPurchaseService::class)->purchase($buyer, $betslip);

        // The visible (available) leg should be tagged with the pivot.
        $availableTx = \App\Models\Transaction::where('user_id', $buyer->id)
            ->where('balance_type', 'available')
            ->where('type', 'purchase')
            ->firstOrFail();

        $this->assertSame(BetslipUserPurchase::class, $availableTx->transactionable_type);
        $this->assertSame($purchase->id, $availableTx->transactionable_id);

        // The escrow leg should NOT be tagged — it's internal.
        $escrowTx = \App\Models\Transaction::where('user_id', $buyer->id)
            ->where('balance_type', 'escrow')
            ->where('type', 'purchase')
            ->firstOrFail();

        $this->assertNull($escrowTx->transactionable_type);
        $this->assertNull($escrowTx->transactionable_id);
    }

    public function test_settlement_sets_settled_at_and_tags_all_money_rows(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00, remaining: 0);
        $purchase = $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(\App\Services\BetslipSettlementService::class)->settle($betslip);

        $purchase->refresh();

        $this->assertSame('won', $purchase->status);
        $this->assertNotNull($purchase->settled_at);

        // Every row written during settlement should be tagged with the pivot.
        $tagged = \App\Models\Transaction::where('transactionable_type', BetslipUserPurchase::class)
            ->where('transactionable_id', $purchase->id)
            ->count();

        // Buyer escrow leg + seller gross + seller fee + platform fee = 4.
        $this->assertSame(4, $tagged);
    }

    public function test_voided_settlement_sets_settled_at_with_voided_status(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslipWithLegs($seller, ['void'], price: 200.00, remaining: 0);
        $purchase = $this->makePurchase($buyer, $seller, $betslip, 200.00);

        app(BetslipSettlementService::class)->settle($betslip);

        $purchase->refresh();

        $this->assertSame('voided', $purchase->status);
        $this->assertNotNull($purchase->settled_at);
    }
    private function makePurchase(
        User $buyer,
        User $seller,
        Betslip $betslip,
        float $price,
    ): BetslipUserPurchase {
        return BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => $price,
            'total_odds' => 3.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }

    // -----------------------------------------------------------------

    /**
     * @param  string[]  $legStatuses  e.g. ['won'] or ['lost'] or ['void', 'won']
     */
    private function makeBetslipWithLegs(
        User $seller,
        array $legStatuses,
        float $price,
        int $remaining = 1,
    ): Betslip {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => $remaining,
            'code' => 'BS-TEST-' . uniqid(),
            'is_winner' => false,
        ]);

        // The odds table has required columns we don't know about yet; skip
        // creating real rows and turn off FK enforcement just for this insert.
        // Replace with real Odd::create() calls once the factory exists.
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        try {
            foreach ($legStatuses as $i => $status) {
                DB::table('betslip_odd')->insert([
                    'betslip_id' => $betslip->id,
                    'odd_id' => 900000 + $i,
                    'status' => $status,
                    'odd_value_at_time' => 1.50,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        return $betslip->fresh();
    }
}