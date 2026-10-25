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
use Illuminate\Support\Facades\DB;
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
            fn($page) => $page
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
            fn($page) => $page
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
            fn($page) => $page
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
            fn($page) => $page
                ->has('contest.legs', 2)
                ->where('contest.legs.0.result_selection', 'Home')
                ->where('contest.legs.1.result_selection', 'Over 2.5')
        );
    }

    public function test_underway_contest_shows_provisional_standings(): void
    {
        $s = $this->underwayScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Contests/Results')
                ->where('contest.status', 'underway')
                ->has('standings', 2)
                // Alice: one correct pick, one pending.
                ->where('standings.0.rank', 1)
                ->where('standings.0.correct', 1)
                ->where('standings.0.picks.0.result', 'correct')
                ->where('standings.0.picks.1.result', 'pending')
                // Bob: no picks submitted, scores zero, ranked below.
                ->where('standings.1.correct', 0)
        );
    }

    public function test_open_contest_still_redirects_to_join(): void
    {
        // Regression guard: the gate now allows 'underway' but must
        // still redirect 'open', 'locked', and 'cancelled'.
        $s = $this->unsettledScenario();

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertRedirect("/contests/join/{$s['contest']->uuid}");
    }

    public function test_standings_expose_rank_previous(): void
    {
        $s = $this->underwayScenario();

        // Second interim pass: rank_previous rolls forward from rank_current.
        app(ContestScoringService::class)->scoreInterim($s['contest']);

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                ->where('standings.0.rank', 1)
                ->where('standings.0.rank_previous', 1)
        );
    }

    // ────────────── Top-10 + viewer payload ────────────────────────────────

    public function test_standings_are_limited_to_top_ten(): void
    {
        $s = $this->baseScenario();
        $s['contest']->update(['status' => 'underway']);

        // Add 13 more accepted entries (2 from base, 13 here = 15 total).
        for ($i = 0; $i < 13; $i++) {
            $u = $this->makeUser("Extra {$i}");
            ContestEntry::create([
                'contest_id' => $s['contest']->id,
                'user_id' => $u->id,
                'status' => 'accepted',
                'joined_at' => now()->subMinutes(20 - $i),
            ]);
        }

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                ->has('standings', 10)
                ->where('total_entries', 15)
        );
    }

    public function test_viewer_entry_is_null_when_not_authenticated(): void
    {
        $s = $this->baseScenario();
        $s['contest']->update(['status' => 'underway']);

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                ->where('viewer_entry', null)
                ->where('viewer_in_top', false)
        );
    }

    public function test_viewer_in_top_does_not_get_duplicate_entry(): void
    {
        $s = $this->baseScenario();
        $s['contest']->update(['status' => 'underway']);

        $viewer = User::find($s['entries'][0]->user_id);

        $response = $this->actingAs($viewer)
            ->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                ->where('viewer_in_top', true)
                ->where('viewer_entry', null)
                ->has('standings', 2)
        );
    }

    public function test_viewer_below_top_ten_gets_a_pinned_entry(): void
    {
        $s = $this->baseScenario();
        $s['contest']->update(['status' => 'underway']);

        // 12 more accepted entries so the viewer's original entry is
        // pushed out of the top 10.
        for ($i = 0; $i < 12; $i++) {
            $u = $this->makeUser("Extra {$i}");
            ContestEntry::create([
                'contest_id' => $s['contest']->id,
                'user_id' => $u->id,
                'status' => 'accepted',
                'joined_at' => now()->subMinutes(30 - $i),
            ]);
        }

        // Re-rank: entries with earlier joined_at come first when scores tie.
        // Alice (base entry 0) was joined_at -10m; the extras are -30m to -18m.
        // Move Alice to the back so she's outside top 10.
        $s['entries'][0]->update(['joined_at' => now()]);

        $viewer = User::find($s['entries'][0]->user_id);

        $response = $this->actingAs($viewer)
            ->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                ->where('viewer_in_top', false)
                ->has('viewer_entry')
                ->has('standings', 10)
        );
    }

    public function test_per_leg_stats_appear_on_each_leg(): void
    {
        $s = $this->baseScenario();
        $this->resolveLeg($s['legs'][0], 'Home');
        $this->resolveLeg($s['legs'][1], 'Over 2.5');

        $this->submitPicks($s['entries'][0], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);
        $this->submitPicks($s['entries'][1], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Under 2.5'],
        ]);

        $s['contest']->update(['status' => 'underway']);
        app(\App\Services\ContestScoringService::class)->scoreInterim($s['contest']);

        $response = $this->get("/contests/{$s['contest']->uuid}/results");

        $response->assertInertia(
            fn($page) => $page
                // Leg 0: both picked Home, both correct.
                ->where('contest.legs.0.stats.total_picks', 2)
                ->where('contest.legs.0.stats.correct_picks', 2)
                // Leg 1: split 1 correct / 1 incorrect.
                ->where('contest.legs.1.stats.total_picks', 2)
                ->where('contest.legs.1.stats.correct_picks', 1)
        );
    }

    // ────────────── Search endpoint ────────────────────────────────────────

    public function test_search_finds_entry_by_user_code(): void
    {
        $s = $this->settledScenario();

        $code = $s['entries'][0]->user->code;

        $response = $this->getJson("/contests/{$s['contest']->uuid}/entries/search?code={$code}");

        $response->assertOk();
        $response->assertJsonPath('entry.code', $code);
        $response->assertJsonPath('entry.correct', 2);
    }

    public function test_search_returns_null_for_unknown_code(): void
    {
        $s = $this->settledScenario();

        $response = $this->getJson("/contests/{$s['contest']->uuid}/entries/search?code=NOPE-XXXX-YYY");

        $response->assertOk();
        $response->assertJsonPath('entry', null);
    }

    public function test_search_returns_null_for_blank_code(): void
    {
        $s = $this->settledScenario();

        $response = $this->getJson("/contests/{$s['contest']->uuid}/entries/search?code=");

        $response->assertOk();
        $response->assertJsonPath('entry', null);
    }

    public function test_search_does_not_leak_entries_from_other_contests(): void
    {
        $s1 = $this->settledScenario();
        $s2 = $this->settledScenario();

        // The user from s1's first entry does not participate in s2.
        $code = $s1['entries'][0]->user->code;

        $response = $this->getJson("/contests/{$s2['contest']->uuid}/entries/search?code={$code}");

        $response->assertOk();
        $response->assertJsonPath('entry', null);
    }

    // ────────────── Leg picks endpoint ─────────────────────────────────────

    public function test_leg_picks_returns_summary_and_paginated_list(): void
    {
        $s = $this->settledScenario();

        $response = $this->getJson("/contests/{$s['contest']->uuid}/legs/{$s['legs'][0]->id}/picks");

        $response->assertOk();
        // Both entries picked Home; both were correct.
        $response->assertJsonPath('summary.total_picks', 2);
        $response->assertJsonPath('summary.correct_picks', 2);
        $response->assertJsonCount(1, 'summary.breakdown');
        $response->assertJsonPath('summary.breakdown.0.selection', 'Home');
        $response->assertJsonPath('summary.breakdown.0.count', 2);
        $response->assertJsonPath('summary.breakdown.0.is_correct', true);

        $response->assertJsonCount(2, 'picks.data');
        $response->assertJsonPath('picks.current_page', 1);
        $response->assertJsonPath('picks.total', 2);
        $response->assertJsonPath('picks.last_page', 1);
    }

    public function test_leg_picks_pagination_respects_per_page(): void
    {
        $s = $this->settledScenario();

        // Add 25 more accepted entries, all picking Home on leg 0.
        for ($i = 0; $i < 25; $i++) {
            $u = $this->makeUser("Extra {$i}");
            $entry = ContestEntry::create([
                'contest_id' => $s['contest']->id,
                'user_id' => $u->id,
                'status' => 'accepted',
                'joined_at' => now(),
            ]);
            ContestPick::create([
                'contest_entry_id' => $entry->id,
                'contest_leg_id' => $s['legs'][0]->id,
                'selection' => 'Home',
                'odds_at_pick' => 2.50,
                'status' => 'pending',
            ]);
        }

        $response = $this->getJson(
            "/contests/{$s['contest']->uuid}/legs/{$s['legs'][0]->id}/picks?page=1&per_page=10"
        );

        $response->assertOk();
        $response->assertJsonCount(10, 'picks.data');
        $response->assertJsonPath('picks.per_page', 10);
        $response->assertJsonPath('picks.total', 27);
        $response->assertJsonPath('picks.last_page', 3);
    }

    public function test_leg_picks_rejects_leg_from_another_contest(): void
    {
        $s1 = $this->settledScenario();
        $s2 = $this->settledScenario();

        $response = $this->getJson(
            "/contests/{$s1['contest']->uuid}/legs/{$s2['legs'][0]->id}/picks"
        );

        $response->assertNotFound();
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
        $contest = Contest::withoutHostGuard(fn () => Contest::create([
            'host_id' => $host->id,
            'name' => 'Test Contest',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]));

        $league = $this->makeLeague();
        $home = $this->makeTeam('Home FC', 900001);
        $away = $this->makeTeam('Away FC', 900002);

        $fx1 = $this->makeFixture($league, $home, $away);
        $fx2 = $this->makeFixture($league, $home, $away);

        $market1 = $this->makeMarket(1, 'Match Winner');
        $market2 = $this->makeMarket(2, 'Over/Under');

        $legs = [
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx1->id,
                'market_id' => $market1->id,
                'status' => 'pending',
            ]),
            ContestLeg::create([
                'contest_id' => $contest->id,
                'fixture_id' => $fx2->id,
                'market_id' => $market2->id,
                'status' => 'pending',
            ]),
        ];

        $userA = $this->makeUser('Alice');
        $userB = $this->makeUser('Bob');

        $entryA = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $userA->id,
            'status' => 'accepted',
            'joined_at' => now()->subMinutes(10),
        ]);
        $entryB = ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $userB->id,
            'status' => 'accepted',
            'joined_at' => now()->subMinutes(5),
        ]);

        return [
            'host' => $host,
            'contest' => $contest,
            'entries' => [$entryA, $entryB],
            'legs' => $legs,
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
                'contest_leg_id' => $leg->id,
                'selection' => $selection,
                'odds_at_pick' => $odd->odd,
                'status' => 'pending',
            ]);
        }
    }

    private function resolveLeg(ContestLeg $leg, string $result): void
    {
        $leg->update([
            'status' => 'won',
            'result_selections' => [$result],
            'resolved_at' => now(),
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
                'sport_id' => 1,
                'name' => 'Test League',
                'country_id' => 1,
                'country' => 'Test Country',
                'season' => 2026,
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
            'id_on_api' => 900000 + random_int(1, 99999),
            'date' => now()->addHours(2)->toDateTimeString(),
            'timestamp' => now()->addHours(2)->timestamp,
            'status_short' => 'NS',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.50, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 3.20, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Over 2.5', 'odd' => 1.85, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fixture->id, 'market_id' => 2, 'value' => 'Under 2.5', 'odd' => 2.05, 'status' => 'pending']);

        return $fixture;
    }

    private function makeMarket(int $id, string $name): Market
    {
        return Market::firstOrCreate(['id' => $id], ['name' => $name]);
    }
    private function underwayScenario(): array
    {
        $s = $this->baseScenario();

        // One leg resolved, one still pending.
        $this->resolveLeg($s['legs'][0], 'Home');

        $this->submitPicks($s['entries'][0], [
            [$s['legs'][0], 'Home'],
            [$s['legs'][1], 'Over 2.5'],
        ]);

        // Contest status is 'underway' per Phase C.
        $s['contest']->update(['status' => 'underway']);

        app(ContestScoringService::class)->scoreInterim($s['contest']);

        return $s;
    }
}