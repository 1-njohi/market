<?php

namespace Tests\Feature\Jackpot;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JackpotListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
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

    public function test_guest_can_view_jackpot_list(): void
    {
        $this->jackpot(['name' => 'Weekend Mega']);

        $response = $this->get(route('jackpots.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Jackpots/Index')
            ->has('jackpots.open', 1)
            ->where('jackpots.open.0.name', 'Weekend Mega')
        );
    }

    public function test_list_shows_only_jackpot_type(): void
    {
        $this->jackpot(['name' => 'Real Jackpot']);

        // A standard contest should not appear.
        $host = User::factory()->create(['is_admin' => true]);
        Contest::create([
            'host_id' => $host->id,
            'name' => 'Not a Jackpot',
            'visibility' => 'public',
            'type' => 'standard',
            'status' => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(3),
        ]);

        $response = $this->get(route('jackpots.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('jackpots.open', 1)
            ->where('jackpots.open.0.name', 'Real Jackpot')
        );
    }

    public function test_list_shows_participant_count(): void
    {
        $jp = $this->jackpot();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        ContestEntry::create(['contest_id' => $jp->id, 'user_id' => $u1->id, 'status' => 'accepted', 'joined_at' => now()]);
        ContestEntry::create(['contest_id' => $jp->id, 'user_id' => $u2->id, 'status' => 'accepted', 'joined_at' => now()]);

        $response = $this->get(route('jackpots.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('jackpots.open.0.participant_count', 2)
        );
    }
}