<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementDismissalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $this->travelTo(Carbon::parse('2026-08-15 15:00:00'));
    }

    private function settledMatchday(int $number, array $overrides = []): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create(array_merge([
            'host_id'           => $host->id,
            'name'              => "MD{$number}",
            'visibility'        => 'public',
            'status'            => 'settled',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => $number,
            'locks_at'          => now()->subDays(3),
            'entry_deadline_at' => now()->subDays(3),
            'starts_at'         => now()->subDays(3),
            'ends_at'           => now()->subDays(2),
            'settled_at'        => now()->subDay(),
        ], $overrides));
    }

    private function entry(User $user, Contest $contest, int $score = 0): ContestEntry
    {
        return ContestEntry::create([
            'contest_id'    => $contest->id,
            'user_id'       => $user->id,
            'status'        => 'accepted',
            'score_correct' => $score,
            'joined_at'     => now()->subDays(4),
            'settled_at'    => $contest->settled_at,
        ]);
    }

    public function test_settled_entry_appears_in_unacknowledged_settlements(): void
    {
        $user    = User::factory()->create();
        $contest = $this->settledMatchday(1);
        $this->entry($user, $contest, 5);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('unacknowledged_settlements', 1)
            ->where('unacknowledged_settlements.0.uuid', $contest->uuid)
            ->where('unacknowledged_settlements.0.matchday_number', 1)
        );
    }

    public function test_multiple_settlements_ordered_by_settled_at_descending(): void
    {
        $user = User::factory()->create();

        $older = $this->settledMatchday(1, ['settled_at' => now()->subDays(5)]);
        $newer = $this->settledMatchday(2, ['settled_at' => now()->subDay()]);

        $this->entry($user, $older, 3);
        $this->entry($user, $newer, 4);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('unacknowledged_settlements', 2)
            ->where('unacknowledged_settlements.0.uuid', $newer->uuid)
            ->where('unacknowledged_settlements.1.uuid', $older->uuid)
        );
    }

    public function test_dismissed_settlement_is_removed_from_prop(): void
    {
        $user    = User::factory()->create();
        $contest = $this->settledMatchday(1);
        $entry   = $this->entry($user, $contest, 3);

        $entry->update(['dismissed_settlement_at' => now()]);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('unacknowledged_settlements', 0)
        );
    }

    public function test_dismiss_endpoint_sets_timestamp(): void
    {
        $user    = User::factory()->create();
        $contest = $this->settledMatchday(1);
        $entry   = $this->entry($user, $contest, 3);

        $response = $this->actingAs($user)
            ->postJson(route('matchday.settlements.dismiss', $entry));

        $response->assertOk();
        $this->assertNotNull($entry->fresh()->dismissed_settlement_at);
    }

    public function test_dismiss_endpoint_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $contest = $this->settledMatchday(1);
        $entry   = $this->entry($user, $contest, 3);

        $this->actingAs($user)
            ->postJson(route('matchday.settlements.dismiss', $entry))
            ->assertOk();

        $first = $entry->fresh()->dismissed_settlement_at;

        $this->actingAs($user)
            ->postJson(route('matchday.settlements.dismiss', $entry))
            ->assertOk();

        $this->assertEquals($first, $entry->fresh()->dismissed_settlement_at);
    }

    public function test_cannot_dismiss_another_users_entry(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $contest = $this->settledMatchday(1);
        $entry   = $this->entry($owner, $contest, 3);

        $this->actingAs($other)
            ->postJson(route('matchday.settlements.dismiss', $entry))
            ->assertStatus(403);
    }

    public function test_cannot_dismiss_unsettled_entry(): void
    {
        $user = User::factory()->create();
        $host = User::factory()->create(['is_admin' => true]);

        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'MD1 open',
            'visibility'        => 'public',
            'status'            => 'open',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => 1,
            'locks_at'          => now()->addDays(2),
            'entry_deadline_at' => now()->addDays(2),
            'starts_at'         => now()->addDays(2),
            'ends_at'           => now()->addDays(3),
        ]);

        $entry = $this->entry($user, $contest, 0);
        $entry->update(['settled_at' => null]);

        $this->actingAs($user)
            ->postJson(route('matchday.settlements.dismiss', $entry))
            ->assertStatus(422);
    }
}