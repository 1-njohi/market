<?php

namespace Tests\Feature\Contests;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Team;
use App\Models\User;
use App\Models\Odd;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContestPickSubmissionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_accepted_entry_can_view_pick_page(): void
    {
        $s = $this->scenario();

        $response = $this->actingAs($s['user'])
            ->get("/contests/{$s['contest']->uuid}/picks");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Picks')
                ->has('contest.legs', 2)
                ->has('picks', 0)
        );
    }

    public function test_pending_entry_cannot_view_pick_page(): void
    {
        $s = $this->scenario(entryStatus: 'pending');

        $response = $this->actingAs($s['user'])
            ->get("/contests/{$s['contest']->uuid}/picks");

        $response->assertStatus(403);
    }

    public function test_host_cannot_view_pick_page_for_own_contest(): void
    {
        $s = $this->scenario(forceAsHost: true);

        $response = $this->actingAs($s['host'])
            ->get("/contests/{$s['contest']->uuid}/picks");

        $response->assertStatus(403);
    }

    public function test_accepted_entry_can_submit_picks(): void
    {
        $s = $this->scenario();

        $response = $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Home'],
                    ['leg_id' => $s['legs'][1]->id, 'selection' => 'Over 2.5'],
                ],
            ]);

        $response->assertRedirect();

        $this->assertSame(2, ContestPick::count());
        $this->assertDatabaseHas('contest_picks', [
            'contest_entry_id' => $s['entry']->id,
            'contest_leg_id'   => $s['legs'][0]->id,
            'selection'        => 'Home',
        ]);
    }

    public function test_picks_snapshot_odds_at_submission(): void
    {
        $s = $this->scenario();

        $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Home'],
                ],
            ]);

        $pick = ContestPick::where('contest_leg_id', $s['legs'][0]->id)->firstOrFail();

        // The helper sets odds to 2.50 for the Home selection.
        $this->assertSame(2.50, (float) $pick->odds_at_pick);
    }

    public function test_resubmitting_picks_replaces_previous(): void
    {
        $s = $this->scenario();

        $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Home'],
                ],
            ]);

        $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Away'],
                ],
            ]);

        $this->assertSame(1, ContestPick::count());

        $pick = ContestPick::where('contest_leg_id', $s['legs'][0]->id)->firstOrFail();
        $this->assertSame('Away', $pick->selection);
    }

    public function test_cannot_submit_picks_after_deadline(): void
    {
        $s = $this->scenario(deadline: now()->subDay());

        $response = $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Home'],
                ],
            ]);

        $response->assertSessionHasErrors('picks');
        $this->assertSame(0, ContestPick::count());
    }

    public function test_cannot_pick_a_leg_from_another_contest(): void
    {
        $s = $this->scenario();
        $other = $this->scenario();

        $response = $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $other['legs'][0]->id, 'selection' => 'Home'],
                ],
            ]);

        $response->assertSessionHasErrors('picks');
        $this->assertSame(0, ContestPick::count());
    }

    public function test_cannot_pick_an_invalid_selection(): void
    {
        $s = $this->scenario();

        $response = $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Nonsense'],
                ],
            ]);

        $response->assertSessionHasErrors('picks');
        $this->assertSame(0, ContestPick::count());
    }

    public function test_cannot_submit_duplicate_leg_picks(): void
    {
        $s = $this->scenario();

        $response = $this->actingAs($s['user'])
            ->post("/contests/{$s['contest']->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Home'],
                    ['leg_id' => $s['legs'][0]->id, 'selection' => 'Away'],
                ],
            ]);

        $response->assertSessionHasErrors('picks');
    }

    // ------------------ helpers ------------------

    /**
     * @return array{
     *     host: User,
     *     user: User,
     *     contest: Contest,
     *     entry: ContestEntry,
     *     legs: ContestLeg[]
     * }
     */
    private function scenario(
        string $entryStatus = 'accepted',
        ?\Carbon\CarbonInterface $deadline = null,
        bool $forceAsHost = false,
    ): array {
        $host = $this->makeUser('Host');
        $user = $forceAsHost ? $host : $this->makeUser('Joiner');

        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Test Contest',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => $deadline ?? now()->addDay(),
            'starts_at'         => now()->addDays(2),
            'ends_at'           => now()->addDays(3),
        ]);

        $entry = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $forceAsHost ? $this->makeUser('Other')->id : $user->id,
            'status'     => $entryStatus,
            'joined_at'  => now(),
        ]);

        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home Team', 900001);
        $away   = $this->makeTeam('Away Team', 900002);

        $fixtureA = $this->makeFixture($league, $home, $away);
        $fixtureB = $this->makeFixture($league, $home, $away);

        $market1 = $this->makeMarket(1, 'Match Winner');
        $market2 = $this->makeMarket(2, 'Over/Under');

        $legs = [
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fixtureA->id,
                'market_id'  => $market1->id,
                'status'     => 'pending',
            ]),
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fixtureB->id,
                'market_id'  => $market2->id,
                'status'     => 'pending',
            ]),
        ];

        return compact('host', 'user', 'contest', 'entry', 'legs');
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
        return Team::firstOrCreate(
            ['id_on_api' => $idOnApi],
            ['name' => $name]
        );
    }

    private function makeFixture(League $league, Team $home, Team $away): Fixture
    {
        $fixture = Fixture::create([
            'id_on_api'    => 900000 + random_int(1, 99999),
            'date'         => now()->addDays(2)->toDateTimeString(),
            'timestamp'    => now()->addDays(2)->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        // Market 1 (Match Winner) — Home/Away.
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'value'      => 'Home',
            'odd'        => 2.50,
            'status'     => 'pending',
        ]);
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'value'      => 'Away',
            'odd'        => 3.20,
            'status'     => 'pending',
        ]);

        // Market 2 (Over/Under) — Over 2.5 / Under 2.5.
        // Wait — the second leg in the test picks 'Over 2.5' but the market
        // is created as Market 1. Fix the test or add market 2.
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => 2,
            'value'      => 'Over 2.5',
            'odd'        => 1.85,
            'status'     => 'pending',
        ]);
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => 2,
            'value'      => 'Under 2.5',
            'odd'        => 2.05,
            'status'     => 'pending',
        ]);

        return $fixture;
    }

    private function makeMarket(int $id, string $name): Market
    {
        return Market::firstOrCreate(
            ['id' => $id],
            ['name' => $name]
        );
    }
}