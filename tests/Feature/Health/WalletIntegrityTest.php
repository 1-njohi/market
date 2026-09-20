<?php

namespace Tests\Feature\Health;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SystemHealthService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class WalletIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesWalletUsers;

    public function test_ledger_and_escrow_integrity_pass_on_a_clean_system(): void
    {
        [$buyer, $buyerWallet] = $this->makeUserWithWallet(balance: 1000.00);

        // Simulate a deposit and a purchase so there are real ledger rows.
        app(WalletService::class)->credit($buyer, 1000.00, 'deposit', 'DEP-1', 'Seed');
        app(WalletService::class)->hold($buyer, 200.00, 'purchase', 'Purchase');

        // The purchase needs a matching pending pivot row for escrow
        // integrity to line up.
        $this->makePendingPurchase($buyer, 200.00);

        $health = app(SystemHealthService::class);

        $ledger = $health->walletLedgerIntegrity();
        $this->assertSame('ok', $ledger['status'], 'Ledger integrity should pass.');

        $escrow = $health->escrowIntegrity();
        $this->assertSame('ok', $escrow['status'], 'Escrow integrity should pass.');
    }

    public function test_escrow_integrity_fails_when_escrow_and_pending_purchases_drift(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);

        // No matching pending purchase — escrow is held against nothing.
        $health = app(SystemHealthService::class);
        $escrow = $health->escrowIntegrity();

        $this->assertSame('fail', $escrow['status']);
    }

    private function makePendingPurchase(User $buyer, float $price): BetslipUserPurchase
    {
        $seller = User::factory()->create();

        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-' . uniqid(),
            'is_winner' => false,
        ]);

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
}