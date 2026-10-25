<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(\App\Services\SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
    }

    private function settledMatchday(int $number): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number}",
            'visibility' => 'public',
            'status' => 'settled',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->subDays(2),
            'entry_deadline_at' => now()->subDays(2),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(1),
            'settled_at' => now()->subDay(),
        ]);
    }

    private function entry(User $u, Contest $c, int $score): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $c->id,
            'user_id' => $u->id,
            'status' => 'accepted',
            'score_correct' => $score,
            'score_units' => 0,
            'joined_at' => now(),
        ]);
    }

    public function test_global_leaderboard_includes_all_users_with_matchday_entries(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $this->entry($alice, $md1, 4);
        $this->entry($bob, $md1, 2);

        $response = $this->actingAs($alice)
            ->getJson(route('leaderboards.matchday') . '?season=2026-27');

        $response->assertOk();
        $ids = array_column($response->json('data'), 'user_id');
        $this->assertContains($alice->id, $ids);
        $this->assertContains($bob->id, $ids);
    }

    public function test_global_leaderboard_supports_from_matchday_filter(): void
    {
        $alice = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $md2 = $this->settledMatchday(2);

        $this->entry($alice, $md1, 9);
        $this->entry($alice, $md2, 3);

        $response = $this->actingAs($alice)
            ->getJson(route('leaderboards.matchday') . '?season=2026-27&from_matchday=2');

        $response->assertOk();
        $this->assertSame(3, $response->json('data.0.points'));
    }

    public function test_global_leaderboard_excludes_users_with_no_entries(): void
    {
        $alice = User::factory()->create();
        $ghost = User::factory()->create();

        $md1 = $this->settledMatchday(1);
        $this->entry($alice, $md1, 4);

        $response = $this->actingAs($alice)
            ->getJson(route('leaderboards.matchday') . '?season=2026-27');

        $response->assertOk();
        $ids = array_column($response->json('data'), 'user_id');
        $this->assertNotContains($ghost->id, $ids);
    }

    public function test_global_leaderboard_requires_season(): void
    {
        $alice = User::factory()->create();

        $this->actingAs($alice)
            ->getJson(route('leaderboards.matchday'))
            ->assertStatus(422);
    }
}