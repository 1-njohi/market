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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSeed = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketplace.matchday.seasons' => ['2026-27', '2027-28'],
        ]);
    }

    // ────────────── Auth ─────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/matchday')->assertRedirect('/login');
    }

    // ────────────── Sub-leagues ──────────────────────────────────────────

    public function test_dashboard_lists_users_sub_leagues_for_active_season(): void
    {
        $user = $this->makeUser('Alice');

        $this->makeSubLeague('2026-27', $user, 'Sunday Crew');
        $this->makeSubLeague('2026-27', $user, 'Work Pals');
        $this->makeSubLeague('2027-28', $user, 'Next Season');   // excluded

        $response = $this->actingAs($user)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->component('Matchday/Dashboard')
            ->where('season', '2026-27')
            ->has('sub_leagues', 2)
        );
    }

    public function test_dashboard_returns_empty_sub_leagues_for_user_with_none(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->has('sub_leagues', 0)
        );
    }

    // ────────────── Current matchday resolution ─────────────────────────

    public function test_dashboard_returns_null_current_matchday_when_none_exists(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->where('current_matchday', null)
        );
    }


    public function test_dashboard_excludes_matchdays_from_other_seasons(): void
    {
        $user = $this->makeUser();

        $this->makeMatchdayContest(season: '2027-28', number: 1, locksAt: now()->addHours(3));
        $this->makeMatchdayContest(season: '2026-27', number: 7, locksAt: now()->addHours(3));

        $response = $this->actingAs($user)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->where('current_matchday.matchday_number', 7)
        );
    }

    // ────────────── Entry state ──────────────────────────────────────────

    public function test_dashboard_includes_pick_counts_for_current_matchday(): void
    {
        $user = $this->makeUser();
        $matchday = $this->makeMatchdayContest(
            season: '2026-27',
            number: 7,
            locksAt: now()->addHours(3),
        );

        $legs = $this->makeLegs($matchday, 3);
        $entry = $this->makeEntry($matchday, $user);
        $this->makePick($entry, $legs[0]);
        $this->makePick($entry, $legs[1]);

        $response = $this->actingAs($user)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->where('current_matchday.picks_required', 3)
            ->where('current_matchday.picks_submitted', 2)
        );
    }

    // ────────────── Leaderboard preview ──────────────────────────────────

    public function test_dashboard_includes_leaderboard_preview_per_sub_league(): void
    {
        $owner = $this->makeUser('Owner');
        $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        // A settled matchday contest with an entry for the owner.
        // score_correct = 3 → 3 points on the leaderboard.
        $matchday = $this->makeMatchdayContest(
            season: '2026-27',
            number: 6,
            locksAt: now()->subDay(),
            status: 'settled',
        );
        $this->makeEntry($matchday, $owner, scoreCorrect: 3);

        $response = $this->actingAs($owner)->get('/matchday');

        $response->assertInertia(fn ($page) => $page
            ->has('sub_leagues', 1)
            ->has('sub_leagues.0.leaderboard_preview', 1)
            ->where('sub_leagues.0.leaderboard_preview.0.points', 3)
            ->where('sub_leagues.0.leaderboard_preview.0.rank', 1)
            ->where('sub_leagues.0.leaderboard_preview.0.user_id', $owner->id)
        );
    }

    // ────────────── Helpers ──────────────────────────────────────────────

    private function makeUser(string $name = 'User'): User
    {
        return User::factory()->create([
            'name' => $name,
            'code' => strtoupper(Str::random(8)),
        ]);
    }

    private function makeSubLeague(string $season, User $owner, string $name): SubLeague
    {
        $sub = SubLeague::forceCreate([
            'season'      => $season,
            'name'        => $name,
            'owner_id'    => $owner->id,
            'invite_code' => strtoupper(Str::random(6)),
            'max_members' => 20,
        ]);

        SubLeagueMember::forceCreate([
            'sub_league_id' => $sub->id,
            'user_id'       => $owner->id,
            'joined_at'     => now(),
        ]);

        return $sub;
    }

    private function makeMatchdayContest(
        string $season,
        int $number,
        \Carbon\CarbonInterface $locksAt,
        string $status = 'open',
    ): Contest {
        return Contest::withoutHostGuard(fn () => Contest::forceCreate([
            'uuid'              => (string) Str::uuid(),
            'host_id'           => $this->makeUser('Admin')->id,
            'name'              => "Matchday {$number}",
            'visibility'        => 'private',
            'status'            => $status,
            'is_matchday'       => true,
            'season'            => $season,
            'league_code'       => 'EPL',
            'matchday_number'   => $number,
            'locks_at'          => $locksAt,
            'entry_deadline_at' => $locksAt,
            'starts_at'         => $locksAt,
            'ends_at'           => $locksAt->copy()->addHours(3),
        ]));
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
                ['id_on_api' => 800000 + $seed * 2],
                ['name' => "H{$seed}"],
            );
            $away = Team::firstOrCreate(
                ['id_on_api' => 800001 + $seed * 2],
                ['name' => "A{$seed}"],
            );

            $fixture = Fixture::forceCreate([
                'id_on_api'    => 810000 + $seed,
                'date'         => now()->addDay(),
                'timestamp'    => now()->addDay()->timestamp,
                'status_short' => 'NS',
                'league_id'    => $league->id,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'home_winner'  => false,
                'settled'      => false,
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

    private function makeEntry(Contest $contest, User $user, int $scoreCorrect = 0): ContestEntry
    {
        return ContestEntry::forceCreate([
            'contest_id'    => $contest->id,
            'user_id'       => $user->id,
            'status'        => 'accepted',
            'score_correct' => $scoreCorrect,
            'joined_at'     => now(),
        ]);
    }

    private function makePick(ContestEntry $entry, ContestLeg $leg): void
    {
        ContestPick::forceCreate([
            'contest_entry_id' => $entry->id,
            'contest_leg_id'   => $leg->id,
            'selection'        => 'Home',
            'odds_at_pick'     => 2.00,
            'status'           => 'pending',
        ]);
    }
}