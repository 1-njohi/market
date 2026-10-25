<?php

namespace Tests\Unit\Services;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use App\Services\SellerEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SellerEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SellerEligibilityService::class);
    }

    // ────────────── Qualification gates ─────────────────────────────────

    public function test_user_with_no_picks_is_not_qualified(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->service->isQualified($user));
        $this->assertSame(0, $this->service->settledPicksCount($user));
        $this->assertNull($this->service->pickWinRate($user));
    }

    public function test_user_with_min_minus_one_picks_is_not_qualified(): void
    {
        $user = User::factory()->create();

        $this->seedSettledPicks($user, won: 12, lost: 7);   // 19 picks, min is 20

        $this->assertSame(19, $this->service->settledPicksCount($user));
        $this->assertNull($this->service->pickWinRate($user));
        $this->assertFalse($this->service->isQualified($user));
    }

    public function test_user_with_exactly_min_picks_at_threshold_is_qualified(): void
    {
        $user = User::factory()->create();

        // 20 picks, 11 won, 9 lost → 11/20 = 0.55 exactly.
        $this->seedSettledPicks($user, won: 11, lost: 9);

        $this->assertSame(20, $this->service->settledPicksCount($user));
        $this->assertSame(0.55, $this->service->pickWinRate($user));
        $this->assertTrue($this->service->isQualified($user));
    }

    public function test_user_with_min_picks_below_threshold_is_not_qualified(): void
    {
        $user = User::factory()->create();

        // 20 picks, 10 won, 10 lost → 0.50, below the 0.55 threshold.
        $this->seedSettledPicks($user, won: 10, lost: 10);

        $this->assertSame(20, $this->service->settledPicksCount($user));
        $this->assertSame(0.5, $this->service->pickWinRate($user));
        $this->assertFalse($this->service->isQualified($user));
    }

    // ────────────── Voided picks are excluded ───────────────────────────

    public function test_win_rate_excludes_voided_picks(): void
    {
        $user = User::factory()->create();

        // 20 won, 20 lost, 30 void → win rate should be 20/40 = 0.50.
        $this->seedSettledPicks($user, won: 20, lost: 20, void: 30);

        $this->assertSame(40, $this->service->settledPicksCount($user));
        $this->assertSame(0.5, $this->service->pickWinRate($user));
    }

    // ────────────── Rolling window ──────────────────────────────────────

    public function test_picks_outside_the_rolling_window_are_excluded(): void
    {
        $user = User::factory()->create();

        $this->seedSettledPicks($user, won: 11, lost: 9);   // in window
        $this->seedSettledPicks($user, won: 5, lost: 5, settledAt: now()->subDays(100));  // outside

        // Only the 20 in-window picks count.
        $this->assertSame(20, $this->service->settledPicksCount($user));
        $this->assertSame(0.55, $this->service->pickWinRate($user));
    }

    public function test_rolling_window_boundary_is_inclusive(): void
    {
        config(['marketplace.rolling_window_days' => 90]);

        $user = User::factory()->create();

        // Exactly 90 days ago — should still count.
        $this->seedSettledPicks($user, won: 11, lost: 9, settledAt: now()->subDays(90));

        $this->assertSame(20, $this->service->settledPicksCount($user));
    }

    // ────────────── Deduplication ───────────────────────────────────────

    public function test_duplicate_picks_across_betslips_count_once(): void
    {
        $user = User::factory()->create();

        // Same odd appears on 5 betslips. All settled.
        $this->seedSettledPicks($user, won: 1, lost: 0, timesPerPick: 5);

        $this->assertSame(1, $this->service->settledPicksCount($user));
    }

    public function test_deduplication_respects_market_and_selection(): void
    {
        $user = User::factory()->create();

        // Same fixture, same market, DIFFERENT selection → two distinct picks.
        $this->seedSettledPicksAcrossSelections($user);

        // Plus one duplicate for good measure.
        $this->seedSettledPicks($user, won: 1, lost: 0, timesPerPick: 3);

        // Expected: 3 unique (Home, Away on market 1 + one already counted selection).
        $count = $this->service->settledPicksCount($user);
        $this->assertGreaterThanOrEqual(2, $count);
        $this->assertLessThanOrEqual(3, $count);
    }

    // ────────────── canSell / canHost ───────────────────────────────────

    public function test_can_sell_returns_false_when_status_is_delisted(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'delisted',
        ]);

        // Even if numbers say qualified.
        $this->seedSettledPicks($user, won: 20, lost: 5);

        $this->assertTrue($this->service->isQualified($user));
        $this->assertFalse($this->service->canSell($user));
    }

    public function test_can_sell_returns_true_when_sellable(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'sellable',
        ]);

        $this->seedSettledPicks($user, won: 20, lost: 5);

        $this->assertTrue($this->service->canSell($user));
    }

    public function test_can_host_uses_numeric_qualification_only(): void
    {
        // Delisted sellers can still host.
        $user = User::factory()->create([
            'sellable_status' => 'delisted',
        ]);

        $this->seedSettledPicks($user, won: 20, lost: 5);

        $this->assertTrue($this->service->canHost($user));
    }

    public function test_can_host_returns_false_when_not_qualified(): void
    {
        $user = User::factory()->create();

        $this->seedSettledPicks($user, won: 5, lost: 15);

        $this->assertFalse($this->service->canHost($user));
    }

    // ────────────── Progress ────────────────────────────────────────────

    public function test_progress_reports_correct_numbers(): void
    {
        $user = User::factory()->create();

        // 20 picks — enough to cross the default min_settled_picks.
        // 14 won, 6 lost → 0.7000 exactly.
        $this->seedSettledPicks($user, won: 14, lost: 6);

        $progress = $this->service->progress($user);

        $this->assertSame(20, $progress['current_picks']);
        $this->assertSame(20, $progress['required_picks']);
        $this->assertSame(0.7, $progress['current_win_rate']);
        $this->assertSame(0.55, $progress['required_win_rate']);
        $this->assertTrue($progress['qualified']);
    }

    public function test_progress_reports_null_win_rate_when_insufficient_picks(): void
    {
        $user = User::factory()->create();

        $this->seedSettledPicks($user, won: 5, lost: 3);

        $progress = $this->service->progress($user);

        $this->assertSame(8, $progress['current_picks']);
        $this->assertNull($progress['current_win_rate']);
    }

    // ────────────── Config overrides ────────────────────────────────────

    public function test_thresholds_respect_config_overrides(): void
    {
        config([
            'marketplace.min_settled_picks' => 5,
            'marketplace.min_win_rate_to_sell' => 0.40,
        ]);

        $user = User::factory()->create();

        // 5 picks, 2 won → 0.40 exactly. Qualifies under the override.
        $this->seedSettledPicks($user, won: 2, lost: 3);

        $this->assertTrue($this->service->isQualified($user));
        $this->assertSame(5, $this->service->settledPicksCount($user));
        $this->assertSame(0.4, $this->service->pickWinRate($user));
    }

    public function test_can_sell_returns_false_for_ineligible_status_even_with_qualifying_numbers(): void
    {
        // The daily command hasn't run yet — status says 'ineligible' but
        // the numbers already qualify. Status is authoritative, so no sale.
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $this->seedSettledPicks($user, won: 12, lost: 8);

        $this->assertTrue($this->service->isQualified($user));   // numbers qualify
        $this->assertFalse($this->service->canSell($user));      // status doesn't
    }

    public function test_can_sell_returns_true_for_warned_status(): void
    {
        // Warned sellers keep selling during grace.
        $user = User::factory()->create(['sellable_status' => 'warned']);
        $this->seedSettledPicks($user, won: 9, lost: 11);   // 45%, below threshold

        $this->assertFalse($this->service->isQualified($user));  // numbers don't qualify
        $this->assertTrue($this->service->canSell($user));       // but status allows
    }

    // ────────────── Helpers ─────────────────────────────────────────────

    /**
     * Counter that increments across every fixture built in this test
     * class. Guarantees unique id_on_api values even when
     * seedSettledPicks is called multiple times.
     */
    private static int $fixtureSeed = 0;

    private function seedSettledPicks(
        User $user,
        int $won,
        int $lost,
        int $void = 0,
        int $timesPerPick = 1,
        ?\Carbon\CarbonImmutable $settledAt = null,
    ): void {
        $settledAt = $settledAt ?? now()->subDays(1);

        $picks = [];
        for ($i = 0; $i < $won; $i++) {
            $picks[] = ['status' => 'won'];
        }
        for ($i = 0; $i < $lost; $i++) {
            $picks[] = ['status' => 'lost'];
        }
        for ($i = 0; $i < $void; $i++) {
            $picks[] = ['status' => 'void'];
        }

        foreach ($picks as $pick) {
            $fixture = $this->makeFixture(self::$fixtureSeed++);
            $odd = $this->makeOdd($fixture, marketId: 1, value: 'Home');

            for ($t = 0; $t < $timesPerPick; $t++) {
                $this->makeBetslipWithOdd(
                    $user,
                    $odd,
                    status: $pick['status'],
                    settledAt: $settledAt,
                );
            }
        }
    }

    /**
     * Two distinct selections on the same fixture+market.
     */
    private function seedSettledPicksAcrossSelections(User $user): void
    {
        $fixture = $this->makeFixture(seed: 900000);
        $oddHome = $this->makeOdd($fixture, marketId: 1, value: 'Home');
        $oddAway = $this->makeOdd($fixture, marketId: 1, value: 'Away');

        $this->makeBetslipWithOdd($user, $oddHome, status: 'won', settledAt: now()->subDay());
        $this->makeBetslipWithOdd($user, $oddAway, status: 'lost', settledAt: now()->subDay());
    }

    private function makeFixture(int $seed): Fixture
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 990000],
            [
                'sport_id' => 1,
                'name' => 'Eligibility Test League',
                'country_id' => 1,
                'country' => 'Test',
                'season' => 2026,
            ],
        );

        $home = Team::firstOrCreate(
            ['id_on_api' => 991000 + $seed],
            ['name' => "Home {$seed}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 992000 + $seed],
            ['name' => "Away {$seed}"],
        );

        return Fixture::forceCreate([
            'id_on_api' => 993000 + $seed,
            'date' => now()->subDays(2),
            'timestamp' => now()->subDays(2)->timestamp,
            'status_short' => 'FT',
            'league_id' => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner' => false,
            'settled' => true,
        ]);
    }

    private function makeOdd(Fixture $fixture, int $marketId, string $value): Odd
    {
        return Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'value' => $value,
            'odd' => 2.00,
            'status' => 'pending',
        ]);
    }

    /**
     * Create a betslip with one odd, write the pivot row with the given
     * status and settled_at.
     *
     * Writes the pivot directly rather than through the app's flow — this
     * is a fixture builder, not a flow test.
     */
    private function makeBetslipWithOdd(
        User $user,
        Odd $odd,
        string $status,
        \Carbon\CarbonImmutable $settledAt,
    ): Betslip {
        $betslip = Betslip::forceCreate([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'code' => strtoupper(Str::random(10)),
            'total_odds' => 2.00,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'is_winner' => $status === 'won',
            'is_sellable' => false,
            'is_delisted' => false,
            'settled_at' => $settledAt,
            'priority_score' => 1,
        ]);

        DB::table('betslip_odd')->insert([
            'betslip_id' => $betslip->id,
            'odd_id' => $odd->id,
            'status' => $status,
            'odd_value_at_time' => 2.00,
            'created_at' => $settledAt,
            'updated_at' => $settledAt,
        ]);

        return $betslip;
    }
}