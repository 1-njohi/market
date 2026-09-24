<?php

namespace Tests\Feature\Referral;

use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Referral;
use App\Models\ReferralTerm;
use App\Models\User;
use App\Services\BetslipSettlementService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class ReferralRewardGuardrailsTest extends TestCase
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

    public function test_reward_skips_when_cap_is_reached(): void
    {
        $s = $this->scenario();
        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        // Pre-fill the counter to the cap.
        Referral::where('referee_id', $s['buyer']->id)->update([
            'wins_counted' => 20,
        ]);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(0.00, (float) $s['referrer']->fresh()->wallet->balance);

        $referral = Referral::where('referee_id', $s['buyer']->id)->firstOrFail();
        $this->assertSame(20, $referral->wins_counted);
    }

    public function test_reward_skips_when_referral_expired(): void
    {
        $s = $this->scenario();
        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        Referral::where('referee_id', $s['buyer']->id)->update([
            'expires_at' => now()->subDay(),
        ]);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(0.00, (float) $s['referrer']->fresh()->wallet->balance);
    }

    public function test_one_expired_referral_does_not_block_the_other(): void
    {
        [$buyerReferrer] = $this->makeUserWithWallet();
        [$sellerReferrer] = $this->makeUserWithWallet();

        $s = $this->scenario();

        app(ReferralService::class)->attribute($s['buyer'], $buyerReferrer->referral_code);
        app(ReferralService::class)->attribute($s['seller'], $sellerReferrer->referral_code);

        // Expire only the buyer's referral.
        Referral::where('referee_id', $s['buyer']->id)->update([
            'expires_at' => now()->subDay(),
        ]);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $this->assertSame(0.00, (float) $buyerReferrer->fresh()->wallet->balance);
        $this->assertSame(10.00, (float) $sellerReferrer->fresh()->wallet->balance);
    }

    public function test_referrer_receives_notification_on_reward(): void
    {
        $s = $this->scenario();
        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $row = DB::table('notifications')
            ->where('notifiable_id', $s['referrer']->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%"type":"referral_reward"%')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode($row->data, true);
        $this->assertSame('referral_reward', $data['type']);
        $this->assertSame(10.0, (float) $data['amount']);
        $this->assertStringContainsString($s['buyer']->name, $data['body']);
        $this->assertStringContainsString($s['betslip']->code, $data['body']);
    }

    public function test_referrer_gets_no_notification_when_no_reward_fires(): void
    {
        $s = $this->scenario(legStatus: 'lost');
        app(ReferralService::class)->attribute($s['buyer'], $s['referrer']->referral_code);

        app(BetslipSettlementService::class)->settle($s['betslip']);

        $count = DB::table('notifications')
            ->where('notifiable_id', $s['referrer']->id)
            ->where('data', 'like', '%"type":"referral_reward"%')
            ->count();

        $this->assertSame(0, $count);
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