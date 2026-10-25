<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchdayDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));
    }

    private function settledMatchday(int $number): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => "MD{$number}",
            'visibility' => 'public',
            'status' => 'settled',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => $number,
            'locks_at' => now()->subDays(2),
            'entry_deadline_at' => now()->subDays(2),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(1),
            'settled_at' => now()->subDay(),
        ]);
    }

    private function entry(User $u, Contest $c, int $score): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $c->id,
            'user_id' => $u->id,
            'status' => 'accepted',
            'score_correct' => $score,
            'score_units' => 0,
            'joined_at' => now(),
        ]);
    }

    private function attachToSubLeague(User $user): void
    {
        $owner = User::factory()->create();
        $sub = app(SubLeagueService::class)->create($owner, 'Crew', '2026-27');

        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);
    }

    public function test_summary_prop_is_null_when_user_has_no_settled_matchdays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/Dashboard')
                ->where('summary', null)
        );
    }

    public function test_summary_prop_is_populated_after_a_matchday_settles(): void
    {
        $user = User::factory()->create();
        $this->attachToSubLeague($user);

        $md1 = $this->settledMatchday(1);
        $this->entry($user, $md1, 3);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/Dashboard')
                ->where('summary.matchdays_played', 1)
                ->where('summary.matchdays_settled', 1)
                ->has('summary.pick_accuracy')
                ->has('summary.points_per_matchday')
        );
    }

    public function test_open_matchdays_prop_includes_every_open_contest(): void
    {
        $user = User::factory()->create();
        $this->attachToSubLeague($user);

        $host = User::factory()->create(['is_admin' => true]);

        // Two simultaneous open matchdays in different leagues.
        $epl = Contest::create([
            'host_id' => $host->id,
            'name' => 'EPL MD5',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 5,
            'locks_at' => now()->addDays(2),
            'entry_deadline_at' => now()->addDays(2),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(3),
        ]);

        $ucl = Contest::create([
            'host_id' => $host->id,
            'name' => 'UCL MD3',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'UCL',
            'matchday_number' => 3,
            'locks_at' => now()->addDays(4),
            'entry_deadline_at' => now()->addDays(4),
            'starts_at' => now()->addDays(4),
            'ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/Dashboard')
                ->has('open_matchdays', 2)
                ->where('open_matchdays.0.uuid', $epl->uuid)
                ->where('open_matchdays.1.uuid', $ucl->uuid)
        );
    }

    public function test_open_matchdays_is_ordered_by_lock_time(): void
    {
        $user = User::factory()->create();
        $this->attachToSubLeague($user);

        $host = User::factory()->create(['is_admin' => true]);

        // Create in reverse lock-time order to prove the sort.
        $late = Contest::create([
            'host_id' => $host->id,
            'name' => 'Late',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'EPL',
            'matchday_number' => 2,
            'locks_at' => now()->addDays(5),
            'entry_deadline_at' => now()->addDays(5),
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(6),
        ]);

        $early = Contest::create([
            'host_id' => $host->id,
            'name' => 'Early',
            'visibility' => 'public',
            'status' => 'open',
            'is_matchday' => true,
            'season' => '2026-27',
            'league_code' => 'UCL',
            'matchday_number' => 1,
            'locks_at' => now()->addDay(),
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertInertia(
            fn($page) => $page
                ->where('open_matchdays.0.uuid', $early->uuid)
                ->where('open_matchdays.1.uuid', $late->uuid)
        );
    }

    public function test_open_matchdays_prop_is_empty_when_none_open(): void
    {
        $user = User::factory()->create();
        $this->attachToSubLeague($user);

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertInertia(
            fn($page) => $page
                ->has('open_matchdays', 0)
        );
    }

    public function test_league_options_prop_is_derived_from_config(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Matchday/Dashboard')
                ->where('league_options', [
                    ['code' => 'EPL', 'label' => 'Premier League'],
                    ['code' => 'UCL', 'label' => 'UEFA Champions League'],
                ])
        );
    }

    public function test_league_options_grows_when_config_grows(): void
    {
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
            'LALIGA' => 140,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('matchday.dashboard'));

        $response->assertInertia(
            fn($page) => $page
                ->where('league_options', [
                    ['code' => 'EPL', 'label' => 'Premier League'],
                    ['code' => 'UCL', 'label' => 'UEFA Champions League'],
                    ['code' => 'LALIGA', 'label' => 'LaLiga'],
                ])
        );
    }
}