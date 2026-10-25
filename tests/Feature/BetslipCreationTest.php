<?php

namespace Tests\Feature;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BetslipCreationTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSeed = 0;

    public function test_ineligible_user_can_create_a_betslip(): void
    {
        // Betslip creation is never blocked — that's the whole point of
        // the "sellable flag, not a gate" design.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertNotNull($betslip);
        $this->assertSame($user->id, $betslip->user_id);
    }

    public function test_betslip_created_by_ineligible_user_has_is_sellable_false(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertFalse($betslip->is_sellable);
    }

    public function test_betslip_created_by_eligible_user_has_is_sellable_true(): void
    {
        // Status is what matters now. The user was promoted by the
        // recompute command.
        $user = User::factory()->create(['sellable_status' => 'sellable']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertTrue($betslip->is_sellable);
        $this->assertSame(500.0, (float) $betslip->price);
    }

    public function test_ineligible_user_price_is_forced_to_sentinel(): void
    {
        // Even if the user submits a real price, the model forces 1.
        // Otherwise their ROI aggregates would be distorted by numbers
        // they never actually sell at.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 5000);

        $this->assertSame(1.0, (float) $betslip->price);
    }

    public function test_delisted_user_betslip_is_not_sellable(): void
    {
        // canSell returns false for delisted regardless of numbers.
        $user = User::factory()->create(['sellable_status' => 'delisted']);
        $this->seedQualifyingPicks($user);   // numbers say qualified
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertFalse($betslip->is_sellable);
        $this->assertSame(1.0, (float) $betslip->price);
    }

    public function test_ineligible_user_betslip_picks_count_toward_win_rate(): void
    {
        // The picks exist on record even though the slip isn't sellable.
        // This is the whole reason ineligible slips aren't blocked: they
        // build the record.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        // Settle the betslip's pivot row.
        DB::table('betslip_odd')
            ->where('betslip_id', $betslip->id)
            ->update(['status' => 'won', 'updated_at' => now()->subDay()]);

        $service = app(\App\Services\SellerEligibilityService::class);
        $this->assertSame(1, $service->settledPicksCount($user));
        $this->assertNull($service->pickWinRate($user));   // below min picks
        // Note: below min picks → service returns null rate. Count is the
        // meaningful assertion here.
    }

    public function test_betslip_created_by_numerically_qualified_but_ineligible_status_user_is_not_sellable(): void
    {
        // Numbers cross the threshold, but the recompute command hasn't
        // run yet. Status is authoritative — the slip isn't sellable.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $this->seedQualifyingPicks($user);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertFalse($betslip->is_sellable);
        $this->assertSame(1.0, (float) $betslip->price);
    }

    public function test_eligible_seller_defaults_to_listed_when_no_intent_is_passed(): void
    {
        // Matches the toggle's default-ON contract. Existing callers
        // (seeders, older fixtures) that don't pass is_sellable get the
        // same behaviour as before this phase.
        $user = User::factory()->create(['sellable_status' => 'sellable']);
        $odd = $this->makeOdd();

        $betslip = $this->createBetslipWithOdd($user, $odd, submittedPrice: 500);

        $this->assertTrue($betslip->is_sellable);
        $this->assertSame(500.0, (float) $betslip->price);
    }

    public function test_eligible_seller_can_explicitly_opt_out_of_listing(): void
    {
        $user = User::factory()->create(['sellable_status' => 'sellable']);
        $odd = $this->makeOdd();

        $betslip = Betslip::create([
            'user_id' => $user->id,
            'code' => strtoupper(\Illuminate\Support\Str::random(10)),
            'total_odds' => 2.00,
            'price' => 500,
            'status' => 'pending',
            'remaining' => 1,
            'is_winner' => false,
            'priority_score' => 1,
            'is_sellable' => false,   // explicit opt-out
        ]);

        $this->assertFalse($betslip->is_sellable);
        $this->assertSame(1.0, (float) $betslip->price);
    }

    public function test_ineligible_user_cannot_opt_in_by_passing_is_sellable_true(): void
    {
        // The hook enforces the ceiling regardless of what the caller
        // passes. Belt-and-braces against a bad controller.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd = $this->makeOdd();

        $betslip = Betslip::create([
            'user_id' => $user->id,
            'code' => strtoupper(\Illuminate\Support\Str::random(10)),
            'total_odds' => 2.00,
            'price' => 500,
            'status' => 'pending',
            'remaining' => 1,
            'is_winner' => false,
            'priority_score' => 1,
            'is_sellable' => true,   // ignored — user can't sell
        ]);

        $this->assertFalse($betslip->is_sellable);
        $this->assertSame(1.0, (float) $betslip->price);
    }

    public function test_confirm_page_exposes_seller_progress_to_ineligible_users(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $odd  = $this->makeOdd();

        $this->actingAs($user);
        session(['betslip_draft' => [$odd->id]]);

        $response = $this->get('/betslip/confirm');

        $response->assertInertia(
            fn ($page) => $page
                ->where('can_sell', false)
                ->where('seller_status', 'ineligible')
                ->has('seller_progress')
                ->where('seller_progress.current_picks', 0)
                ->where('seller_progress.required_picks', 20)
                ->where('seller_progress.current_win_rate', null)
        );
    }

    // ────────────── Helpers ─────────────────────────────────────────────

    /**
     * Exercise the real model creation path — the hook lives on the model,
     * so we bypass the controller (which has its own price-validation and
     * session-draft requirements unrelated to this test).
     */
    private function createBetslipWithOdd(
        User $user,
        Odd $odd,
        int $submittedPrice,
    ): Betslip {
        $betslip = Betslip::create([
            'user_id' => $user->id,
            'code' => strtoupper(Str::random(10)),
            'total_odds' => 2.00,
            'price' => $submittedPrice,
            'status' => 'pending',
            'remaining' => 1,
            'is_winner' => false,
            'priority_score' => 1,
        ]);

        DB::table('betslip_odd')->insert([
            'betslip_id' => $betslip->id,
            'odd_id' => $odd->id,
            'status' => 'pending',
            'odd_value_at_time' => 2.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $betslip;
    }

    private function seedQualifyingPicks(User $user): void
    {
        // 20 unique fixtures, 12 won + 8 lost → 0.60 rate, above 0.55.
        for ($i = 0; $i < 20; $i++) {
            $fixture = $this->makeFixture(self::$fixtureSeed++);
            $odd = $this->makeOdd($fixture, marketId: $i + 1);

            $betslip = Betslip::create([
                'user_id' => $user->id,
                'code' => strtoupper(Str::random(10)),
                'total_odds' => 2.00,
                'price' => 100,
                'status' => 'settled',
                'remaining' => 0,
                'is_winner' => $i < 12,
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

    private function makeOdd(?Fixture $fixture = null, int $marketId = 1): Odd
    {
        $fixture = $fixture ?? $this->makeFixture(self::$fixtureSeed++);

        return Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'value' => 'Home',
            'odd' => 2.00,
            'status' => 'pending',
        ]);
    }

    private function makeFixture(int $seed): Fixture
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 995000],
            [
                'sport_id' => 1,
                'name' => 'Betslip Creation Test League',
                'country_id' => 1,
                'country' => 'Test',
                'season' => 2026,
            ],
        );

        $home = Team::firstOrCreate(
            ['id_on_api' => 996000 + $seed],
            ['name' => "H{$seed}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 997000 + $seed],
            ['name' => "A{$seed}"],
        );

        return Fixture::forceCreate([
            'id_on_api' => 998000 + $seed,
            'date' => now()->subDay(),
            'timestamp' => now()->subDay()->timestamp,
            'status_short' => 'NS',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner' => false,
            'settled' => false,
        ]);
    }
}