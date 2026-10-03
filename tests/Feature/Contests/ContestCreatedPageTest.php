<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContestCreatedPageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_publish_redirects_to_the_created_page(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->draft($host, $s);
        $response = $this->publish($host);

        $contest = Contest::firstOrFail();
        $response->assertRedirect("/contests/{$contest->id}/created");
    }

    public function test_created_page_renders_for_host(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->draft($host, $s);
        $this->publish($host);
        $contest = Contest::firstOrFail();

        $response = $this->actingAs($host)->get("/contests/{$contest->id}/created");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contests/Created')
            ->where('contest.id', $contest->id)
            ->where('contest.uuid', $contest->uuid)
            ->where('contest.name', 'Sunday Crew')
            ->where('contest.status', 'open')
            ->where('contest.legs_count', 5)
            ->has('contest.entry_deadline_at')
        );
    }

    public function test_created_page_forbidden_for_non_host(): void
    {
        $host    = $this->makeUser('Host');
        $other   = $this->makeUser('Other');
        $s       = $this->fixtureSet();

        $this->draft($host, $s);
        $this->publish($host);
        $contest = Contest::firstOrFail();

        $response = $this->actingAs($other)->get("/contests/{$contest->id}/created");

        $response->assertForbidden();
    }

    public function test_created_page_redirects_guests_to_login(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->draft($host, $s);
        $this->publish($host);
        $contest = Contest::firstOrFail();

        // actingAs() persists for the rest of this test method. Clear the
        // guard so the following request is genuinely unauthenticated.
        auth()->logout();

        $response = $this->get("/contests/{$contest->id}/created");

        $response->assertRedirect('/login');
    }

    // ------------------ helpers ------------------

    private function draft(User $host, array $s): void
    {
        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $legs[] = [
                'fixture_id' => $s['fixtures'][$i]->id,
                'market_id'  => $s['market1']->id,
                'selection'  => 'Home',
            ];
        }

        $this->actingAs($host)->post('/contests/draft', ['legs' => $legs]);
    }

    private function publish(User $host)
    {
        return $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'description'       => 'Weekly challenge.',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
        ]);
    }

    private function fixtureSet(): array
    {
        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home FC', 900001);
        $away   = $this->makeTeam('Away FC', 900002);

        $fixtures = [];
        for ($i = 0; $i < 6; $i++) {
            $fixtures[] = $this->makeFixture(
                $league, $home, $away,
                kickoff: now()->addHours(2 + $i * 2),
            );
        }

        return [
            'league'   => $league,
            'home'     => $home,
            'away'     => $away,
            'fixtures' => $fixtures,
            'market1'  => $this->makeMarket(1, 'Match Winner'),
        ];
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(Str::random(8)),
        ]);
    }

    private function makeLeague(): League
    {
        return League::firstOrCreate(
            ['id_on_api' => 900100],
            [
                'sport_id'   => 1,
                'name'       => 'Test League',
                'country_id' => 1,
                'country'    => 'Test Country',
                'season'     => 2026,
            ]
        );
    }

    private function makeTeam(string $name, int $idOnApi): Team
    {
        return Team::firstOrCreate(['id_on_api' => $idOnApi], ['name' => $name]);
    }

    private function makeFixture(
        League $league,
        Team $home,
        Team $away,
        ?\Carbon\CarbonInterface $kickoff = null,
    ): Fixture {
        $kickoff = $kickoff ?? now()->addHours(2);

        $fixture = Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 99999),
            'date'         => $kickoff->toDateTimeString(),
            'timestamp'    => $kickoff->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.50, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 3.20, 'status' => 'pending']);

        return $fixture;
    }

    private function makeMarket(int $id, string $name): Market
    {
        return Market::firstOrCreate(['id' => $id], ['name' => $name]);
    }
}