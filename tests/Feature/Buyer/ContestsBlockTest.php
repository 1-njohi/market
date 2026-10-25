<?php

namespace Tests\Feature\Buyer;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\BuyerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract for buyer_data.contests.
 *
 * The buyer dashboard shows ONLY contests the user entered as a player.
 * Contests the user hosts belong to the seller dashboard. This test
 * locks that split in.
 */
class ContestsBlockTest extends TestCase
{
    use RefreshDatabase;

    private function service(): BuyerDashboardService
    {
        return app(BuyerDashboardService::class);
    }

    private function makeContest(User $host, array $overrides = []): Contest
    {
        return Contest::withoutHostGuard(fn () => Contest::create(array_merge([
            'host_id'           => $host->id,
            'name'              => 'Test Contest ' . uniqid(),
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at'         => now()->addDay()->addHour(),
            'ends_at'           => now()->addDay()->addHours(2),
        ], $overrides)));
    }
    private function joinContest(User $user, Contest $contest, string $status = 'accepted'): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => $status,
            'joined_at'  => now(),
        ]);
    }

    public function test_dashboard_contests_block_is_array(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('contests', $data);
        $this->assertIsArray($data['contests']);
    }

    public function test_hosted_contest_is_excluded_from_buyer_dashboard(): void
    {
        $buyer = User::factory()->create();
        $this->makeContest($buyer); // buyer is the host

        $data = $this->service()->getDashboardData($buyer);

        $this->assertCount(0, $data['contests']);
    }

    public function test_entered_contest_is_included_in_buyer_dashboard(): void
    {
        $buyer = User::factory()->create();
        $host = User::factory()->create();
        $contest = $this->makeContest($host);

        $this->joinContest($buyer, $contest, 'accepted');

        $data = $this->service()->getDashboardData($buyer);

        $this->assertCount(1, $data['contests']);
        $this->assertSame('player', $data['contests'][0]['role']);
    }

    public function test_contest_row_exposes_required_keys(): void
    {
        $buyer = User::factory()->create();
        $host = User::factory()->create();
        $contest = $this->makeContest($host);

        $this->joinContest($buyer, $contest, 'accepted');

        $row = $this->service()->getDashboardData($buyer)['contests'][0];

        foreach (['id', 'uuid', 'name', 'role', 'legs_count', 'entry_deadline_at'] as $key) {
            $this->assertArrayHasKey($key, $row, "Missing key: {$key}");
        }
    }

    public function test_pending_entry_is_excluded(): void
    {
        $buyer = User::factory()->create();
        $host = User::factory()->create();
        $contest = $this->makeContest($host);

        $this->joinContest($buyer, $contest, 'pending');

        $data = $this->service()->getDashboardData($buyer);

        $this->assertCount(0, $data['contests']);
    }
}