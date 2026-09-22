<?php

namespace Tests\Feature\Dashboard;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Services\BuyerDashboardService;
use App\Services\SellerDashboardService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonInterface;
class WalletSummaryTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.betslip_pirates.fee_tiers' => [
                ['max' => null, 'percentage' => 0.10],
            ],
        ]);
    }

    public function test_buyer_wallet_summary_exposes_escrow_balance(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 800.00, escrow: 200.00);

        $summary = app(BuyerDashboardService::class)->getWalletSummary($buyer);

        $this->assertSame(800.00, (float) $summary['balance']);
        $this->assertSame(200.00, (float) $summary['escrow_balance']);
        $this->assertArrayNotHasKey('pending_balance', $summary);
    }

    public function test_seller_wallet_summary_reports_at_stake_projection(): void
    {
        [$seller] = $this->makeUserWithWallet(balance: 180.00);

        // Two pending sales at 200 + 300 = 500 gross at stake.
        $this->makePendingPurchase($seller, 200.00);
        $this->makePendingPurchase($seller, 300.00);

        $summary = app(SellerDashboardService::class)->getWalletSummary($seller);

        $this->assertSame(180.00, (float) $summary['balance']);
        $this->assertSame(500.00, (float) $summary['gross_at_stake']);
        $this->assertSame(450.00, (float) $summary['net_if_all_win']);
        $this->assertArrayNotHasKey('pending_balance', $summary);
    }
    public function test_buyer_transaction_list_shows_only_available_legs(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 0);

        // Simulate a purchase (two ledger rows: available and escrow).
        app(\App\Services\WalletService::class)->credit($buyer, 500.00, 'deposit', 'DEP-1', 'Seed');
        app(\App\Services\WalletService::class)->hold($buyer, 200.00, 'purchase', 'Buy');

        // Simulate a refund (two more ledger rows).
        app(\App\Services\WalletService::class)->refundEscrow($buyer, 200.00, 'refund', 'Refund');

        $summary = app(BuyerDashboardService::class)->getWalletSummary($buyer);
        $txs = collect($summary['recent_transactions']);

        // Deposit + purchase available leg + refund available leg = 3 rows.
        // Escrow legs (2 of them) must be filtered out.
        $this->assertCount(3, $txs);
        $this->assertTrue($txs->every(fn($t) => $t['type'] !== 'purchase' || $t['amount'] < 0));
    }

    public function test_buyer_refund_rate_uses_counts_not_amounts(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchaseOf($buyer, 100.00, 'refunded');
        $this->makeBuyerPurchaseOf($buyer, 200.00, 'won');

        $metrics = app(BuyerDashboardService::class)->getPerformanceMetrics($buyer);

        // 1 refunded / 2 purchases = 50%
        $this->assertSame(50.0, (float) $metrics['refund_rate']);
    }

    public function test_settled_outcomes_returns_won_refunded_and_voided(): void
    {
        [$buyer] = $this->makeUserWithWallet();
        [$seller] = $this->makeUserWithWallet();

        $this->makeSettledPurchaseFrom($buyer, $seller, 'won', now()->subMinutes(30));
        $this->makeSettledPurchaseFrom($buyer, $seller, 'refunded', now()->subMinutes(20));
        $this->makeSettledPurchaseFrom($buyer, $seller, 'voided', now()->subMinutes(10));

        $outcomes = app(BuyerDashboardService::class)->getSettledOutcomes($buyer);

        $this->assertCount(3, $outcomes);
        // Newest first — voided, refunded, won.
        $this->assertSame('voided', $outcomes[0]['outcome']);
        $this->assertSame('refunded', $outcomes[1]['outcome']);
        $this->assertSame('won', $outcomes[2]['outcome']);
    }

    public function test_transaction_rows_include_pivot_outcome(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        $betslip = $this->makeBetslipWithLegs($seller, ['won'], price: 200.00, remaining: 1);
        app(\App\Services\BetslipPurchaseService::class)->purchase($buyer, $betslip);

        $summary = app(BuyerDashboardService::class)->getWalletSummary($buyer);
        $purchaseRow = collect($summary['recent_transactions'])
            ->firstWhere('type', 'purchase');

        $this->assertNotNull($purchaseRow);
        $this->assertSame('pending', $purchaseRow['outcome']);
    }

    public function test_seller_settlements_shows_gross_fee_and_net(): void
    {
        [$seller] = $this->makeUserWithWallet();
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        // Build a pivot + settle it through the real settlement service so
        // the transactions are tagged exactly as they would be in production.
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 200.00,
            'status' => 'pending',
            'remaining' => 1,
            'code' => 'BS-' . uniqid(),
            'is_winner' => false,
        ]);

        $purchase = BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 200.00,
            'total_odds' => 3.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);

        // Simulate the win: settle via the wallet primitive, then tag rows
        // and update the pivot the same way BetslipSettlementService does.
        $txs = app(\App\Services\WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: 200.00,
            fee: 20.00,
            context: "Betslip #{$betslip->code}",
        );
        foreach ($txs as $tx) {
            $tx->update([
                'transactionable_type' => BetslipUserPurchase::class,
                'transactionable_id' => $purchase->id,
            ]);
        }
        $purchase->update(['status' => 'won', 'settled_at' => now()]);

        $settlements = app(SellerDashboardService::class)->getSettlements($seller);

        $this->assertCount(1, $settlements);
        $this->assertSame('won', $settlements[0]['outcome']);
        $this->assertSame(200.00, (float) $settlements[0]['gross']);
        $this->assertSame(20.00, (float) $settlements[0]['fee']);
        $this->assertSame(180.00, (float) $settlements[0]['net']);
        $this->assertSame($buyer->name, $settlements[0]['buyer_name']);
    }

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

    private function makeSettledPurchaseFrom(
        User $buyer,
        User $seller,
        string $status,
        CarbonInterface $settledAt,
    ): BetslipUserPurchase {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $status === 'won',
        ]);

        return BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 100.00,
            'total_odds' => 3.50,
            'status' => $status,
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => $settledAt->copy()->subHour(),
            'settled_at' => $settledAt,
        ]);
    }
    private function makePendingPurchase(User $seller, float $price): void
    {
        $buyer = User::factory()->create();

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . uniqid(),
            'is_winner' => false,
        ]);

        BetslipUserPurchase::create([
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

    public function test_seller_financial_summary_reflects_won_purchases(): void
    {
        [$seller] = $this->makeUserWithWallet(balance: 270.00);
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 400.00);
        [$platform] = $this->makeUserWithWallet();
        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        // Two wins at 200 and 100, each with a 10% fee (200*0.1 = 20, 100*0.1 = 10).
        // The ledger rows are what getFinancialSummary now reads.
        $this->settleWin($seller, $buyer, $platform, gross: 200.00, fee: 20.00);
        $this->settleWin($seller, $buyer, $platform, gross: 100.00, fee: 10.00);

        // A refunded purchase writes no seller-side rows; it must not affect revenue.
        $this->makeSettledPurchase($seller, $buyer, 50.00, 'refunded');

        $summary = app(SellerDashboardService::class)->getFinancialSummary($seller);

        $this->assertSame(300.00, (float) $summary['total_revenue']);
        $this->assertSame(30.00, (float) $summary['platform_fees']);
        $this->assertSame(270.00, (float) $summary['net_earnings']);
    }

    public function test_seller_quick_stats_counts_settled_purchases(): void
    {
        [$seller] = $this->makeUserWithWallet();
        [$buyer] = $this->makeUserWithWallet();

        $this->makeSettledPurchase($seller, $buyer, 200.00, 'won');
        $this->makeSettledPurchase($seller, $buyer, 100.00, 'refunded');

        $stats = app(SellerDashboardService::class)->getQuickStats($seller);

        $this->assertSame(2, $stats['total_sold']);
    }

    public function test_win_rate_ignores_voided_betslips(): void
    {
        [$seller] = $this->makeUserWithWallet();

        $this->makeSettledBetslip($seller, 'settled', isWinner: true);
        $this->makeSettledBetslip($seller, 'settled', isWinner: false);
        $this->makeSettledBetslip($seller, 'voided', isWinner: false);

        $metrics = app(SellerDashboardService::class)->getPerformanceMetrics($seller);

        // 1 win / 2 settled = 50%. If voided leaks into the denominator, this
        // reads 33.3% and the test catches it.
        $this->assertSame(50.0, (float) $metrics['win_rate']);
    }

    public function test_buyer_win_rate_excludes_voided_purchases(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchase($buyer, 'won');
        $this->makeBuyerPurchase($buyer, 'refunded');
        $this->makeBuyerPurchase($buyer, 'voided');

        $metrics = app(BuyerDashboardService::class)->getPerformanceMetrics($buyer);

        // 1 win / (1 win + 1 refunded) = 50%. Voided is a push and belongs
        // in neither numerator nor denominator.
        $this->assertSame(50.0, (float) $metrics['win_rate']);
    }

    public function test_buyer_status_distribution_counts_voided_separately(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchase($buyer, 'pending');
        $this->makeBuyerPurchase($buyer, 'won');
        $this->makeBuyerPurchase($buyer, 'refunded');
        $this->makeBuyerPurchase($buyer, 'voided');

        $charts = app(BuyerDashboardService::class)->getChartData($buyer);
        $byStatus = collect($charts['status_distribution'])->keyBy('status');

        $this->assertSame(1, $byStatus['Pending']['count']);
        $this->assertSame(1, $byStatus['Won']['count']);
        $this->assertSame(1, $byStatus['Refunded']['count']);
        $this->assertSame(1, $byStatus['Voided']['count']);
    }
    public function test_top_sellers_win_rate_excludes_voided_purchases(): void
    {
        [$buyer] = $this->makeUserWithWallet();
        [$seller] = $this->makeUserWithWallet();

        // Two purchases from the same seller: one win, one void.
        $this->makeBuyerPurchaseFrom($buyer, $seller, 'won');
        $this->makeBuyerPurchaseFrom($buyer, $seller, 'voided');

        $metrics = app(BuyerDashboardService::class)->getPerformanceMetrics($buyer);

        $topSeller = collect($metrics['top_sellers'])->firstWhere('seller_id', $seller->id);

        $this->assertNotNull($topSeller);
        // 1 win / 1 settled = 100%. If voided leaks in, this reads 50%.
        $this->assertSame(100.0, (float) $topSeller['win_rate']);
    }
    public function test_buyer_financial_summary_separates_spent_committed_and_escrowed(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchaseOf($buyer, 100.00, 'pending');
        $this->makeBuyerPurchaseOf($buyer, 200.00, 'won');
        $this->makeBuyerPurchaseOf($buyer, 300.00, 'refunded');
        $this->makeBuyerPurchaseOf($buyer, 400.00, 'voided');

        $summary = app(BuyerDashboardService::class)->getFinancialSummary($buyer);

        // Only 'won' is money that left the buyer for good.
        $this->assertSame(200.00, (float) $summary['total_spent']);

        // Won + pending = money that left available and didn't come back.
        $this->assertSame(300.00, (float) $summary['total_committed']);

        // Pending only — this is what's still reclaimable via refund.
        $this->assertSame(100.00, (float) $summary['escrowed']);

        // Refunded + voided = money that came back.
        $this->assertSame(700.00, (float) $summary['total_refunded']);
    }

    public function test_buyer_quick_stats_total_spent_excludes_pending_and_voided(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchaseOf($buyer, 100.00, 'pending');
        $this->makeBuyerPurchaseOf($buyer, 200.00, 'won');
        $this->makeBuyerPurchaseOf($buyer, 300.00, 'voided');

        $stats = app(BuyerDashboardService::class)->getQuickStats($buyer);

        $this->assertSame(200.00, (float) $stats['total_spent']);
        $this->assertSame(300.00, (float) $stats['total_committed']);
    }

    public function test_buyer_average_price_is_over_committed_purchases(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $this->makeBuyerPurchaseOf($buyer, 100.00, 'won');
        $this->makeBuyerPurchaseOf($buyer, 200.00, 'pending');
        $this->makeBuyerPurchaseOf($buyer, 900.00, 'refunded');
        $this->makeBuyerPurchaseOf($buyer, 900.00, 'voided');

        $metrics = app(BuyerDashboardService::class)->getPerformanceMetrics($buyer);

        // (100 + 200) / 2 = 150
        $this->assertSame(150.0, (float) $metrics['avg_price']);
    }

    public function test_buyer_dashboard_includes_watch_record(): void
    {
        [$buyer] = $this->makeUserWithWallet();
        [$seller] = $this->makeUserWithWallet();

        $this->watchSettled($buyer, $seller, 'settled', true, 2.00, 'BS-W1');
        $this->watchSettled($buyer, $seller, 'settled', true, 3.00, 'BS-W2');
        $this->watchSettled($buyer, $seller, 'settled', false, 1.50, 'BS-L1');

        $data = app(BuyerDashboardService::class)->getDashboardData($buyer);

        $this->assertArrayHasKey('watch_record', $data);
        $this->assertSame(3, $data['watch_record']['settled_count']);
        $this->assertSame(2, $data['watch_record']['won_count']);
        $this->assertSame(2.0, (float) $data['watch_record']['units']);
    }

    public function test_seller_active_betslips_carry_watch_counts(): void
    {
        [$seller] = $this->makeUserWithWallet();
        $buyer1 = $this->makeUser();
        $buyer2 = $this->makeUser();

        $betslipA = $this->makePendingSellerBetslip($seller, 'BS-A');
        $betslipB = $this->makePendingSellerBetslip($seller, 'BS-B');

        // A: 2 watchers. B: 1 watcher.
        $betslipA->watchers()->attach($buyer1->id, ['watched_at' => now()]);
        $betslipA->watchers()->attach($buyer2->id, ['watched_at' => now()]);
        $betslipB->watchers()->attach($buyer1->id, ['watched_at' => now()]);

        $data = app(SellerDashboardService::class)->getDashboardData($seller);

        $active = collect($data['betslips']['active']);
        $a = $active->firstWhere('code', 'BS-A');
        $b = $active->firstWhere('code', 'BS-B');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertSame(2, $a['watch_count']);
        $this->assertSame(1, $b['watch_count']);

        // Aggregate at the betslips level.
        $this->assertSame(3, $data['betslips']['total_watchers']);
    }

    private function makePendingSellerBetslip(User $seller, string $code): \App\Models\Betslip
    {
        return \App\Models\Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'pending',
            'remaining' => 1,
            'code' => $code,
            'is_winner' => false,
        ]);
    }
    private function makeBuyerPurchase(User $buyer, string $status): void
    {
        $seller = User::factory()->create();

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $status === 'won',
        ]);

        BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 100.00,
            'total_odds' => 3.50,
            'status' => $status,
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }
    private function makeSettledPurchase(User $seller, User $buyer, float $price, string $status): void
    {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $status === 'won',
        ]);

        BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => $price,
            'total_odds' => 3.50,
            'status' => $status,
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }

    private function makeSettledBetslip(
        User $seller,
        string $status,
        bool $isWinner
    ): Betslip {
        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $isWinner,
        ]);
    }

    private function makeBuyerPurchaseFrom(User $buyer, User $seller, string $status): void
    {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $status === 'won',
        ]);

        BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => 100.00,
            'total_odds' => 3.50,
            'status' => $status,
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }
    private function makeBuyerPurchaseOf(User $buyer, float $price, string $status): void
    {
        $seller = User::factory()->create();

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => $status === 'won',
        ]);

        BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => $price,
            'total_odds' => 3.50,
            'status' => $status,
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }

    private function settleWin(
        User $seller,
        User $buyer,
        User $platform,
        float $gross,
        float $fee,
        ?float $takeFromEscrow = null,
        ?string $forceStatus = null,
    ): BetslipUserPurchase {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $gross,
            'status' => 'settled',
            'remaining' => 0,
            'code' => 'BS-' . uniqid(),
            'is_winner' => true,
        ]);

        $purchase = BetslipUserPurchase::create([
            'betslip_id' => $betslip->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'purchase_price' => $gross,
            'total_odds' => 3.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);

        $txs = app(\App\Services\WalletService::class)->settleEscrowToSeller(
            buyer: $buyer,
            seller: $seller,
            platform: $platform,
            gross: $gross,
            fee: $fee,
            context: "Betslip #{$betslip->code}",
        );

        foreach ($txs as $tx) {
            $tx->update([
                'transactionable_type' => BetslipUserPurchase::class,
                'transactionable_id' => $purchase->id,
            ]);
        }

        $purchase->update(['status' => 'won', 'settled_at' => now()]);

        return $purchase;
    }
    private function makeUser(): User
    {
        return User::factory()->create([
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }


    private function watchSettled(
        User $buyer,
        User $seller,
        string $status,
        bool $isWinner,
        float $totalOdds,
        string $code,
    ): void {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => $totalOdds,
            'price' => 100.00,
            'status' => $status,
            'remaining' => 0,
            'code' => $code,
            'is_winner' => $isWinner,
        ]);

        $buyer->watchedBetslips()->attach($betslip->id, [
            'watched_at' => now(),
        ]);
    }
}