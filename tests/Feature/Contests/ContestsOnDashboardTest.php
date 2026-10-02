<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Services\BuyerDashboardService;
use App\Services\SellerDashboardService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Concerns\CreatesWalletUsers;
use Tests\TestCase;

class ContestsOnDashboardTest extends TestCase
{
    use DatabaseMigrations;
    use CreatesWalletUsers;

    public function test_hosted_contests_appear_on_buyer_dashboard(): void
    {
        [$user] = $this->makeUserWithWallet();
        $contest = $this->makeContest($user, name: 'Sunday Crew');

        $data = app(BuyerDashboardService::class)->getDashboardData($user);

        $this->assertArrayHasKey('contests', $data);
        $this->assertCount(1, $data['contests']);
        $this->assertSame('Sunday Crew', $data['contests'][0]['name']);
        $this->assertSame('host', $data['contests'][0]['role']);
        $this->assertSame($contest->uuid, $data['contests'][0]['uuid']);
    }

    public function test_accepted_contests_appear_on_buyer_dashboard(): void
    {
        [$host] = $this->makeUserWithWallet();
        [$user] = $this->makeUserWithWallet();

        $contest = $this->makeContest($host, name: 'KPL Degens');
        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        $data = app(BuyerDashboardService::class)->getDashboardData($user);

        $this->assertCount(1, $data['contests']);
        $this->assertSame('KPL Degens', $data['contests'][0]['name']);
        $this->assertSame('player', $data['contests'][0]['role']);
    }

    public function test_pending_entries_are_excluded(): void
    {
        [$host] = $this->makeUserWithWallet();
        [$user] = $this->makeUserWithWallet();

        $contest = $this->makeContest($host);
        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $data = app(BuyerDashboardService::class)->getDashboardData($user);

        $this->assertCount(0, $data['contests']);
    }

    public function test_settled_contests_are_excluded(): void
    {
        [$host] = $this->makeUserWithWallet();

        $this->makeContest($host, status: 'settled');

        $data = app(BuyerDashboardService::class)->getDashboardData($host);

        $this->assertCount(0, $data['contests']);
    }

    public function test_pending_join_requests_count_for_hosts(): void
    {
        [$host] = $this->makeUserWithWallet();
        [$joiner] = $this->makeUserWithWallet();

        $contest = $this->makeContest($host);
        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $joiner->id,
            'status'     => 'pending',
            'joined_at'  => now(),
        ]);

        $data = app(BuyerDashboardService::class)->getDashboardData($host);

        $this->assertCount(1, $data['contests']);
        $this->assertSame(1, $data['contests'][0]['pending_requests']);
    }

    public function test_contests_appear_on_seller_dashboard_too(): void
    {
        [$user] = $this->makeUserWithWallet();
        $this->makeContest($user, name: 'Sunday Crew');

        $data = app(SellerDashboardService::class)->getDashboardData($user);

        $this->assertArrayHasKey('contests', $data);
        $this->assertCount(1, $data['contests']);
    }

    private function makeContest(
        \App\Models\User $host,
        string $name = 'Test Contest',
        string $status = 'open',
    ): Contest {
        return Contest::create([
            'host_id'           => $host->id,
            'name'              => $name,
            'visibility'        => 'private',
            'status'            => $status,
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(4),
            'ends_at'           => now()->addDays(5),
        ]);
    }
}