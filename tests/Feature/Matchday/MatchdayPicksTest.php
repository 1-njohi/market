<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchdayPicksTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSeed = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['marketplace.matchday.seasons' => ['2026-27']]);
    }

    // ────────────── Show ─────────────────────────────────────────────────

    public function test_matchday_contest_view_marks_is_matchday(): void
    {
        [$user, $contest] = $this->scenario();

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contests/Picks')
            ->where('contest.is_matchday', true)
            ->where('contest.matchday_number', 7)
        );
    }

    public function test_regular_contest_view_marks_is_matchday_false(): void
    {
        [$user, $contest] = $this->scenario(isMatchday: false);

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertInertia(fn ($page) => $page
            ->where('contest.is_matchday', false)
        );
    }

    public function test_matchday_view_exposes_locks_at(): void
    {
        [$user, $contest] = $this->scenario();

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertInertia(fn ($page) => $page
            ->has('contest.locks_at')
        );
    }

    public function test_regular_contest_view_has_null_locks_at(): void
    {
        [$user, $contest] = $this->scenario(isMatchday: false);

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertInertia(fn ($page) => $page
            ->where('contest.locks_at', null)
        );
    }

    // ────────────── Submit ───────────────────────────────────────────────

    public function test_matchday_picks_submit_redirects_to_matchday_dashboard(): void
    {
        [$user, $contest, $legs] = $this->scenario();

        $response = $this->actingAs($user)
            ->post("/contests/{$contest->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $legs[0]->id, 'selection' => 'Home'],
                    ['leg_id' => $legs[1]->id, 'selection' => 'Away'],
                ],
            ]);

        $response->assertRedirect('/matchday');
        $this->assertSame(2, ContestPick::count());
    }

    public function test_regular_picks_submit_redirects_back_to_picks_page(): void
    {
        [$user, $contest, $legs] = $this->scenario(isMatchday: false);

        $response = $this->actingAs($user)
            ->post("/contests/{$contest->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $legs[0]->id, 'selection' => 'Home'],
                ],
            ]);

        $response->assertRedirect("/contests/{$contest->uuid}/picks");
    }

    public function test_matchday_lock_is_enforced_on_submit(): void
    {
        [$user, $contest, $legs] = $this->scenario(locksAt: now()->subHour());

        $response = $this->actingAs($user)
            ->post("/contests/{$contest->uuid}/picks", [
                'picks' => [
                    ['leg_id' => $legs[0]->id, 'selection' => 'Home'],
                ],
            ]);

        $response->assertSessionHasErrors('picks');
        $this->assertSame(0, ContestPick::count());
    }

    // ────────────── Sub-league context ───────────────────────────────────

    public function test_matchday_view_includes_sub_league_context(): void
    {
        [$user, $contest] = $this->scenario();

        // Give the user a sub-league for the season.
        $sub = SubLeague::forceCreate([
            'season'      => '2026-27',
            'name'        => 'Sunday Crew',
            'owner_id'    => $user->id,
            'invite_code' => strtoupper(Str::random(6)),
            'max_members' => 20,
        ]);
        SubLeagueMember::forceCreate([
            'sub_league_id' => $sub->id,
            'user_id'       => $user->id,
            'joined_at'     => now(),
        ]);

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertInertia(fn ($page) => $page
            ->has('sub_leagues', 1)
            ->where('sub_leagues.0.name', 'Sunday Crew')
        );
    }

    public function test_regular_contest_view_omits_sub_league_context(): void
    {
        [$user, $contest] = $this->scenario(isMatchday: false);

        $response = $this->actingAs($user)
            ->get("/contests/{$contest->uuid}/picks");

        $response->assertInertia(fn ($page) => $page
            ->has('sub_leagues', 0)
        );
    }

    // ────────────── Helpers ──────────────────────────────────────────────

    /**
     * @return array{0: User, 1: Contest, 2: array<int, ContestLeg>}
     */
    private function scenario(
        bool $isMatchday = true,
        ?\Carbon\CarbonInterface $locksAt = null,
    ): array {
        $admin = User::factory()->create(['name' => 'Admin']);
        $user  = User::factory()->create();

        $locksAt = $locksAt ?? now()->addHours(3);

        $contest = Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid'              => (string) Str::uuid(),
            'host_id'           => $admin->id,
            'name'              => $isMatchday ? 'Matchday 7' : 'Sunday Crew Contest',
            'visibility'        => 'private',
            'status'            => 'open',
            'is_matchday'       => $isMatchday,
            'season'            => $isMatchday ? '2026-27' : null,
            'league_code'       => $isMatchday ? 'EPL' : null,
            'matchday_number'   => $isMatchday ? 7 : null,
            'locks_at'          => $isMatchday ? $locksAt : null,
            'entry_deadline_at' => $locksAt,
            'starts_at'         => $locksAt,
            'ends_at'           => $locksAt->copy()->addHours(3),
        ]));

        ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id'    => $user->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        $legs = $this->makeLegs($contest, 3);

        return [$user, $contest, $legs];
    }

    /**
     * @return array<int, ContestLeg>
     */
    private function makeLegs(Contest $contest, int $count): array
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 900100],
            [
                'sport_id'   => 1,
                'name'       => 'Test',
                'country_id' => 1,
                'country'    => 'KE',
                'season'     => 2026,
            ],
        );

        $market = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        $legs = [];
        for ($i = 0; $i < $count; $i++) {
            $seed = self::$fixtureSeed++;
            $home = Team::firstOrCreate(
                ['id_on_api' => 700000 + $seed * 2],
                ['name' => "H{$seed}"],
            );
            $away = Team::firstOrCreate(
                ['id_on_api' => 700001 + $seed * 2],
                ['name' => "A{$seed}"],
            );

            $fixture = Fixture::forceCreate([
                'id_on_api'    => 710000 + $seed,
                'date'         => now()->addDay(),
                'timestamp'    => now()->addDay()->timestamp,
                'status_short' => 'NS',
                'league_id'    => $league->id,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'home_winner'  => false,
                'settled'      => false,
            ]);

            \App\Models\Odd::create([
                'fixture_id' => $fixture->id,
                'market_id'  => 1,
                'value'      => 'Home',
                'odd'        => 2.50,
                'status'     => 'pending',
            ]);
            \App\Models\Odd::create([
                'fixture_id' => $fixture->id,
                'market_id'  => 1,
                'value'      => 'Away',
                'odd'        => 3.20,
                'status'     => 'pending',
            ]);

            $legs[] = ContestLeg::forceCreate([
                'contest_id' => $contest->id,
                'fixture_id' => $fixture->id,
                'market_id'  => $market->id,
                'status'     => 'pending',
            ]);
        }

        return $legs;
    }
}