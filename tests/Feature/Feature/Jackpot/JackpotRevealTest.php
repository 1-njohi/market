<?php

namespace Tests\Feature\Jackpot;

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
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JackpotRevealTest extends TestCase
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

        League::firstOrCreate(['id' => 39], [
            'id_on_api' => 39,
            'sport_id' => 1,
            'name' => 'Premier League',
            'country_id' => 1,
            'country' => 'England',
            'season' => 2026,
        ]);
    }

    private function jackpot(): Contest
    {
        $host = User::factory()->create(['is_admin' => true]);

        return Contest::create([
            'host_id' => $host->id,
            'name' => 'Jackpot',
            'visibility' => 'public',
            'type' => 'jackpot',
            'status' => 'open',
            'locks_at' => now()->addDay(),
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);
    }

    private function legWithOptions(Contest $contest): ContestLeg
    {
        $home = Team::firstOrCreate(['id_on_api' => 900000 + random_int(1, 99999)], ['name' => 'H']);
        $away = Team::firstOrCreate(['id_on_api' => 910000 + random_int(1, 99999)], ['name' => 'A']);

        $fx = Fixture::create([
            'id_on_api' => 900000 + random_int(1, 999999),
            'date' => now()->addDay(),
            'timestamp' => now()->addDay()->timestamp,
            'status_short' => 'NS',
            'league_id' => 39,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);

        Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Home', 'odd' => 2.00, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Draw', 'odd' => 3.00, 'status' => 'pending']);
        Odd::create(['fixture_id' => $fx->id, 'market_id' => 1, 'value' => 'Away', 'odd' => 4.00, 'status' => 'pending']);

        return ContestLeg::create([
            'contest_id' => $contest->id,
            'fixture_id' => $fx->id,
            'market_id' => 1,
            'status' => 'pending',
        ]);
    }

    private function enter(User $user, Contest $contest): ContestEntry
    {
        return ContestEntry::create([
            'contest_id' => $contest->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'joined_at' => now(),
        ]);
    }

    private function submitPick(User $user, Contest $contest, ContestLeg $leg, string $selection)
    {
        return $this->actingAs($user)->post(
            route('contests.picks.store', $contest->uuid),
            ['picks' => [['leg_id' => $leg->id, 'selection' => $selection]]],
        );
    }

    public function test_picking_a_jackpot_leg_sets_revealed_at(): void
    {
        $contest = $this->jackpot();
        $leg = $this->legWithOptions($contest);
        $user = User::factory()->create();
        $entry = $this->enter($user, $contest);

        $this->submitPick($user, $contest, $leg, 'Home')->assertSessionHasNoErrors();

        $pick = ContestPick::where('contest_entry_id', $entry->id)
            ->where('contest_leg_id', $leg->id)
            ->first();

        $this->assertNotNull($pick);
        $this->assertNotNull($pick->revealed_at);
    }

    public function test_cannot_update_pick_after_revealed(): void
    {
        $contest = $this->jackpot();
        $leg = $this->legWithOptions($contest);
        $user = User::factory()->create();
        $entry = $this->enter($user, $contest);

        $this->submitPick($user, $contest, $leg, 'Home')->assertSessionHasNoErrors();

        // Attempt to change to Draw. Should fail because pick is locked.
        $response = $this->actingAs($user)->post(
            route('contests.picks.store', $contest->uuid),
            ['picks' => [['leg_id' => $leg->id, 'selection' => 'Draw']]],
        );

        $response->assertSessionHasErrors();

        $this->assertSame(
            'Home',
            ContestPick::where('contest_entry_id', $entry->id)
                ->where('contest_leg_id', $leg->id)
                ->value('selection'),
        );
    }

    public function test_picks_page_returns_distribution_only_for_revealed_legs(): void
    {
        $contest = $this->jackpot();
        $legA = $this->legWithOptions($contest);
        $legB = $this->legWithOptions($contest);
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $eViewer = $this->enter($viewer, $contest);
        $eOther = $this->enter($other, $contest);

        // Viewer picked legA only. Other picked both, both legs "Home".
        ContestPick::withoutMatchdayLock(fn() => ContestPick::create([
            'contest_entry_id' => $eViewer->id,
            'contest_leg_id' => $legA->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'status' => 'pending',
            'revealed_at' => now(),
        ]));
        ContestPick::withoutMatchdayLock(fn() => ContestPick::create([
            'contest_entry_id' => $eOther->id,
            'contest_leg_id' => $legA->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'status' => 'pending',
            'revealed_at' => now(),
        ]));
        ContestPick::withoutMatchdayLock(fn() => ContestPick::create([
            'contest_entry_id' => $eOther->id,
            'contest_leg_id' => $legB->id,
            'selection' => 'Home',
            'odds_at_pick' => 2.00,
            'status' => 'pending',
            'revealed_at' => now(),
        ]));

        $response = $this->actingAs($viewer)->get(
            route('contests.picks', $contest->uuid)
        );

        $response->assertInertia(
            fn($page) => $page
                ->has("contest.legs")
        );

        $response = $this->actingAs($viewer)->get(route('contests.picks', $contest->uuid));
        $response->assertOk();

        // Inertia serializes props into a JSON payload; we can get it back.
        $page = json_decode($response->content(), true) ?: $response->viewData('page');
        $props = $page['props'] ?? [];

        $legs = $props['contest']['legs'] ?? [];
        $legById = collect($legs)->keyBy('id');

        // LegA: viewer picked → distribution present.
        $this->assertArrayHasKey('distribution', $legById[$legA->id]);
        $this->assertSame(100, $legById[$legA->id]['distribution']['Home']);

        // LegB: viewer hasn't picked → no distribution.
        $this->assertArrayNotHasKey('distribution', $legById[$legB->id]);
    }
}