<?php

namespace Tests\Feature\Matchday;

use App\Models\Contest;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMatchdayContestTest extends TestCase
{
    use RefreshDatabase;

    /** Mirrors ContestCreateController::MIN_LEGS. */
    private const MIN_LEGS = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // Contest::booted()->creating runs the host eligibility check
        // against SellerEligibilityService. Matchday tests don't exercise
        // that guard, so bind a permissive stub.
        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        // Freeze "now" so `after:now` on entry_deadline_at and the
        // kickoff dates below are all in the future.
        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));

        // fixtures.league_id has a real FK to leagues.id. Seed the three
        // league rows the tests reference with explicit ids that match
        // config('marketplace.matchday.supported_league_codes').
        foreach ([
            [39, 'Premier League'],
            [2, 'UEFA Champions League'],
            [999, 'Unsupported League'],
        ] as [$id, $name]) {
            League::firstOrCreate(
                ['id' => $id],
                [
                    'id_on_api' => $id,
                    'sport_id' => 1,
                    'name' => $name,
                    'country_id' => 1,
                    'country' => 'Test Country',
                    'season' => 2026,
                ],
            );
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * Build N fixtures + markets + matching odds, and return a session-ready
     * contest_draft array. Defaults to the controller's MIN_LEGS.
     *
     * @return array{fixtures: \Illuminate\Support\Collection<int,Fixture>, legs: array<int,array>}
     */
    private function buildLegs(
        int $count = self::MIN_LEGS,
        int $leagueId = 39,
        string $kickoff = '2026-08-15 15:00:00',
    ): array {
        $fixtures = Fixture::factory()->count($count)->create([
            'league_id' => $leagueId,
            'date' => $kickoff,
        ]);

        $legs = [];
        foreach ($fixtures as $fx) {
            $market = Market::factory()->create();
            $selection = 'Home';

            Odd::factory()->create([
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'value' => $selection,
                'odd' => 2.00,
            ]);

            $legs[] = [
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'selection' => $selection,
            ];
        }

        return ['fixtures' => $fixtures, 'legs' => $legs];
    }

    private function matchdayPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'MD1 EPL',
            'description' => 'Opening matchday',
            'type' => 'matchday',    
            'season' => '2026-27',
            'matchday_number' => 1,
            'auto_entry' => false,
        ], $overrides);
    }

    public function test_admin_can_create_matchday_contest_with_valid_data(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);

        $admin = $this->admin();
        $data = $this->buildLegs();

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload());

        // Assert on errors first — otherwise assertRedirect() passes on a
        // validation-back redirect and we chase ghosts.
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('contests', [
            'host_id' => $admin->id,
            'is_matchday' => true,
        ]);
    }

    public function test_matchday_contest_sets_is_matchday_true(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();
        $data = $this->buildLegs();

        $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload())
            ->assertSessionHasNoErrors();

        $contest = Contest::where('is_matchday', true)->firstOrFail();
        $this->assertTrue((bool) $contest->is_matchday);
    }

    public function test_matchday_contest_sets_league_code_from_inference(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();
        $data = $this->buildLegs(leagueId: 39);   // EPL

        $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload())
            ->assertSessionHasNoErrors();

        $contest = Contest::where('is_matchday', true)->firstOrFail();
        $this->assertSame('EPL', $contest->league_code);
    }

    public function test_matchday_contest_sets_locks_at_from_first_fixture(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.lock_offset_minutes', 10);

        $admin = $this->admin();
        $data = $this->buildLegs(kickoff: '2026-08-15 15:00:00');

        $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload())
            ->assertSessionHasNoErrors();

        $contest = Contest::where('is_matchday', true)->firstOrFail();

        $this->assertNotNull($contest->locks_at);
        $this->assertSame(
            '2026-08-15 14:50:00',
            $contest->locks_at->utc()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $contest->locks_at->timestamp,
            $contest->entry_deadline_at->timestamp
        );
    }

    public function test_matchday_contest_rejects_mixed_league_fixtures(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();

        // Three EPL + two UCL = five legs (satisfies MIN_LEGS) with mixed leagues.
        $epl = $this->buildLegs(count: 3, leagueId: 39);
        $ucl = $this->buildLegs(count: 2, leagueId: 2);
        $legs = array_merge($epl['legs'], $ucl['legs']);

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $legs])
            ->post(route('contests.store'), $this->matchdayPayload());

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );
        $this->assertDatabaseMissing('contests', ['is_matchday' => true]);
    }

    public function test_matchday_contest_rejects_unknown_league_code(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();
        $data = $this->buildLegs(leagueId: 999);

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload());

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );
        $this->assertDatabaseMissing('contests', ['is_matchday' => true]);
    }

    public function test_matchday_contest_requires_season_from_configured_list(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();
        $data = $this->buildLegs();

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->matchdayPayload([
                'season' => '1999-00',
            ]));

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );
        $this->assertDatabaseMissing('contests', ['is_matchday' => true]);
    }

    public function test_matchday_contest_rejects_duplicate_matchday_number_for_same_season_and_league(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();

        // First creation succeeds.
        $first = $this->buildLegs();
        $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $first['legs']])
            ->post(route('contests.store'), $this->matchdayPayload(['matchday_number' => 1]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Second creation with same (season, league, matchday_number) fails.
        $second = $this->buildLegs();

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $second['legs']])
            ->post(route('contests.store'), $this->matchdayPayload(['matchday_number' => 1]));

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );

        $this->assertSame(
            1,
            Contest::where('is_matchday', true)
                ->where('season', '2026-27')
                ->where('league_code', 'EPL')
                ->where('matchday_number', 1)
                ->count()
        );
    }

    public function test_non_matchday_contest_is_unaffected_by_this_flow(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $admin = $this->admin();
        $data = $this->buildLegs();

        $payload = [
            'name' => 'Regular Contest',
            'description' => 'Business as usual',
            'entry_deadline_at' => '2026-08-10 12:00:00',
            'auto_entry' => false,
        ];

        $response = $this
            ->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $contest = Contest::latest('id')->firstOrFail();

        $this->assertFalse((bool) $contest->is_matchday);
        $this->assertNull($contest->season);
        $this->assertNull($contest->league_code);
        $this->assertNull($contest->matchday_number);
        $this->assertNull($contest->locks_at);
        $this->assertSame(
            '2026-08-10 12:00:00',
            $contest->entry_deadline_at->format('Y-m-d H:i:s')
        );
    }
}