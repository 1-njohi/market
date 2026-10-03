<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContestTwoStepCreateTest extends TestCase
{
    use DatabaseMigrations;

    // ═══════════════════════════════════════════════════════════════
    // STEP 1 — draft (legs only)
    // ═══════════════════════════════════════════════════════════════

    public function test_authenticated_user_can_save_a_draft(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $response = $this->actingAs($host)
            ->post('/contests/draft', $this->draftPayload($s));

        $response->assertRedirect('/contests/confirm');

        $draft = session('contest_draft');
        $this->assertNotNull($draft);
        $this->assertCount(5, $draft);
        $this->assertSame($s['fixtures'][0]->id, $draft[0]['fixture_id']);
        $this->assertSame('Home', $draft[0]['selection']);
    }

    public function test_guest_cannot_save_a_draft(): void
    {
        $s = $this->fixtureSet();

        $response = $this->post('/contests/draft', $this->draftPayload($s));

        $response->assertRedirect('/login');
        $this->assertNull(session('contest_draft'));
    }

    public function test_draft_requires_at_least_five_legs(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => [
                $this->legFrom($s, 0),
                $this->legFrom($s, 1),
                $this->legFrom($s, 2),
                $this->legFrom($s, 3),
            ],
        ]);

        $response->assertSessionHasErrors('legs');
        $this->assertNull(session('contest_draft'));
    }

    public function test_draft_requires_selection_on_every_leg(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = array_map(fn ($i) => $this->legFrom($s, $i), range(0, 4));
        unset($legs[2]['selection']);
        $legs = array_values($legs);

        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => $legs,
        ]);

        $response->assertSessionHasErrors('legs.2.selection');
        $this->assertNull(session('contest_draft'));
    }

    public function test_draft_rejects_invalid_selection(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = array_map(fn ($i) => $this->legFrom($s, $i), range(0, 4));
        $legs[0]['selection'] = 'Nonsense';
        $legs = array_values($legs);

        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => $legs,
        ]);

        $response->assertSessionHasErrors('legs.0.selection');
        $this->assertNull(session('contest_draft'));
    }

    public function test_draft_rejects_duplicate_fixture_market_pairs(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = array_map(fn ($i) => $this->legFrom($s, $i), range(0, 4));
        $legs[1] = $legs[0];
        $legs = array_values($legs);

        $response = $this->actingAs($host)->post('/contests/draft', [
            'legs' => $legs,
        ]);

        $response->assertSessionHasErrors('legs');
        $this->assertNull(session('contest_draft'));
    }

    public function test_draft_does_not_create_a_contest_row(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $this->assertSame(0, Contest::count());
    }

    // ═══════════════════════════════════════════════════════════════
    // STEP 2 UI — confirm page
    // ═══════════════════════════════════════════════════════════════

    public function test_confirm_renders_draft_legs(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)->get('/contests/confirm');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contests/Confirm')
            ->has('legs', 5)
            ->where('legs.0.selection', 'Home')
            ->has('earliest_kickoff')
        );
    }

    public function test_confirm_redirects_when_draft_is_missing(): void
    {
        $host = $this->makeUser('Host');

        $response = $this->actingAs($host)->get('/contests/confirm');

        $response->assertRedirect('/contests/create');
    }

    // ═══════════════════════════════════════════════════════════════
    // STEP 2 SUBMIT — publish
    // ═══════════════════════════════════════════════════════════════

    public function test_publish_creates_contest_from_draft(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s));

        $response->assertRedirect();
        $this->assertSame(1, Contest::count());

        $contest = Contest::first();
        $this->assertSame('Sunday Crew', $contest->name);
        $this->assertSame('Weekly challenge.', $contest->description);
        $this->assertSame($host->id, $contest->host_id);
        $this->assertSame('private', $contest->visibility);
        $this->assertSame('open', $contest->status);
    }

    public function test_publish_creates_legs_entry_and_picks(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));
        $this->actingAs($host)->post('/contests', $this->configPayload($s));

        $contest = Contest::first();

        $this->assertSame(5, ContestLeg::where('contest_id', $contest->id)->count());

        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $host->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('accepted', $entry->status);
        $this->assertSame(5, ContestPick::where('contest_entry_id', $entry->id)->count());
    }

    public function test_publish_clears_the_draft(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));
        $this->actingAs($host)->post('/contests', $this->configPayload($s));

        $this->assertNull(session('contest_draft'));
    }

    public function test_publish_redirects_when_draft_is_missing(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s));

        $response->assertRedirect('/contests/create');
        $this->assertSame(0, Contest::count());
    }

    public function test_publish_requires_name(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s, ['name' => '']));

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Contest::count());
    }

    public function test_publish_requires_deadline(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s, ['entry_deadline_at' => '']));

        $response->assertSessionHasErrors('entry_deadline_at');
        $this->assertSame(0, Contest::count());
    }

    public function test_publish_requires_deadline_in_the_future(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s, [
                'entry_deadline_at' => now()->subDay()->toDateTimeString(),
            ]));

        $response->assertSessionHasErrors('entry_deadline_at');
        $this->assertSame(0, Contest::count());
    }

    public function test_publish_rejects_deadline_after_earliest_kickoff(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        // Earliest kickoff is +2h. Deadline +3h is after it.
        $this->actingAs($host)->post('/contests/draft', $this->draftPayload($s));

        $response = $this->actingAs($host)
            ->post('/contests', $this->configPayload($s, [
                'entry_deadline_at' => now()->addHours(3)->toDateTimeString(),
            ]));

        $response->assertSessionHasErrors('entry_deadline_at');
        $this->assertSame(0, Contest::count());
    }

    // ═══════════════════════════════════════════════════════════════
    // helpers
    // ═══════════════════════════════════════════════════════════════

    private function fixtureSet(): array
    {
        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home FC', 900001);
        $away   = $this->makeTeam('Away FC', 900002);

        $fixtures = [];
        for ($i = 0; $i < 6; $i++) {
            $fixtures[] = $this->makeFixture(
                $league,
                $home,
                $away,
                kickoff: now()->addHours(2 + $i * 2),
            );
        }

        return [
            'league'   => $league,
            'home'     => $home,
            'away'     => $away,
            'fixtures' => $fixtures,
            'market'   => $this->makeMarket(1, 'Match Winner'),
        ];
    }

    private function legFrom(array $s, int $fixtureIndex): array
    {
        return [
            'fixture_id' => $s['fixtures'][$fixtureIndex]->id,
            'market_id'  => $s['market']->id,
            'selection'  => 'Home',
        ];
    }

    /** Step 1 payload — legs only. */
    private function draftPayload(array $s): array
    {
        return [
            'legs' => array_map(fn ($i) => $this->legFrom($s, $i), range(0, 4)),
        ];
    }

    /** Step 2 payload — config only. Legs come from the session. */
    private function configPayload(array $s, array $overrides = []): array
    {
        return array_merge([
            'name'              => 'Sunday Crew',
            'description'       => 'Weekly challenge.',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
        ], $overrides);
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