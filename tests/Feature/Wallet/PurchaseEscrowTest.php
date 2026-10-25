<?php

namespace Tests\Feature\Wallet;

use App\Models\Betslip;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BetslipPurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseEscrowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_moves_funds_from_buyer_available_to_buyer_escrow(): void
    {
        [$buyer, $buyerWallet] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet(balance: 0.00);
        $seller->update(['sellable_status' => 'sellable']);

        $betslip = $this->makeBetslip(seller: $seller, price: 200.00);

        app(BetslipPurchaseService::class)->purchase($buyer, $betslip);

        $buyerWallet->refresh();

        $this->assertSame(800.00, (float) $buyerWallet->balance);
        $this->assertSame(200.00, (float) $buyerWallet->escrow_balance);

        $sellerWallet = Wallet::where('user_id', $seller->id)->firstOrFail();
        $this->assertSame(0.00, (float) $sellerWallet->balance);
        $this->assertSame(0.00, (float) $sellerWallet->escrow_balance);
    }
    // -----------------------------------------------------------------
    // Helpers — inline for now, extract to factories once the shape
    // stabilises across several tests.
    // -----------------------------------------------------------------

    /**
     * @return array{0: User, 1: Wallet}
     */
    private function makeUserWithWallet(float $balance): array
    {
        $user = User::factory()->create();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => $balance,
            'escrow_balance' => 0,
            'total_deposited' => $balance,
            'total_withdrawn' => 0,
            'currency' => 'KES',
        ]);

        return [$user, $wallet];
    }

    private function makeBetslip(User $seller, float $price): Betslip
    {
        return Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 3,
            'code' => 'BS-TEST-' . uniqid(),
            'is_winner' => false,
        ]);
    }
}