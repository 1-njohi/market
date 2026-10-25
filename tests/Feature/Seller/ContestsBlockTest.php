<?php

namespace Tests\Feature\Seller;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\SellerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract for seller_data.contests.
 *
 * The seller dashboard shows ONLY contests the user is hosting.
 * Contests the user entered as a player belong to the buyer dashboard.
 */
class ContestsBlockTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SellerDashboardService
    {
        return app(SellerDashboardService::class);
    }

    private function makeContest(User $host, array $overrides = []): Contest
    {
        return Contest::withoutHostGuard(fn() => Contest::create(array_merge([
            'host_id' => $host->id,
            'name' => 'Test Contest ' . uniqid(),
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay()->addHour(),
            'ends_at' => now()->addDay()->addHours(2),
        ], $overrides)));
    }
    private function joinContest(User $user, Contest $contest, string $status = 'accepted'): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => $status,
            'joined_at' => now(),
        ]);
    }

    public function test_dashboard_contests_block_is_array(): void
    {
        $user = User::factory()->create();
        $data = $this->service()->getDashboardData($user);

        $this->assertArrayHasKey('contests', $data);
        $this->assertIsArray($data['contests']);
    }

    public function test_hosted_contest_is_included_in_seller_dashboard(): void
    {
        $seller = User::factory()->create();
        $this->makeContest($seller);

        $data = $this->service()->getDashboardData($seller);

        $this->assertCount(1, $data['contests']);
        $this->assertSame('host', $data['contests'][0]['role']);
    }

    public function test_entered_contest_is_excluded_from_seller_dashboard(): void
    {
        $seller = User::factory()->create();
        $host = User::factory()->create();
        $contest = $this->makeContest($host);

        $this->joinContest($seller, $contest, 'accepted');

        $data = $this->service()->getDashboardData($seller);

        $this->assertCount(0, $data['contests']);
    }

    public function test_contest_row_exposes_required_keys(): void
    {
        $seller = User::factory()->create();
        $this->makeContest($seller);

        $row = $this->service()->getDashboardData($seller)['contests'][0];

        foreach (['id', 'uuid', 'name', 'role', 'legs_count', 'entry_deadline_at', 'accepted_entries', 'pending_requests'] as $key) {
            $this->assertArrayHasKey($key, $row, "Missing key: {$key}");
        }
    }

    public function test_settled_contest_is_excluded(): void
    {
        $seller = User::factory()->create();
        $this->makeContest($seller, ['status' => 'settled']);

        $data = $this->service()->getDashboardData($seller);

        $this->assertCount(0, $data['contests']);
    }
}