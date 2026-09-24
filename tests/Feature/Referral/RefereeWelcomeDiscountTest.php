<?php

namespace Tests\Feature\Referral;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\User;
use App\Services\BetslipPurchaseService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class RefereeWelcomeDiscountTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.betslip_pirates.referral_defaults' => [
                'reward_percentage' => 0.10,
                'max_transactions' => 20,
                'window_months' => 12,
                'referee_discount_pct' => 0.20,
                'referee_discount_cap' => 20.00,
            ],
        ]);
    }

    public function test_plain_buyer_pays_listed_price(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        $betslip = $this->makeBetslip($seller, price: 100.00);

        app(BetslipPurchaseService::class)->purchase($buyer, $betslip);

        $purchase = BetslipUserPurchase::where('buyer_id', $buyer->id)->firstOrFail();
        $this->assertSame(100.00, (float) $purchase->purchase_price);

        // Buyer's wallet debited the full price.
        $this->assertSame(900.00, (float) $buyer->fresh()->wallet->balance);
    }

    public function test_referee_first_purchase_gets_discount(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$referee] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        $betslip = $this->makeBetslip($seller, price: 100.00);

        app(BetslipPurchaseService::class)->purchase($referee, $betslip);

        $purchase = BetslipUserPurchase::where('buyer_id', $referee->id)->firstOrFail();
        // 20% off 100 = 20 off, capped at 20 → pays 80.
        $this->assertSame(80.00, (float) $purchase->purchase_price);

        // Referee's wallet debited the discounted price only.
        $this->assertSame(920.00, (float) $referee->fresh()->wallet->balance);

        // Flag flips so the discount can't be reused.
        $this->assertTrue((bool) $referee->fresh()->welcome_discount_used);
    }

    public function test_discount_is_capped(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$referee] = $this->makeUserWithWallet(balance: 5000.00);
        [$seller] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        // 20% of 500 = 100, but cap is 20 → pays 480.
        $betslip = $this->makeBetslip($seller, price: 500.00);

        app(BetslipPurchaseService::class)->purchase($referee, $betslip);

        $purchase = BetslipUserPurchase::where('buyer_id', $referee->id)->firstOrFail();
        $this->assertSame(480.00, (float) $purchase->purchase_price);
    }

    public function test_discount_only_applies_to_first_purchase(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$referee] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        $first = $this->makeBetslip($seller, price: 100.00);
        $second = $this->makeBetslip($seller, price: 100.00);

        app(BetslipPurchaseService::class)->purchase($referee, $first);
        app(BetslipPurchaseService::class)->purchase($referee, $second);

        $purchases = BetslipUserPurchase::where('buyer_id', $referee->id)
            ->orderBy('created_at')
            ->get();

        $this->assertSame(80.00, (float) $purchases[0]->purchase_price);
        $this->assertSame(100.00, (float) $purchases[1]->purchase_price);
    }

    public function test_discount_does_not_apply_to_non_referee(): void
    {
        [$buyer] = $this->makeUserWithWallet(balance: 1000.00);
        [$seller] = $this->makeUserWithWallet();

        $betslip = $this->makeBetslip($seller, price: 100.00);

        app(BetslipPurchaseService::class)->purchase($buyer, $betslip);

        $purchase = BetslipUserPurchase::where('buyer_id', $buyer->id)->firstOrFail();
        $this->assertSame(100.00, (float) $purchase->purchase_price);
        $this->assertFalse((bool) $buyer->fresh()->welcome_discount_used);
    }

    private function makeBetslip(User $seller, float $price): Betslip
    {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 3.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 1,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        try {
            \Illuminate\Support\Facades\DB::table('betslip_odd')->insert([
                'betslip_id' => $betslip->id,
                'odd_id' => 900000,
                'status' => 'pending',
                'odd_value_at_time' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        return $betslip->fresh();
    }
}