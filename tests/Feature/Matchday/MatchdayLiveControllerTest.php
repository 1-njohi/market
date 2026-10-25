<?php

namespace Tests\Feature\Matchday;

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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchdayLiveControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $this->travelTo(Carbon::parse('2026-08-15 15:00:00'));

        League::firstOrCreate(
            ['id' => 39],
            [
                'id_on_api' => 39, 'sport_id' => 1, 'name' => 'Premier League',
                'country_id' => 1, 'country' => 'England', 'season' => 2026,
            ],
        );
    }

    public function test_returns_empty_payload_when_user_has_no_active_matchdays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('matchday.live'));

        $response->assertOk();
        $response->assertJson(['data' => []]);
    }

    public function test_returns_live_rows_for_underway_matchdays(): void
    {
        $user = User::factory()->create();

        $contest = Contest::create([
            'host_id'           => User::factory()->create(['is_admin' => true])->id,
            'name'              => 'MD1 EPL',
            'visibility'        => 'public',
            'status'            => 'underway',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => 1,
            'locks_at'          => now()->subHours(2),
            'entry_deadline_at' => now()->subHours(2),
            'starts_at'         => now()->subHours(1),
            'ends_at'           => now()->addHours(3),
        ]);

        ContestEntry::create([
            'contest_id'    => $contest->id,
            'user_id'       => $user->id,
            'status'        => 'accepted',
            'rank_current'  => 3,
            'rank_previous' => 5,
            'score_correct' => 0,
            'joined_at'     => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('matchday.live'));

        $response->assertOk();
        $response->assertJsonPath('data.0.uuid', $contest->uuid);
        $response->assertJsonPath('data.0.rank_current', 3);
        $response->assertJsonPath('data.0.rank_previous', 5);
        $response->assertJsonPath('data.0.rank_delta', 2);
    }

    public function test_response_sets_etag_and_returns_304_when_unchanged(): void
    {
        $user = User::factory()->create();

        $contest = Contest::create([
            'host_id'           => User::factory()->create(['is_admin' => true])->id,
            'name'              => 'MD1',
            'visibility'        => 'public',
            'status'            => 'underway',
            'is_matchday'       => true,
            'season'            => '2026-27',
            'league_code'       => 'EPL',
            'matchday_number'   => 1,
            'locks_at'          => now()->subHours(2),
            'entry_deadline_at' => now()->subHours(2),
            'starts_at'         => now()->subHours(1),
            'ends_at'           => now()->addHours(3),
        ]);

        ContestEntry::create([
            'contest_id'    => $contest->id,
            'user_id'       => $user->id,
            'status'        => 'accepted',
            'score_correct' => 0,
            'joined_at'     => now(),
        ]);

        $first = $this->actingAs($user)->getJson(route('matchday.live'));
        $first->assertOk();
        $etag = $first->headers->get('ETag');
        $this->assertNotNull($etag);

        $second = $this->actingAs($user)
            ->withHeaders(['If-None-Match' => $etag])
            ->getJson(route('matchday.live'));

        $second->assertStatus(304);
    }

    public function test_returns_401_for_guest(): void
    {
        $this->getJson(route('matchday.live'))->assertStatus(401);
    }
}