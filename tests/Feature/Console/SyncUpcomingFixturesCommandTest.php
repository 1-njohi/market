<?php

namespace Tests\Feature\Console;

use App\Models\Fixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncUpcomingFixturesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_fixtures_from_api_response(): void
    {
        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(id: 12345, homeId: 100, awayId: 200),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertDatabaseHas('fixtures', [
            'id_on_api'    => 12345,
            'home_team_id' => 100,
            'away_team_id' => 200,
            'status_short' => 'NS',
            'status_long'  => 'Not Started',
        ]);
    }

    public function test_updates_existing_fixture_without_duplicating(): void
    {
        Fixture::forceCreate([
            'id_on_api'    => 12345,
            'status_short' => 'NS',
            'status_long'  => 'Not Started',
            'home_team_id' => 100,
            'away_team_id' => 200,
            'home_winner'  => false,
            'settled'      => false,
        ]);

        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(
                    id: 12345,
                    homeId: 100,
                    awayId: 200,
                    statusShort: 'TBD',
                    statusLong: 'Time to be defined',
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertSame(1, Fixture::where('id_on_api', 12345)->count());
        $this->assertSame('TBD', Fixture::where('id_on_api', 12345)->value('status_short'));
    }

    public function test_continues_after_one_date_fails(): void
    {
        // First date → 500 error, second date → success.
        Http::fake([
            '*date=*' => Http::sequence()
                ->push('boom', 500)
                ->push($this->envelope([
                    $this->fixturePayload(id: 99999, homeId: 100, awayId: 200),
                ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        // Second date succeeded despite first date failure.
        $this->assertDatabaseHas('fixtures', ['id_on_api' => 99999]);
    }

    public function test_logs_error_on_api_failure(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        \Illuminate\Support\Facades\Log::spy();

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => is_string($msg) && str_contains($msg, 'Failed to sync fixtures'))
            ->atLeast()->once();
    }

    public function test_creates_home_and_away_teams_when_missing(): void
    {
        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(
                    id: 12345, homeId: 100, awayId: 200,
                    homeName: 'Manchester United', awayName: 'Newcastle',
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertDatabaseHas('teams', ['id_on_api' => 100, 'name' => 'Manchester United']);
        $this->assertDatabaseHas('teams', ['id_on_api' => 200, 'name' => 'Newcastle']);

        // Fixtures store the API team id (via homeTeam()/awayTeam() relations).
        $this->assertDatabaseHas('fixtures', [
            'id_on_api'    => 12345,
            'home_team_id' => 100,
            'away_team_id' => 200,
        ]);
    }

    public function test_creates_league_when_missing_and_links_fixture_by_local_id(): void
    {
        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(id: 12345, homeId: 100, awayId: 200, leagueId: 39),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $league = \App\Models\League::where('id_on_api', 39)->first();
        $this->assertNotNull($league);
        $this->assertSame('Premier League', $league->name);

        // fixtures.league_id targets leagues.id (local), not id_on_api.
        $this->assertDatabaseHas('fixtures', [
            'id_on_api' => 12345,
            'league_id' => $league->id,
        ]);
    }

    public function test_does_not_duplicate_teams_across_multiple_fixtures(): void
    {
        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(id: 12345, homeId: 100, awayId: 200),
                $this->fixturePayload(id: 12346, homeId: 100, awayId: 300),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertSame(1, \App\Models\Team::where('id_on_api', 100)->count());
        $this->assertSame(2, \App\Models\Team::whereIn('id_on_api', [200, 300])->count());
    }

    public function test_updates_existing_team_name_without_duplicating(): void
    {
        \App\Models\Team::forceCreate(['id_on_api' => 100, 'name' => 'Old Name']);

        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(
                    id: 12345, homeId: 100, awayId: 200,
                    homeName: 'New Name',
                ),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertSame(1, \App\Models\Team::where('id_on_api', 100)->count());
        $this->assertSame('New Name', \App\Models\Team::where('id_on_api', 100)->value('name'));
    }

    public function test_updates_existing_league_without_duplicating(): void
    {
        $existing = \App\Models\League::forceCreate([
            'id_on_api'  => 39,
            'name'       => 'Old League',
            'country_id' => 0,
        ]);

        Http::fake([
            '*' => Http::response($this->envelope([
                $this->fixturePayload(id: 12345, homeId: 100, awayId: 200, leagueId: 39, leagueName: 'New League'),
            ]), 200),
        ]);

        $this->artisan('fixtures:sync-upcoming')->assertSuccessful();

        $this->assertSame(1, \App\Models\League::where('id_on_api', 39)->count());
        $this->assertSame('New League', $existing->fresh()->name);

        // Fixture still links to the same (existing) local league id.
        $this->assertDatabaseHas('fixtures', [
            'id_on_api' => 12345,
            'league_id' => $existing->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    /** @param array<int, array<string, mixed>> $fixtures */
    private function envelope(array $fixtures): array
    {
        return [
            'get'      => 'fixtures',
            'results'  => count($fixtures),
            'response' => $fixtures,
        ];
    }

    private function fixturePayload(
        int $id,
        int $homeId,
        int $awayId,
        string $statusShort = 'NS',
        string $statusLong = 'Not Started',
        string $homeName = 'Home FC',
        string $awayName = 'Away FC',
        int $leagueId = 39,
        string $leagueName = 'Premier League',
    ): array {
        return [
            'fixture' => [
                'id'       => $id,
                'referee'  => 'A. Taylor',
                'timezone' => 'UTC',
                'date'     => '2026-10-05T19:00:00+00:00',
                'timestamp' => 1791183600,
                'periods'  => ['first' => null, 'second' => null],
                'venue'    => ['id' => 525, 'name' => 'Selhurst Park', 'city' => 'London'],
                'status'   => ['long' => $statusLong, 'short' => $statusShort, 'elapsed' => null, 'extra' => null],
            ],
            'league' => [
                'id'       => $leagueId,
                'name'     => $leagueName,
                'country'  => 'England',
                'logo'     => null,
                'flag'     => null,
                'season'   => 2026,
                'round'    => 'Regular Season - 1',
                'standings' => true,
            ],
            'teams' => [
                'home' => ['id' => $homeId, 'name' => $homeName, 'logo' => null, 'winner' => null],
                'away' => ['id' => $awayId, 'name' => $awayName, 'logo' => null, 'winner' => null],
            ],
            'goals' => ['home' => null, 'away' => null],
            'score' => [
                'halftime'  => ['home' => null, 'away' => null],
                'fulltime'  => ['home' => null, 'away' => null],
                'extratime' => ['home' => null, 'away' => null],
                'penalty'   => ['home' => null, 'away' => null],
            ],
            'events' => [],
        ];
    }
}