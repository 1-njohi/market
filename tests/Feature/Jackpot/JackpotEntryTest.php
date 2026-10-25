<?php

namespace Tests\Feature\Jackpot;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JackpotEntryTest extends TestCase
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
            'name' => 'Mega',
            'visibility' => 'public',
            'type' => 'jackpot',
            'status' => 'open',
            'locks_at' => now()->addDays(2),
            'entry_deadline_at' => now()->addDays(2),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(3),
        ], $overrides));
    }

    public function test_user_can_enter_open_jackpot(): void
    {
        $jp = $this->jackpot();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('jackpots.enter', $jp->uuid));

        $response->assertRedirect();
        $this->assertDatabaseHas('contest_entries', [
            'contest_id' => $jp->id,
            'user_id' => $user->id,
            'status' => 'accepted',
        ]);
    }

    public function test_entering_twice_returns_existing_entry(): void
    {
        $jp = $this->jackpot();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('jackpots.enter', $jp->uuid))->assertRedirect();
        $this->actingAs($user)->post(route('jackpots.enter', $jp->uuid))->assertRedirect();

        $this->assertSame(1, ContestEntry::where('contest_id', $jp->id)->where('user_id', $user->id)->count());
    }

    public function test_cannot_enter_after_lock(): void
    {
        $jp = $this->jackpot(['locks_at' => now()->subHour()]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('jackpots.enter', $jp->uuid));

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('contest_entries', ['contest_id' => $jp->id, 'user_id' => $user->id]);
    }

    public function test_guest_cannot_enter(): void
    {
        $jp = $this->jackpot();

        $this->post(route('jackpots.enter', $jp->uuid))->assertRedirect('/login');
    }
}