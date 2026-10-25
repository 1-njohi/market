<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubLeagueLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['marketplace.matchday.seasons' => ['2026-27']]);
    }

    // ────────────── Access control ───────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $this->get("/matchday/sub-leagues/{$sub->id}/leaderboard")
            ->assertRedirect('/login');
    }

    public function test_non_member_cannot_view(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $this->actingAs($other)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard")
            ->assertForbidden();
    }

    public function test_unknown_sub_league_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/matchday/sub-leagues/99999/leaderboard')
            ->assertNotFound();
    }

    // ────────────── Page render ──────────────────────────────────────────

    public function test_member_can_view(): void
    {
        $owner = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $response = $this->actingAs($owner)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard");

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/SubLeagueLeaderboard')
                ->where('sub_league.id', $sub->id)
                ->where('sub_league.name', 'Sunday Crew')
                ->where('sub_league.season', '2026-27')
                ->where('from_matchday', 1)
                ->has('max_matchday')
        );
    }

    public function test_includes_sub_league_leaderboard_rows(): void
    {
        $owner = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $md = $this->makeMatchdayContest('2026-27', 1, 'settled');
        $this->makeEntry($md, $owner, scoreCorrect: 3);

        $response = $this->actingAs($owner)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard");

        $response->assertInertia(
            fn($page) => $page
                ->has('sub_league_leaderboard', 1)
                ->where('sub_league_leaderboard.0.user_id', $owner->id)
                ->where('sub_league_leaderboard.0.points', 3)
                ->where('sub_league_leaderboard.0.rank', 1)
        );
    }

    // ────────────── Filters ──────────────────────────────────────────────

    public function test_from_matchday_filter_scopes_sub_league_leaderboard(): void
    {
        $owner = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $md1 = $this->makeMatchdayContest('2026-27', 1, 'settled');
        $md2 = $this->makeMatchdayContest('2026-27', 2, 'settled');
        $this->makeEntry($md1, $owner, scoreCorrect: 5);
        $this->makeEntry($md2, $owner, scoreCorrect: 7);

        // from_matchday=2 → md1 is excluded, only md2's 7 points count.
        $response = $this->actingAs($owner)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard?from_matchday=2");

        $response->assertInertia(
            fn($page) => $page
                ->where('from_matchday', 2)
                ->where('sub_league_leaderboard.0.points', 7)
        );
    }

    // ────────────── Global leaderboard ───────────────────────────────────

    public function test_includes_global_leaderboard(): void
    {
        $owner = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $md = $this->makeMatchdayContest('2026-27', 1, 'settled');
        $this->makeEntry($md, $owner, scoreCorrect: 3);

        $response = $this->actingAs($owner)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard");

        $response->assertInertia(
            fn($page) => $page
                ->has('global_leaderboard')
                ->where('global_leaderboard.0.user_id', $owner->id)
                ->where('global_leaderboard.0.points', 3)
        );
    }

    // ────────────── Scoping ──────────────────────────────────────────────

    public function test_sub_league_leaderboard_excludes_non_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $sub = $this->makeSubLeague('2026-27', $owner, 'Sunday Crew');

        $md = $this->makeMatchdayContest('2026-27', 1, 'settled');
        $this->makeEntry($md, $owner, scoreCorrect: 3);
        $this->makeEntry($md, $outsider, scoreCorrect: 99);

        $response = $this->actingAs($owner)
            ->get("/matchday/sub-leagues/{$sub->id}/leaderboard");

        // Outsider has 99 points but isn't a member — must not appear.
        $response->assertInertia(
            fn($page) => $page
                ->has('sub_league_leaderboard', 1)
                ->where('sub_league_leaderboard.0.user_id', $owner->id)
        );
    }

    // ────────────── Helpers ──────────────────────────────────────────────

    private function makeSubLeague(string $season, User $owner, string $name): SubLeague
    {
        $sub = SubLeague::forceCreate([
            'season' => $season,
            'name' => $name,
            'owner_id' => $owner->id,
            'invite_code' => strtoupper(Str::random(6)),
            'max_members' => 20,
        ]);

        SubLeagueMember::forceCreate([
            'sub_league_id' => $sub->id,
            'user_id' => $owner->id,
            'joined_at' => now(),
        ]);

        return $sub;
    }

    private function makeMatchdayContest(string $season, int $number, string $status): Contest
    {
        return Contest::withoutHostGuard(fn() => Contest::forceCreate([
            'uuid' => (string) Str::uuid(),
            'host_id' => User::factory()->create()->id,
            'name' => "Matchday {$number}",
            'visibility' => 'private',
            'status' => $status,
            'is_matchday' => true,
            'season' => $season,
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->subDay(),
            'entry_deadline_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHours(3),
        ]));
    }

    private function makeEntry(Contest $contest, User $user, int $scoreCorrect): ContestEntry
    {
        return ContestEntry::forceCreate([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'score_correct' => $scoreCorrect,
            'joined_at' => now(),
        ]);
    }
}