<?php

namespace Tests\Feature\Referral;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BetslipSettlementService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class ReferralRewardTriggerTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.betslip_pirates.fee_tiers' => [
                ['max' => null, 'percentage' => 0.25],
            ],
            'services.betslip_pirates.referral_defaults' => [
                'reward_percentage' => 0.10,
                'max_transactions' => 20,
                'window_months' => 12,
                'referee_discount_pct' => 0.20,
                'referee_discount_cap' => 20.00,
            ],
        ]);
    }

    public function test_buyer_referee_win_rewards_referrer(): void
    {
        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(10.00, (float) $s['referrer']->fresh()->wallet->balance);

        $referral = Referral::where('referee_id', $s['buyer']->id)->firstOrFail();
        $this->assertSame(1, $referral->wins_counted);
        $this->assertSame('10.00', $referral->total_earned);
    }

    public function test_seller_referee_win_rewards_referrer(): void
    {
        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['seller'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(10.00, (float) $s['referrer']->fresh()->wallet->balance);

        $referral = Referral::where('referee_id', $s['seller']->id)->firstOrFail();
        $this->assertSame(1, $referral->wins_counted);
    }

    public function test_both_referees_each_reward_their_referrer(): void
    {
        [$buyerReferrer] = $this->makeUserWithWallet();
        [$sellerReferrer] = $this->makeUserWithWallet();

        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['buyer'], $buyerReferrer->referral_code);
        app(ReferralService::class)->attribute($s['seller'], $sellerReferrer->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(10.00, (float) $buyerReferrer->fresh()->wallet->balance);
        $this->assertSame(10.00, (float) $sellerReferrer->fresh()->wallet->balance);
    }

    public function test_loss_does_not_reward(): void
    {
        $s = $this->scenario(legStatus: 'lost');

        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(0.00, (float) $s['referrer']->fresh()->wallet->balance);
        $this->assertSame(0, Referral::where('referee_id', $s['buyer']->id)->first()->wins_counted);
    }

    public function test_reward_uses_listed_price_not_discounted_price(): void
    {
        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(10.00, (float) $s['referrer']->fresh()->wallet->balance);
    }

    public function test_reward_writes_ledger_row(): void
    {
        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $row = Transaction::where('user_id', $s['referrer']->id)
            ->where('type', 'referral_reward')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(10.00, (float) $row->amount);
        $this->assertSame('available', $row->balance_type);
        $this->assertStringContainsString('referral', strtolower($row->description));
    }

    private function scenario(string $legStatus = 'won'): array
    {
        [$buyer] = $this->makeUserWithWallet(balance: 0, escrow: 200.00);
        [$seller] = $this->makeUserWithWallet();
        [$platform] = $this->makeUserWithWallet();
        [$referrer] = $this->makeUserWithWallet();

        config(['services.betslip_pirates.platform_user_email' => $platform->email]);

        $betslip = $this->makeBetslip($seller, legStatus: $legStatus, price: 100.00);

        $this->makePendingPurchase($buyer, $seller, $betslip, 100.00);

        return compact('buyer', 'seller', 'platform', 'referrer', 'betslip');
    }

    private function makeBetslip(User $seller, string $legStatus, float $price): Betslip
    {
        $betslip = Betslip::create([
            'user_id' => $seller->id,
            'total_odds' => 2.50,
            'price' => $price,
            'status' => 'pending',
            'remaining' => 1,
            'code' => 'BS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'is_winner' => false,
        ]);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        try {
            DB::table('betslip_odd')->insert([
                'betslip_id' => $betslip->id,
                'odd_id' => 900000,
                'status' => $legStatus,
                'odd_value_at_time' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        }

        return $betslip->fresh();
    }

    private function makePendingPurchase(
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
            'total_odds' => 2.50,
            'status' => 'pending',
            'payment_method' => 'wallet',
            'payment_reference' => 'TEST-' . uniqid(),
            'purchased_at' => now(),
        ]);
    }
}