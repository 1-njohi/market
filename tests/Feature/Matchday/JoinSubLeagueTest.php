<?php

namespace Tests\Feature\Matchday;

use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\SubLeagueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JoinSubLeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
    }

    private function makeSubLeague(User $owner, string $name = 'Crew'): SubLeague
    {
        return app(SubLeagueService::class)->create($owner, $name, '2026-27');
    }

    public function test_user_can_join_public_sub_league_by_invite_code(): void
    {
        $owner  = User::factory()->create();
        $joiner = User::factory()->create();
        $sub    = $this->makeSubLeague($owner);

        $response = $this->actingAs($joiner)->postJson(route('sub-leagues.join'), [
            'invite_code' => $sub->invite_code,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', $sub->id);

        $this->assertTrue(
            SubLeagueMember::where('sub_league_id', $sub->id)
                ->where('user_id', $joiner->id)
                ->exists()
        );
    }

    public function test_join_fails_when_invite_code_is_invalid(): void
    {
        $joiner = User::factory()->create();

        $response = $this->actingAs($joiner)->postJson(route('sub-leagues.join'), [
            'invite_code' => 'DOESNOTEXIST',
        ]);

        $response->assertStatus(404);
    }

    public function test_join_fails_when_sub_league_is_full(): void
    {
        config()->set('marketplace.matchday.max_sub_league_members', 2);

        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        // Owner (1) + one joiner (2) = cap of 2.
        $this->actingAs(User::factory()->create())
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code])
            ->assertOk();

        $overflow = $this->actingAs(User::factory()->create())
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code]);

        $overflow->assertStatus(422);
        $this->assertSame(2, $sub->fresh()->members()->count());
    }

    public function test_join_fails_when_user_is_already_a_member(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        // Owner is already a member.
        $response = $this->actingAs($owner)
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code]);

        $response->assertStatus(422);
        $this->assertSame(1, $sub->fresh()->members()->count());
    }

    public function test_user_can_join_multiple_sub_leagues_in_same_season(): void
    {
        $owner = User::factory()->create();
        $a = $this->makeSubLeague($owner, 'A');
        $b = $this->makeSubLeague($owner, 'B');

        $joiner = User::factory()->create();

        $this->actingAs($joiner)
            ->postJson(route('sub-leagues.join'), ['invite_code' => $a->invite_code])
            ->assertOk();

        $this->actingAs($joiner)
            ->postJson(route('sub-leagues.join'), ['invite_code' => $b->invite_code])
            ->assertOk();

        $this->assertSame(
            2,
            SubLeagueMember::where('user_id', $joiner->id)->count()
        );
    }

    public function test_member_count_updates_after_join(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $this->assertSame(1, $sub->fresh()->members()->count());

        $this->actingAs(User::factory()->create())
            ->postJson(route('sub-leagues.join'), ['invite_code' => $sub->invite_code])
            ->assertOk();

        $this->assertSame(2, $sub->fresh()->members()->count());
    }
}