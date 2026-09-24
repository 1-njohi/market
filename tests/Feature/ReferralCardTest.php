<?php

namespace Tests\Feature\Referral;

use App\Models\Referral;
use App\Services\BuyerDashboardService;
use App\Services\ReferralService;
use App\Services\SellerDashboardService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class ReferralCardTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;


    public function test_buyer_dashboard_shows_card_after_7_days(): void
    {
        [$buyer] = $this->makeUserWithWallet();
        $buyer->forceFill(['created_at' => now()->subDays(10)])->save();

        $data = app(BuyerDashboardService::class)->getDashboardData($buyer);

        $this->assertNotNull($data['referral_card']);
        $this->assertSame($buyer->referral_code, $data['referral_card']['code']);
        $this->assertStringContainsString(
            $buyer->referral_code,
            $data['referral_card']['share_url'],
        );
        $this->assertSame(0, $data['referral_card']['referee_count']);
        $this->assertSame(0.0, $data['referral_card']['total_earned']);
    }

    public function test_buyer_card_reflects_earnings_and_referee_count(): void
    {
        [$buyer] = $this->makeUserWithWallet();
        [$refereeA] = $this->makeUserWithWallet();
        [$refereeB] = $this->makeUserWithWallet();

        $buyer->forceFill(['created_at' => now()->subDays(10)])->save();

        app(ReferralService::class)->attribute($refereeA, $buyer->referral_code);
        app(ReferralService::class)->attribute($refereeB, $buyer->referral_code);

        Referral::where('referee_id', $refereeA->id)->update([
            'wins_counted' => 3,
            'total_earned' => 30.00,
        ]);
        Referral::where('referee_id', $refereeB->id)->update([
            'wins_counted' => 1,
            'total_earned' => 12.50,
        ]);

        $data = app(BuyerDashboardService::class)->getDashboardData($buyer);

        $this->assertSame(2, $data['referral_card']['referee_count']);
        $this->assertSame(42.50, $data['referral_card']['total_earned']);
        $this->assertSame(4, $data['referral_card']['total_wins']);
    }

    public function test_buyer_dashboard_shows_card_for_new_user_with_zero_stats(): void
    {
        [$buyer] = $this->makeUserWithWallet();

        $data = app(BuyerDashboardService::class)->getDashboardData($buyer);

        $this->assertNotNull($data['referral_card']);
        $this->assertSame($buyer->referral_code, $data['referral_card']['code']);
        $this->assertSame(0, $data['referral_card']['referee_count']);
        $this->assertSame(0.0, $data['referral_card']['total_earned']);
        $this->assertSame(0, $data['referral_card']['total_wins']);
    }
    public function test_seller_dashboard_shows_card_for_new_user_with_zero_stats(): void
    {
        [$seller] = $this->makeUserWithWallet();

        $data = app(SellerDashboardService::class)->getDashboardData($seller);

        $this->assertNotNull($data['referral_card']);
        $this->assertSame($seller->referral_code, $data['referral_card']['code']);
        $this->assertSame(0, $data['referral_card']['referee_count']);
    }

    public function test_seller_dashboard_shows_card_after_7_days(): void
    {
        [$seller] = $this->makeUserWithWallet();
        $seller->forceFill(['created_at' => now()->subDays(10)])->save();

        $data = app(SellerDashboardService::class)->getDashboardData($seller);

        $this->assertNotNull($data['referral_card']);
        $this->assertSame($seller->referral_code, $data['referral_card']['code']);
    }
}