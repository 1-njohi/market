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
use Tests\TestCase;

class ContestCreateWithHostPicksTest extends TestCase
{
    use DatabaseMigrations;

    public function test_create_requires_a_selection_for_every_leg(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $response = $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
            'legs' => [
                ['fixture_id' => $s['fixtures'][0]->id, 'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => $s['fixtures'][1]->id, 'market_id' => $s['market1']->id, 'selection' => 'Away'],
                ['fixture_id' => $s['fixtures'][2]->id, 'market_id' => $s['market1']->id, 'selection' => 'Home'],
                ['fixture_id' => $s['fixtures'][3]->id, 'market_id' => $s['market1']->id, 'selection' => 'Away'],
                ['fixture_id' => $s['fixtures'][4]->id, 'market_id' => $s['market1']->id], // missing selection
            ],
        ]);

        $response->assertSessionHasErrors('legs.4.selection');
        $this->assertSame(0, Contest::count());
    }

    public function test_create_rejects_invalid_selection_for_a_leg(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $legs[] = [
                'fixture_id' => $s['fixtures'][$i]->id,
                'market_id'  => $s['market1']->id,
                'selection'  => $i === 0 ? 'Nonsense' : 'Home',
            ];
        }

        $response = $this->actingAs($host)->post('/contests', [
            'name'              => 'Bad pick',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
            'legs'              => $legs,
        ]);

        $response->assertSessionHasErrors('legs.0.selection');
        $this->assertSame(0, Contest::count());
    }

    public function test_create_creates_a_host_entry_with_picks(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $legs[] = [
                'fixture_id' => $s['fixtures'][$i]->id,
                'market_id'  => $s['market1']->id,
                'selection'  => 'Home',
            ];
        }

        $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
            'legs'              => $legs,
        ]);

        $contest = Contest::firstOrFail();

        // Host has an accepted entry.
        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $host->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('accepted', $entry->status);

        // Host has one pick per leg, with odds snapshotted.
        $this->assertSame(5, ContestPick::where('contest_entry_id', $entry->id)->count());

        $firstPick = ContestPick::where('contest_entry_id', $entry->id)
            ->where('contest_leg_id', $contest->legs[0]->id)
            ->firstOrFail();

        $this->assertSame('Home', $firstPick->selection);
        $this->assertSame('2.50', (string) $firstPick->odds_at_pick);
    }

    public function test_manage_page_does_not_include_host_entry_in_entries_list(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $legs[] = [
                'fixture_id' => $s['fixtures'][$i]->id,
                'market_id'  => $s['market1']->id,
                'selection'  => 'Home',
            ];
        }

        $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
            'legs'              => $legs,
        ]);

        $contest = Contest::firstOrFail();

        $response = $this->actingAs($host)
            ->get("/contests/{$contest->id}/manage");

        $response->assertInertia(
            fn ($page) => $page->has('contest.entries', 0)
        );
    }

    public function test_host_can_view_and_edit_their_picks(): void
    {
        $host = $this->makeUser('Host');
        $s    = $this->fixtureSet();

        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $legs[] = [
                'fixture_id' => $s['fixtures'][$i]->id,
                'market_id'  => $s['market1']->id,
                'selection'  => 'Home',
            ];
        }

        $this->actingAs($host)->post('/contests', [
            'name'              => 'Sunday Crew',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
            'legs'              => $legs,
        ]);

        $contest = Contest::firstOrFail();

        // Host can load the picks page.
        $response = $this->actingAs($host)
            ->get("/contests/{$contest->uuid}/picks");
        $response->assertOk();

        // Host can change picks.
        $this->actingAs($host)->post("/contests/{$contest->uuid}/picks", [
            'picks' => $contest->legs->map(fn ($leg) => [
                'leg_id'    => $leg->id,
                'selection' => 'Away',
            ])->all(),
        ]);

        $entry = ContestEntry::where('contest_id', $contest->id)
            ->where('user_id', $host->id)
            ->firstOrFail();

        $this->assertSame(
            5,
            ContestPick::where('contest_entry_id', $entry->id)
                ->where('selection', 'Away')
                ->count()
        );
    }

    // ------------------ helpers ------------------

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
            'code' => strtoupper(\Illuminate\Support\Str::random(8)),
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
            'home_team_id' => $home->id_on_api,
            'away_team_id' => $away->id_on_api,
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