<?php

namespace Tests\Feature\Jackpot;

use App\Models\Contest;
use App\Models\Fixture;
use App\Models\Market;
use App\Models\Odd;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateJackpotContestTest extends TestCase
{
    use RefreshDatabase;

    /** Mirrors ContestCreateController::MIN_LEGS and MAX_LEGS for matchday. */
    private const JACKPOT_MIN = 7;
    private const JACKPOT_MAX = 20;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn() => new class {
            public function canHost(User $user): bool
                {
                    return true;
                }
            });

        config()->set('marketplace.matchday.seasons', ['2026-27']);
        config()->set('marketplace.matchday.supported_league_codes', ['EPL' => 39]);
        config()->set('marketplace.matchday.lock_offset_minutes', 10);

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));

        \App\Models\League::firstOrCreate(
            ['id' => 39],
            [
                'id_on_api' => 39,
                'sport_id' => 1,
                'name' => 'Premier League',
                'country_id' => 1,
                'country' => 'England',
                'season' => 2026,
            ],
        );
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /** @return array{fixtures: \Illuminate\Support\Collection, legs: array} */
    private function buildLegs(int $count, string $kickoff = '2026-08-15 15:00:00'): array
    {
        $fixtures = Fixture::factory()->count($count)->create([
            'league_id' => 39,
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

    private function jackpotPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mega Jackpot',
            'description' => 'Weekend 13-leg',
            'type' => 'jackpot',
            'auto_entry' => false,
        ], $overrides);
    }

    public function test_admin_can_create_jackpot_contest(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(self::JACKPOT_MIN);

        $response = $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('contests', ['type' => 'jackpot']);
    }

    public function test_jackpot_sets_type_correctly(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(self::JACKPOT_MIN);

        $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload());

        $contest = Contest::where('type', 'jackpot')->firstOrFail();
        $this->assertSame('jackpot', $contest->type);
        $this->assertTrue($contest->isJackpot());
        $this->assertFalse($contest->is_matchday);
    }

    public function test_jackpot_forces_public_visibility(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(self::JACKPOT_MIN);

        // Admin attempts to make it private via auto_entry=false. Even
        // without admin opt-in, jackpot should be public.
        $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload(['auto_entry' => false]));

        $contest = Contest::where('type', 'jackpot')->firstOrFail();
        $this->assertSame('public', $contest->visibility);
    }

    public function test_jackpot_requires_at_least_seven_legs(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(6);   // one below minimum

        $response = $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload());

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );
        $this->assertDatabaseMissing('contests', ['type' => 'jackpot']);
    }

    public function test_jackpot_allows_up_to_twenty_legs(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(self::JACKPOT_MAX);

        $response = $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload());

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contests', ['type' => 'jackpot']);
    }

    public function test_jackpot_rejects_more_than_twenty_legs(): void
    {
        $admin = $this->admin();
        $data = $this->buildLegs(21);

        $response = $this->actingAs($admin)
            ->withSession(['contest_draft' => $data['legs']])
            ->post(route('contests.store'), $this->jackpotPayload());

        $this->assertTrue(
            $response->isRedirect() || $response->isClientError(),
            "Expected a validation-style rejection, got {$response->status()}"
        );
        $this->assertDatabaseMissing('contests', ['type' => 'jackpot']);
    }

    public function test_jackpot_derives_locks_at_from_earliest_fixture(): void
    {
        $admin = $this->admin();

        // Create legs across three different kickoffs; the earliest should win.
        $fixtures = Fixture::factory()->count(self::JACKPOT_MIN)->create([
            'league_id' => 39,
            'date' => '2026-08-15 15:00:00',
        ]);

        // Nudge one fixture earlier.
        $fixtures->first()->update(['date' => '2026-08-15 12:30:00']);

        $legs = [];
        foreach ($fixtures as $fx) {
            $market = Market::factory()->create();
            Odd::factory()->create([
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'value' => 'Home',
                'odd' => 2.00,
            ]);
            $legs[] = [
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'selection' => 'Home',
            ];
        }

        $this->actingAs($admin)
            ->withSession(['contest_draft' => $legs])
            ->post(route('contests.store'), $this->jackpotPayload());

        $contest = Contest::where('type', 'jackpot')->firstOrFail();

        // 12:30 − 10 min offset = 12:20.
        $this->assertSame(
            '2026-08-15 12:20:00',
            $contest->locks_at->utc()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $contest->locks_at->timestamp,
            $contest->entry_deadline_at->timestamp
        );
    }

    public function test_jackpot_does_not_run_league_inference(): void
    {
        $admin = $this->admin();

        // Legs from mixed leagues — matchday would reject, jackpot should accept.
        $eplFixtures = Fixture::factory()->count(4)->create([
            'league_id' => 39,
            'date' => '2026-08-15 15:00:00',
        ]);

        \App\Models\League::firstOrCreate(
            ['id' => 2],
            [
                'id_on_api' => 2,
                'sport_id' => 1,
                'name' => 'Champions League',
                'country_id' => 1,
                'country' => 'Europe',
                'season' => 2026,
            ],
        );

        $uclFixtures = Fixture::factory()->count(4)->create([
            'league_id' => 2,
            'date' => '2026-08-15 20:00:00',
        ]);

        $legs = [];
        foreach ($eplFixtures->merge($uclFixtures) as $fx) {
            $market = Market::factory()->create();
            Odd::factory()->create([
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'value' => 'Home',
                'odd' => 2.00,
            ]);
            $legs[] = [
                'fixture_id' => $fx->id,
                'market_id' => $market->id,
                'selection' => 'Home',
            ];
        }

        $response = $this->actingAs($admin)
            ->withSession(['contest_draft' => $legs])
            ->post(route('contests.store'), $this->jackpotPayload());

        $response->assertSessionHasNoErrors();
        $contest = Contest::where('type', 'jackpot')->firstOrFail();

        // No league inference means league_code is null.
        $this->assertNull($contest->league_code);
        $this->assertNull($contest->season);
    }
}