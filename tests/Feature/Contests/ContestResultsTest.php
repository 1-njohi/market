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
use App\Services\ContestScoringService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ContestResultsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_results_page_is_public(): void
    {
        $s = $this->settledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Contests/Results')
                ->where('contest.uuid', $s['contest']->uuid)
                ->has('standings', 2)
        );
    }

    public function test_standings_are_ranked_correctly(): void
    {
        $s = $this->settledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn ($page) => $page
                ->where('standings.0.rank', 1)
                ->where('standings.0.correct', 2)
                ->where('standings.1.rank', 2)
                ->where('standings.1.correct', 1)
        );
    }

    public function test_picks_are_visible_after_settlement(): void
    {
        $s = $this->settledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn ($page) => $page
                ->has('standings.0.picks', 2)
                ->where('standings.0.picks.0.leg_id', $s['legs'][0]->id)
                ->where('standings.0.picks.0.selection', 'Home')
                ->where('standings.0.picks.0.result', 'correct')
                ->where('standings.0.picks.0.is_winner', true)
        );
    }

    public function test_results_page_404s_for_unsettled_contest(): void
    {
        $s = $this->unsettledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        // Unsettled contest hasn't published results — redirect back to
        // the contest page instead of 404 so the user has context.
        $response->assertRedirect("/contests/join/{$s['contest']->uuid}");
    }

    public function test_legs_are_included_in_payload(): void
    {
        $s = $this->settledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn ($page) => $page
                ->has('contest.legs', 2)
                ->where('contest.legs.0.result_selection', 'Home')
                ->where('contest.legs.1.result_selection', 'Over 2.5')
        );
    }

    // ------------------ helpers ------------------

    private function settledScenario(): array
    {
        $s = $this->baseScenario();

        $this->resolveLeg($s['legs'][0], 'Home');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');

        // Entry A: 2 correct.
        $this->submitPicks($s['entries'][0], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        // Entry B: 1 correct.
        $this->submitPicks($s['entries'][1], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Under 2.5'],
        ]);

        app(ContestScoringService::class)->score($s['contest']);

        return $s;
    }

    private function unsettledScenario(): array
    {
        return $this->baseScenario();
    }

    private function baseScenario(): array
    {
        $host = $this->makeUser('Host');
        $contest = Contest::create([
            'host_id'           => $host->id,
            'name'              => 'Test Contest',
            'visibility'        => 'private',
            'status'            => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at'         => now()->addHours(2),
            'ends_at'           => now()->addHours(4),
        ]);

        $league = $this->makeLeague();
        $home   = $this->makeTeam('Home FC', 900001);
        $away   = $this->makeTeam('Away FC', 900002);

        $fx1 = $this->makeFixture($league, $home, $away);
        $fx2 = $this->makeFixture($league, $home, $away);

        $market1 = $this->makeMarket(1, 'Match Winner');
        $market2 = $this->makeMarket(2, 'Over/Under');

        $legs = [
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx1->id,
                'market_id'  => $market1->id,
                'status'     => 'pending',
            ]),
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx2->id,
                'market_id'  => $market2->id,
                'status'     => 'pending',
            ]),
        ];

        $userA = $this->makeUser('Alice');
        $userB = $this->makeUser('Bob');

        $entryA = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $userA->id,
            'status'     => 'accepted',
            'joined_at'  => now()->subMinutes(10),
        ]);
        $entryB = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $userB->id,
            'status'     => 'accepted',
            'joined_at'  => now()->subMinutes(5),
        ]);

        return [
            'host'    => $host,
            'contest' => $contest,
            'entries' => [$entryA, $entryB],
            'legs'    => $legs,
        ];
    }

    private function submitPicks(ContestEntry $entry, array $picks): void
    {
        foreach ($picks as [$leg, $selection]) {
            $odd = Odd::where('fixture_id', $leg->fixture_id)
                ->where('market_id', $leg->market_id)
                ->where('value', $selection)
                ->firstOrFail();

            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id'   => $leg->id,
                'selection'        => $selection,
                'odds_at_pick'     => $odd->odd,
                'status'           => 'pending',
            ]);
        }
    }

    private function resolveLeg(ContestLeg $leg, string $result): void
    {
        $leg->update([
            'status'           => 'won',
            'result_selection' => $result,
            'resolved_at'      => now(),
        ]);
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
            'date'         => now()->addHours(2)->toDateTimeString(),
            'timestamp'    => now()->addHours(2)->timestamp,
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