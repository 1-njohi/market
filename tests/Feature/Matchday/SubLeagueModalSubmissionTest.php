<?php

namespace Tests\Feature\Matchday;

use App\Models\SubLeague;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubLeagueModalSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);
        config()->set('marketplace.matchday.league_labels', [
            'EPL' => 'Premier League',
            'UCL' => 'UEFA Champions League',
        ]);
    }

    public function test_create_modal_accepts_multiple_league_codes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Mixed Crew',
            'season' => '2026-27',
            'league_codes' => ['EPL', 'UCL'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.league_codes', ['EPL', 'UCL']);

        $sub = SubLeague::where('name', 'Mixed Crew')->firstOrFail();
        $this->assertSame(['EPL', 'UCL'], $sub->league_codes);
    }

    public function test_create_modal_accepts_a_single_league_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'EPL Only',
            'season' => '2026-27',
            'league_codes' => ['EPL'],
        ]);

        $response->assertCreated();
        $sub = SubLeague::where('name', 'EPL Only')->firstOrFail();
        $this->assertSame(['EPL'], $sub->league_codes);
    }

    public function test_create_modal_rejects_empty_league_codes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Empty',
            'season' => '2026-27',
            'league_codes' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('league_codes');
        $this->assertSame(0, SubLeague::count());
    }

    public function test_create_modal_rejects_unsupported_league_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('sub-leagues.store'), [
            'name' => 'Bad',
            'season' => '2026-27',
            'league_codes' => ['EPL', 'BUNDESLIGA'],
        ]);

        $response->assertStatus(422);
        // Laravel surfaces the error on the offending array index.
        $response->assertJsonValidationErrors(['league_codes.1']);
        $this->assertSame(0, SubLeague::count());
    }

    public function test_dashboard_props_expose_league_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/Dashboard')
                ->where('league_options', [
                    ['code' => 'EPL', 'label' => 'Premier League'],
                    ['code' => 'UCL', 'label' => 'UEFA Champions League'],
                ])
        );
    }
}