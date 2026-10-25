<?php

namespace Tests\Feature\Admin;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchdayControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSeed = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketplace.matchday.seasons' => ['2026-27', '2027-28'],
            'marketplace.matchday.lock_offset_minutes' => 10,
            'marketplace.matchday.supported_league_codes' => [
                'EPL' => 39,
                'UCL' => 2,
            ],
        ]);
    }

    // ────────────── Access control ───────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/matchdays')->assertRedirect('/login');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/matchdays')
            ->assertForbidden();
    }

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/matchdays');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('admin/matchdays/Index')
                ->has('matchdays')
                ->has('seasons')
                ->has('leagues')
        );
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/matchdays/create');

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('admin/matchdays/Create')
                ->has('seasons')
                ->has('leagues')
        );
    }

    // ────────────── Store ────────────────────────────────────────────────

    public function test_admin_can_create_a_matchday(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'description' => null,
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertRedirect();

        $contest = Contest::where('is_matchday', true)->first();
        $this->assertNotNull($contest);
        $this->assertSame('2026-27', $contest->season);
        $this->assertSame('EPL', $contest->league_code);
        $this->assertSame(1, $contest->matchday_number);
        $this->assertSame('Matchday 1', $contest->name);
        $this->assertNotNull($contest->locks_at);
        $this->assertSame(5, ContestLeg::where('contest_id', $contest->id)->count());
    }

    public function test_store_requires_season(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertSessionHasErrors('season');
        $this->assertSame(0, Contest::where('is_matchday', true)->count());
    }

    public function test_store_rejects_season_not_in_config(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2050-51',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertSessionHasErrors('season');
    }

    public function test_store_requires_minimum_five_fixtures(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 4, leagueId: 39);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertSessionHasErrors('fixture_ids');
    }

    public function test_store_rejects_mixed_leagues(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $epl = $this->makeFixtures(count: 3, leagueId: 39);
        $ucl = $this->makeFixtures(count: 2, leagueId: 2);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $epl->pluck('id')->concat($ucl->pluck('id'))->all(),
        ]);

        $response->assertSessionHasErrors('legs');
        $this->assertSame(0, Contest::where('is_matchday', true)->count());
    }

    public function test_store_rejects_unsupported_league(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 999);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertSessionHasErrors('legs');
    }

    public function test_store_rejects_duplicate_matchday(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $this->makeExistingMatchday($admin, number: 1);

        $response = $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Duplicate',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $response->assertSessionHasErrors('matchday_number');
    }

    public function test_store_derives_locks_at_from_earliest_fixture(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Earliest kickoff is +1 day; offset is 10 minutes → locks_at ≈ +1d - 10m.
        $fixtures = collect([
            $this->makeFixture(leagueId: 39, kickoffAt: now()->addDays(3)),
            $this->makeFixture(leagueId: 39, kickoffAt: now()->addDays(1)),
            $this->makeFixture(leagueId: 39, kickoffAt: now()->addDays(2)),
            $this->makeFixture(leagueId: 39, kickoffAt: now()->addDays(4)),
            $this->makeFixture(leagueId: 39, kickoffAt: now()->addDays(5)),
        ]);

        $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $contest = Contest::where('is_matchday', true)->firstOrFail();
        $expected = now()->addDays(1)->subMinutes(10);

        $this->assertTrue(
            $contest->locks_at->diffInSeconds($expected) < 5,
            "Expected locks_at ~{$expected}, got {$contest->locks_at}"
        );
    }

    public function test_store_creates_match_winner_legs_only(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $contest = Contest::where('is_matchday', true)->firstOrFail();
        $legs = ContestLeg::where('contest_id', $contest->id)->get();

        $this->assertCount(5, $legs);
        $legs->each(function ($leg) {
            $this->assertSame(1, $leg->market_id);
        });
    }

    public function test_store_does_not_create_host_entry(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $contest = Contest::where('is_matchday', true)->firstOrFail();

        // The admin is not a player in their own matchday. Members of
        // season sub-leagues are auto-entered separately — but the admin
        // gets no entry unless they belong to a sub-league themselves.
        $this->assertSame(
            0,
            ContestEntry::where('contest_id', $contest->id)
                ->where('user_id', $admin->id)
                ->count()
        );
    }

    public function test_store_auto_enters_sub_league_members(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $sub = SubLeague::forceCreate([
            'season' => '2026-27',
            'name' => 'Sunday Crew',
            'owner_id' => $member->id,
            'invite_code' => 'ABC123',
            'max_members' => 20,
        ]);
        SubLeagueMember::forceCreate([
            'sub_league_id' => $sub->id,
            'user_id' => $member->id,
            'joined_at' => now(),
        ]);

        $fixtures = $this->makeFixtures(count: 5, leagueId: 39);

        $this->actingAs($admin)->post('/admin/matchdays', [
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 1,
            'name' => 'Matchday 1',
            'fixture_ids' => $fixtures->pluck('id')->all(),
        ]);

        $contest = Contest::where('is_matchday', true)->firstOrFail();

        $this->assertSame(1, ContestEntry::where('contest_id', $contest->id)->count());
        $this->assertSame(
            $member->id,
            ContestEntry::where('contest_id', $contest->id)->first()->user_id
        );
    }

    // ────────────── Cancel ───────────────────────────────────────────────

    public function test_admin_can_cancel_matchday(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $contest = $this->makeExistingMatchday($admin);

        $this->actingAs($admin)
            ->post("/admin/matchdays/{$contest->id}/cancel")
            ->assertRedirect();

        $this->assertSame('cancelled', $contest->fresh()->status);
    }

    public function test_non_admin_cannot_cancel_matchday(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $contest = $this->makeExistingMatchday($admin);

        $this->actingAs($user)
            ->post("/admin/matchdays/{$contest->id}/cancel")
            ->assertForbidden();

        $this->assertNotSame('cancelled', $contest->fresh()->status);
    }

    // ────────────── Helpers ──────────────────────────────────────────────

    /**
     * @return Collection<int, Fixture>
     */
    private function makeFixtures(int $count, int $leagueId): Collection
    {
        return collect(range(0, $count - 1))->map(function ($i) use ($leagueId) {
            return $this->makeFixture(
                leagueId: $leagueId,
                kickoffAt: now()->addDays(2)->addHours($i),
            );
        });
    }

    private function makeFixture(int $leagueId, \Carbon\CarbonInterface $kickoffAt): Fixture
    {
        $seed = self::$fixtureSeed++;

        // Leagues are looked up by id, so we can't rely on firstOrCreate
        // (id isn't mass-fillable). Create with an explicit id when
        // missing, using the same id that fixtures.league_id references.
        $league = League::find($leagueId);
        if (!$league) {
            $league = new League([
                'id_on_api' => 900000 + $leagueId,
                'sport_id' => 1,
                'name' => "League {$leagueId}",
                'country_id' => 1,
                'country' => 'Test',
                'season' => 2026,
            ]);
            $league->id = $leagueId;
            $league->save();
        }

        $home = Team::firstOrCreate(
            ['id_on_api' => 700000 + $seed * 2],
            ['name' => "H{$seed}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 700001 + $seed * 2],
            ['name' => "A{$seed}"],
        );

        $fixture = Fixture::forceCreate([
            'id_on_api' => 710000 + $seed,
            'date' => $kickoffAt,
            'timestamp' => $kickoffAt->timestamp,
            'status_short' => 'NS',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner' => false,
            'settled' => false,
        ]);

        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'value' => 'Home',
            'odd' => 2.00,
            'status' => 'pending',
        ]);
        Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => 1,
            'value' => 'Away',
            'odd' => 3.00,
            'status' => 'pending',
        ]);

        return $fixture;
    }
    private function makeExistingMatchday(User $admin, int $number = 1): Contest
    {
        return Contest::withoutHostGuard(fn() => Contest::forceCreate([
            'uuid' => (string) Str::uuid(),
            'host_id' => $admin->id,
            'name' => "Existing Matchday {$number}",
            'visibility' => 'private',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->addDay(),
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
        ]));
    }
}