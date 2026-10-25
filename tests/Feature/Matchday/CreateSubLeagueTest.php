<?php

namespace Tests\Feature\Matchday;

use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSubLeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
    }

    public function test_authenticated_user_can_create_sub_league(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Sunday Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Sunday Crew');
        $response->assertJsonPath('data.season', '2026-27');
    }

    public function test_sub_league_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'season' => '2026-27',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_sub_league_requires_season_from_configured_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Sunday Crew',
            'season' => '1999-00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('season');
    }

    public function test_creator_is_added_as_member_on_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Sunday Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ])->assertCreated();
        $sub = SubLeague::where('name', 'Sunday Crew')->firstOrFail();

        $this->assertSame($user->id, $sub->owner_id);
        $this->assertTrue(
            SubLeagueMember::where('sub_league_id', $sub->id)
                ->where('user_id', $user->id)
                ->exists()
        );
        $this->assertSame(1, $sub->members()->count());
    }

    public function test_invite_code_is_generated_and_unique(): void
    {
        $user = User::factory()->create();

        $a = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'A',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ])->json('data.invite_code');

        $b = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'B',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ])->json('data.invite_code');

        $this->assertIsString($a);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{12}$/', $a);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{12}$/', $b);
        $this->assertNotSame($a, $b);
    }

    public function test_cannot_create_two_sub_leagues_with_same_name_in_same_season(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Duplicate',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ])->assertCreated();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Duplicate',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_sub_league_requires_at_least_one_league_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Sunday Crew',
            'season' => '2026-27',
            'league_codes' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('league_codes');
    }

    public function test_sub_league_rejects_unsupported_league_codes(): void
    {
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Sunday Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL', 'BUNDESLIGA'],
        ]);

        $response->assertStatus(422);

        // Laravel surfaces the error on the offending array index, not the
        // parent key. Use a wildcard so the test is index-agnostic.
        $response->assertJsonValidationErrors(['league_codes.1']);
    }

    public function test_sub_league_accepts_every_league_code_from_config(): void
    {
        // Adding a league is a config-only operation. This test proves it:
        // a brand-new code not present in the original hardcoded pair is
        // accepted without touching any PHP file.
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
            'LALIGA' => 140,
            'SERIEA' => 135,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'All-of-Europe Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL', 'UCL', 'LALIGA', 'SERIEA'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath(
            'data.league_codes',
            ['EPL', 'UCL', 'LALIGA', 'SERIEA'],
        );
    }

    public function test_sub_league_creation_persists_league_codes(): void
    {
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'PL-only Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ])->assertCreated();

        $sub = SubLeague::where('name', 'PL-only Crew')->firstOrFail();
        $this->assertSame(['EPL'], $sub->league_codes);
    }
}