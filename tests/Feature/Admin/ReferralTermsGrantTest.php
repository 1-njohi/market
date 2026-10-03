<?php

namespace Tests\Feature\Admin;

use App\Models\ReferralTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReferralTermsGrantTest extends TestCase
{
    use DatabaseMigrations;

    public function test_non_admin_cannot_grant_terms(): void
    {
        $user   = $this->makeUser('Not Admin');
        $target = $this->makeUser('Target');

        $response = $this->actingAs($user)
            ->post("/admin/users/{$target->id}/referral-terms", $this->validPayload());

        $response->assertStatus(403);
        $this->assertSame(0, ReferralTerm::count());
    }

    public function test_admin_can_grant_custom_terms(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser('Influencer');

        $response = $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/referral-terms", [
                'reward_percentage'    => 0.20,
                'max_transactions'     => 50,
                'window_months'        => 24,
                'referee_discount_pct' => 0.20,
                'referee_discount_cap' => 20.00,
                'notes'                => 'Telegram tipster partnership',
            ]);

        $response->assertRedirect();

        $term = ReferralTerm::where('user_id', $target->id)->first();
        $this->assertNotNull($term);
        $this->assertSame('0.2000', (string) $term->reward_percentage);
        $this->assertSame(50, $term->max_transactions);
        $this->assertSame(24, $term->window_months);
        $this->assertSame('0.2000', (string) $term->referee_discount_pct);
        $this->assertSame('20.00', (string) $term->referee_discount_cap);
        $this->assertSame('Telegram tipster partnership', $term->notes);
        $this->assertSame($admin->id, $term->granted_by);
        $this->assertNotNull($term->granted_at);
    }

    public function test_granting_replaces_existing_terms(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser('Influencer');

        ReferralTerm::create([
            'user_id'              => $target->id,
            'reward_percentage'    => 0.15,
            'max_transactions'     => 30,
            'window_months'        => 12,
            'referee_discount_pct' => 0.20,
            'referee_discount_cap' => 20.00,
            'granted_at'           => now()->subMonth(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/referral-terms", $this->validPayload([
                'reward_percentage' => 0.25,
            ]));

        $this->assertSame(1, ReferralTerm::where('user_id', $target->id)->count());
        $this->assertSame(
            '0.2500',
            (string) ReferralTerm::where('user_id', $target->id)->first()->reward_percentage
        );
    }

    public function test_admin_cannot_grant_themselves_terms(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/referral-terms", $this->validPayload());

        $response->assertSessionHasErrors();
        $this->assertSame(0, ReferralTerm::count());
    }

    public function test_percentage_is_validated_to_the_safe_range(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser('Influencer');

        // Above 25% is rejected — the reward cap protects the platform.
        $response = $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/referral-terms", $this->validPayload([
                'reward_percentage' => 0.50,
            ]));

        $response->assertSessionHasErrors('reward_percentage');
        $this->assertSame(0, ReferralTerm::count());
    }

    public function test_admin_can_revoke_custom_terms(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeUser('Influencer');

        ReferralTerm::create([
            'user_id'              => $target->id,
            'reward_percentage'    => 0.20,
            'max_transactions'     => 50,
            'window_months'        => 24,
            'referee_discount_pct' => 0.20,
            'referee_discount_cap' => 20.00,
            'granted_at'           => now(),
        ]);

        $response = $this->actingAs($admin)
            ->delete("/admin/users/{$target->id}/referral-terms");

        $response->assertRedirect();
        $this->assertSame(0, ReferralTerm::where('user_id', $target->id)->count());
    }

    // ------------------ helpers ------------------

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
        ]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'name'     => 'Admin',
            'code'     => strtoupper(\Illuminate\Support\Str::random(8)),
            'is_admin' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'reward_percentage'    => 0.20,
            'max_transactions'     => 50,
            'window_months'        => 24,
            'referee_discount_pct' => 0.20,
            'referee_discount_cap' => 20.00,
            'notes'                => 'Test grant',
        ], $overrides);
    }
}