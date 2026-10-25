<?php

namespace Tests\Feature\Jackpot;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JackpotDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });
    }

    private function jackpot(array $overrides = []): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create(array_merge([
            'host_id' => $host->id,
            'name' => 'Mega Jackpot',
            'visibility' => 'public',
            'type' => 'jackpot',
            'status' => 'open',
            'locks_at' => now()->addDays(2),
            'entry_deadline_at' => now()->addDays(2),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(3),
        ], $overrides));
    }

    public function test_guest_can_view_detail(): void
    {
        $jp = $this->jackpot();

        $response = $this->get(route('jackpots.show', $jp->uuid));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Jackpots/Show')
                ->where('jackpot.uuid', $jp->uuid)
                ->where('jackpot.is_entered', false)
        );
    }

    public function test_detail_requires_login_if_not_jackpot(): void
    {
        $host = User::factory()->create(['is_admin' => true]);
        $standard = Contest::create([
            'host_id' => $host->id,
            'name' => 'Std',
            'visibility' => 'public',
            'type' => 'standard',
            'status' => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(3),
        ]);

        $this->get(route('jackpots.show', $standard->uuid))->assertNotFound();
    }

    public function test_entrant_sees_is_entered_true(): void
    {
        $jp = $this->jackpot();
        $user = User::factory()->create();
        ContestEntry::create(['contest_id' => $jp->id, 'user_id' => $user->id, 'status' => 'accepted', 'joined_at' => now()]);

        $response = $this->actingAs($user)->get(route('jackpots.show', $jp->uuid));

        $response->assertInertia(
            fn($page) => $page
                ->where('jackpot.is_entered', true)
        );
    }
}