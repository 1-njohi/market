<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\SubLeague;
use App\Models\SubLeagueMember;
use App\Models\User;
use App\Services\Matchday\SubLeagueService;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubLeagueDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.max_sub_league_members', 20);
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);
        config()->set('marketplace.matchday.league_labels', [
            'EPL' => 'Premier League',
            'UCL' => 'UEFA Champions League',
        ]);

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));
    }

    private function makeSubLeague(User $owner, string $name = 'Crew'): SubLeague
    {
        return app(SubLeagueService::class)->create($owner, $name, '2026-27', ['EPL']);
    }

    private function openMatchday(int $number, string $code = 'EPL'): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id'           => $host->id,
            'name'              => "MD{$number} {$code}",
            'visibility'        => 'public',
            'status'            => 'open',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => $code,
            'matchday_number'   => $number,
            'locks_at'          => now()->addDays(3),
            'entry_deadline_at' => now()->addDays(3),
            'starts_at'         => now()->addDays(3),
            'ends_at'           => now()->addDays(4),
        ]);
    }

    private function settledMatchday(int $number, string $code = 'EPL'): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id'           => $host->id,
            'name'              => "MD{$number} {$code}",
            'visibility'        => 'public',
            'status'            => 'settled',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => $code,
            'matchday_number'   => $number,
            'locks_at'          => now()->subDays(5),
            'entry_deadline_at' => now()->subDays(5),
            'starts_at'         => now()->subDays(5),
            'ends_at'           => now()->subDays(4),
            'settled_at'        => now()->subDays(4),
        ]);
    }

    public function test_member_can_view_detail_page(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $response = $this->actingAs($owner)
            ->get(route('matchday.sub-leagues.show', $sub));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Matchday/SubLeagueDetail')
            ->where('sub_league.id', $sub->id)
            ->where('sub_league.name', 'Crew')
            ->where('sub_league.season', '2026-27')
            ->where('sub_league.is_owner', true)
        );
    }

    public function test_non_member_gets_403(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('matchday.sub-leagues.show', $sub))
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $this->get(route('matchday.sub-leagues.show', $sub))
            ->assertRedirect('/login');
    }

    public function test_members_prop_lists_all_members_with_join_dates(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $m1 = User::factory()->create();
        $m2 = User::factory()->create();

        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id'       => $m1->id,
            'joined_at'     => now()->subDays(10),
        ]);
        SubLeagueMember::create([
            'sub_league_id' => $sub->id,
            'user_id'       => $m2->id,
            'joined_at'     => now()->subDays(3),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('matchday.sub-leagues.show', $sub));

        $response->assertInertia(fn ($page) => $page
            ->has('members', 3)
            ->where('members.0.is_owner', true)
            ->where('members.1.user_id', $m1->id)
            ->where('members.2.user_id', $m2->id)
        );
    }

    public function test_matchdays_prop_lists_upcoming_and_recent_settled(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $upcoming = $this->openMatchday(3);
        $recent   = $this->settledMatchday(2);

        // Out-of-scope: UCL, different season — none of these should appear.
        $this->openMatchday(4, 'UCL');

        $response = $this->actingAs($owner)
            ->get(route('matchday.sub-leagues.show', $sub));

        $response->assertInertia(fn ($page) => $page
            ->has('matchdays', 2)
            // Upcoming sorted before settled.
            ->where('matchdays.0.uuid', $upcoming->uuid)
            ->where('matchdays.0.is_upcoming', true)
            ->where('matchdays.1.uuid', $recent->uuid)
            ->where('matchdays.1.is_upcoming', false)
        );
    }

    public function test_matchdays_prop_includes_viewers_pick_count(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $contest = $this->openMatchday(3);

        ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id'    => $owner->id,
            'status'     => 'accepted',
            'joined_at'  => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('matchday.sub-leagues.show', $sub));

        $response->assertInertia(fn ($page) => $page
            ->where('matchdays.0.is_entered', true)
            ->where('matchdays.0.picks_required', 0)
            ->where('matchdays.0.picks_submitted', 0)
        );
    }

    public function test_leaderboard_prop_uses_from_matchday_filter(): void
    {
        $owner = User::factory()->create();
        $sub   = $this->makeSubLeague($owner);

        $this->settledMatchday(1);
        $this->settledMatchday(2);

        $response = $this->actingAs($owner)
            ->get(route('matchday.sub-leagues.show', $sub) . '?from_matchday=2');

        $response->assertInertia(fn ($page) => $page
            ->where('from_matchday', 2)
        );
    }
}