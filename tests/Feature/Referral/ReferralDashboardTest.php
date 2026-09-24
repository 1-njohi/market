<?php

namespace Tests\Feature\Referral;

use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class ReferralDashboardTest extends TestCase
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

    public function test_dashboard_returns_empty_state_for_user_with_no_referees(): void
    {
        [$referrer] = $this->makeUserWithWallet();

        $dashboard = app(ReferralService::class)->dashboardFor($referrer);

        $this->assertSame($referrer->referral_code, $dashboard['code']);
        $this->assertStringContainsString($referrer->referral_code, $dashboard['share_url']);
        $this->assertSame(0, $dashboard['referee_count']);
        $this->assertSame(0.0, $dashboard['total_earned']);
        $this->assertSame(0, $dashboard['total_wins']);
        $this->assertSame([], $dashboard['referees']);
        $this->assertSame(0.10, $dashboard['terms']['reward_percentage']);
        $this->assertSame(20, $dashboard['terms']['max_transactions']);
        $this->assertSame(12, $dashboard['terms']['window_months']);
        $this->assertSame(0.20, $dashboard['terms']['referee_discount_pct']);
        $this->assertSame(20.00, $dashboard['terms']['referee_discount_cap']);
        $this->assertFalse($dashboard['terms']['is_override']);
    }

    public function test_dashboard_aggregates_across_referees(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$refereeA] = $this->makeUserWithWallet();
        [$refereeB] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($refereeA, $referrer->referral_code);
        app(ReferralService::class)->attribute($refereeB, $referrer->referral_code);

        // Simulate rewards landing.
        Referral::where('referee_id', $refereeA->id)->update([
            'wins_counted' => 3,
            'total_earned' => 30.00,
        ]);
        Referral::where('referee_id', $refereeB->id)->update([
            'wins_counted' => 1,
            'total_earned' => 12.50,
        ]);

        $dashboard = app(ReferralService::class)->dashboardFor($referrer);

        $this->assertSame(2, $dashboard['referee_count']);
        $this->assertSame(42.50, $dashboard['total_earned']);
        $this->assertSame(4, $dashboard['total_wins']);
        $this->assertCount(2, $dashboard['referees']);
    }

    public function test_dashboard_lists_referees_with_progress(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$referee] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        Referral::where('referee_id', $referee->id)->update([
            'wins_counted' => 5,
            'total_earned' => 50.00,
        ]);

        $dashboard = app(ReferralService::class)->dashboardFor($referrer);

        $row = $dashboard['referees'][0];

        $this->assertSame($referee->id, $row['referee_id']);
        $this->assertSame($referee->name, $row['referee_name']);
        $this->assertSame(5, $row['wins_counted']);
        $this->assertSame(50.00, $row['total_earned']);
        $this->assertFalse($row['is_expired']);
        $this->assertNotNull($row['expires_at']);
    }

    public function test_dashboard_marks_expired_referees(): void
    {
        [$referrer] = $this->makeUserWithWallet();
        [$referee] = $this->makeUserWithWallet();

        app(ReferralService::class)->attribute($referee, $referrer->referral_code);

        Referral::where('referee_id', $referee->id)->update([
            'expires_at' => now()->subDay(),
        ]);

        $dashboard = app(ReferralService::class)->dashboardFor($referrer);

        $this->assertTrue($dashboard['referees'][0]['is_expired']);
    }

    public function test_refer_page_renders_for_authenticated_user(): void
    {
        [$referrer] = $this->makeUserWithWallet();

        $response = $this->actingAs($referrer)->get('/refer');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Refer')
                ->has('dashboard')
                ->where('dashboard.code', $referrer->referral_code)
                ->where('dashboard.referee_count', 0)
        );
    }

    public function test_refer_page_redirects_unauthenticated_user(): void
    {
        $response = $this->get('/refer');

        $response->assertRedirect('/login');
    }
}