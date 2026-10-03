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

class ContestCreateTest extends TestCase
{
    use DatabaseMigrations;

    public function test_create_page_renders_for_authenticated_user(): void
    {
        $host = $this->makeUser('Host');

        $response = $this->actingAs($host)->get('/contests/create');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Create')
                ->has('leagues')
        );
    }

    public function test_guest_cannot_view_create_page(): void
    {
        $response = $this->get('/contests/create');

        $response->assertRedirect('/login');
    }

    public function test_host_can_create_a_contest_with_legs(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        // Need 5 legs — minimum is 5.
        $extraFixtures = [
            $this->makeFixture($s['league'], $s['home'], $s['away'], kickoff: now()->addHours(6)),
            $this->makeFixture($s['league'], $s['home'], $s['away'], kickoff: now()->addHours(8)),
            $this->makeFixture($s['league'], $s['home'], $s['away'], kickoff: now()->addHours(10)),
        ];

        $legs = [
            ['fixture_id' => $s['fixtures'][0]->id, 'market_id' => $s['market1']->id, 'selection' => 'Home'],
            ['fixture_id' => $s['fixtures'][1]->id, 'market_id' => $s['market2']->id, 'selection' => 'Over 2.5'],
            ['fixture_id' => $extraFixtures[0]->id, 'market_id' => $s['market1']->id, 'selection' => 'Away'],
            ['fixture_id' => $extraFixtures[1]->id, 'market_id' => $s['market1']->id, 'selection' => 'Home'],
            ['fixture_id' => $extraFixtures[2]->id, 'market_id' => $s['market1']->id, 'selection' => 'Away'],
        ];

        // Step 1: pick legs.
        $this->actingAs($host)
            ->post('/contests/draft', ['legs' => $legs])
            ->assertRedirect('/contests/confirm');

        // Step 2: publish with config only. Legs come from session.
        $response = $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'description'       => 'Weekly challenge.',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertRedirect();

        $contest = Contest::where('host_id', $host->id)->firstOrFail();
        $this->assertSame('Sunday Crew', $contest->name);
        $this->assertSame('private', $contest->visibility);
        $this->assertSame('open', $contest->status);
        $this->assertCount(5, $contest->legs);

        $this->assertEquals(
            $s['fixtures'][0]->date,
            $contest->starts_at->toDateTimeString()
        );
    }

    public function test_leg_with_invalid_fixture_or_market_rejected(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        // Pad to 5 legs so the min-count rule passes and the fixture
        // existence check is what actually fires.
        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => [
                ['fixture_id' => 999999,                    'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => $s['fixtures'][0]->id,     'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => $s['fixtures'][1]->id,     'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => 999998,                    'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => 999997,                    'market_id' => $s['market1']->id, 'selection' => 'Home'],
            ],
        ]);

        $response->assertSessionHasErrors('legs');
        $this->assertSame(0, Contest::count());
    }

    public function test_leg_cap_enforced_at_fifty(): void
    {
        $host   = $this->makeUser('Host');
        $s      = $this->fixtureSet();
        $league = $s['league'];
        $home   = $s['home'];
        $away   = $s['away'];

        $legs = [];
        for ($i = 0; $i < 55; $i++) {
            $fx = $this->makeFixture($league, $home, $away);
            $legs[] = [
                'fixture_id' => $fx->id,
                'market_id'  => $s['market1']->id,
                'selection'  => 'Home',
            ];
        }

        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => $legs,
        ]);

        $response->assertSessionHasErrors('legs');
    }

    // ------------------ helpers ------------------

    private function fixtureSet(): array
    {
        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home FC', 900001);
        $away   = $this->makeTeam('Away FC', 900002);

        $fx1 = $this->makeFixture($league, $home, $away, kickoff: now()->addHours(2));
        $fx2 = $this->makeFixture($league, $home, $away, kickoff: now()->addHours(4));

        return [
            'league'   => $league,
            'home'     => $home,
            'away'     => $away,
            'fixtures' => [$fx1, $fx2],
            'market1'  => $this->makeMarket(1, 'Match Winner'),
            'market2'  => $this->makeMarket(2, 'Over/Under'),
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

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home',      'odd' => 2.50, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away',      'odd' => 3.20, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Over 2.5',  'odd' => 1.85, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Under 2.5', 'odd' => 2.05, 'status' => 'pending']);

        return $fixture;
    }

    private function makeMarket(int $id, string $name): Market
    {
        return Market::firstOrCreate(['id' => $id], ['name' => $name]);
    }
}