<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use App\Exceptions\UnauthorizedHostingException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContestHostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The skipHostGuard static persists across test methods within a
        // process. Tests here assert the guard fires, so any leaked value
        // from a preceding test class has to be cleared first.
        Contest::resetHostGuard();
    }

    private static int $fixtureSeed = 0;

    // ────────────── Model-level ─────────────────────────────────────────

    public function test_creating_a_contest_as_an_ineligible_host_throws(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        $this->actingAs($user);

        $this->expectException(UnauthorizedHostingException::class);

        Contest::create([
            'host_id' => $user->id,
            'name' => 'Test',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]);
    }

    public function test_creating_a_contest_as_a_qualified_host_succeeds(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $this->seedQualifyingPicks($user);

        $this->actingAs($user);

        $contest = Contest::withoutHostGuard(fn() => Contest::create([
            'host_id' => $user->id,
            'name' => 'Test',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]));

        $this->assertDatabaseHas('contests', ['id' => $contest->id]);
    }

    public function test_delisted_user_with_qualifying_numbers_can_still_host(): void
    {
        // canHost uses numeric qualification only — status is ignored.
        $user = User::factory()->create(['sellable_status' => 'delisted']);
        $this->seedQualifyingPicks($user);

        $this->actingAs($user);

        $contest = Contest::withoutHostGuard(fn() => Contest::create([
            'host_id' => $user->id,
            'name' => 'Test',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addHour(),
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
        ]));

        $this->assertDatabaseHas('contests', ['id' => $contest->id]);
    }

    // ────────────── HTTP endpoint ───────────────────────────────────────

    public function test_ineligible_user_gets_403_from_contest_store_endpoint(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        $this->actingAs($user);
        session(['contest_draft' => $this->makeDraftLegs()]);

        $response = $this->post('/contests', [
            'name' => 'Blocked Contest',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('contests', 0);
    }

    public function test_403_message_interpolates_from_config(): void
    {
        config([
            'marketplace.min_settled_picks' => 15,
            'marketplace.min_win_rate_to_sell' => 0.60,
            'marketplace.rolling_window_days' => 60,
        ]);

        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        $this->actingAs($user);
        session(['contest_draft' => $this->makeDraftLegs()]);

        $response = $this->post('/contests', [
            'name' => 'Blocked',
            'entry_deadline_at' => now()->addHour()->toDateTimeString(),
        ]);

        $response->assertStatus(403);
        $body = $response->getContent();
        $this->assertStringContainsString('15', $body);
        $this->assertStringContainsString('60', $body);       // 0.60 * 100
        $this->assertStringContainsString('60 days', $body);
    }

    // ────────────── Helpers ─────────────────────────────────────────────

    /**
     * Five draft legs — satisfies ContestCreateController::MIN_LEGS and
     * the no-duplicate-pair rule.
     *
     * @return array<int, array{fixture_id:int, market_id:int, selection:string}>
     */
    private function makeDraftLegs(): array
    {
        $legs = [];
        for ($i = 0; $i < 5; $i++) {
            $fixture = $this->makeFixture(self::$fixtureSeed++);
            $odd = Odd::create([
                'fixture_id' => $fixture->id,
                'market_id' => 1,
                'value' => 'Home',
                'odd' => 2.00,
                'status' => 'pending',
            ]);

            $legs[] = [
                'fixture_id' => $fixture->id,
                'market_id' => 1,
                'selection' => 'Home',
            ];
        }
        return $legs;
    }

    private function seedQualifyingPicks(User $user): void
    {
        for ($i = 0; $i < 20; $i++) {
            $fixture = $this->makeFixture(self::$fixtureSeed++);
            $odd = Odd::create([
                'fixture_id' => $fixture->id,
                'market_id' => $i + 1,
                'value' => 'Home',
                'odd' => 2.00,
                'status' => 'pending',
            ]);

            $betslip = \App\Models\Betslip::forceCreate([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'code' => strtoupper(\Illuminate\Support\Str::random(10)),
                'total_odds' => 2.00,
                'price' => 100,
                'status' => 'settled',
                'remaining' => 0,
                'is_winner' => $i < 12,
                'is_sellable' => false,
                'is_delisted' => false,
                'priority_score' => 1,
            ]);

            DB::table('betslip_odd')->insert([
                'betslip_id' => $betslip->id,
                'odd_id' => $odd->id,
                'status' => $i < 12 ? 'won' : 'lost',
                'odd_value_at_time' => 2.00,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }
    }

    private function makeFixture(int $seed): Fixture
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 995001],
            [
                'sport_id' => 1,
                'name' => 'Contest Hosting Test League',
                'country_id' => 1,
                'country' => 'Test',
                'season' => 2026,
            ],
        );

        $home = Team::firstOrCreate(
            ['id_on_api' => 998100 + $seed],
            ['name' => "H{$seed}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 998200 + $seed],
            ['name' => "A{$seed}"],
        );

        return Fixture::forceCreate([
            'id_on_api' => 998300 + $seed,
            'date' => now()->addHours(3),
            'timestamp' => now()->addHours(3)->timestamp,
            'status_short' => 'NS',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner' => false,
            'settled' => false,
        ]);
    }
}