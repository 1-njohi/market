<?php

namespace Tests\Feature\Console;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use App\Notifications\SellerDelistedNotification;
use App\Notifications\SellerPromotedNotification;
use App\Notifications\SellerWarnedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecomputeSellerStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Counter for fixture seeds — must be unique across every fixture
     * this test class builds. Named to avoid colliding with PHPUnit's
     * reserved static $seed property (used for its random-order feature).
     */
    private static int $fixtureSeed = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }
    // ────────────── Promotions and initial qualification ─────────────────

    public function test_ineligible_user_with_qualifying_picks_becomes_sellable(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $this->seedPicks($user, won: 12, lost: 8);   // 60%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('sellable', $user->sellable_status);
        $this->assertNotNull($user->sellable_status_changed_at);
        $this->assertNull($user->sellable_warned_at);
        Notification::assertSentTo($user, SellerPromotedNotification::class);
    }

    public function test_user_with_fewer_than_min_picks_keeps_current_status(): void
    {
        $user = User::factory()->create(['sellable_status' => 'sellable']);
        $this->seedPicks($user, won: 3, lost: 2);   // 5 picks, below min

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('sellable', $user->sellable_status);
        $this->assertSame(5, $user->picks_settled_rolling);
        $this->assertNull($user->pick_win_rate_rolling);
        Notification::assertNothingSent();
    }

    // ────────────── Sellable → warned ───────────────────────────────────

    public function test_sellable_user_dropping_below_stay_threshold_transitions_to_warned(): void
    {
        $user = User::factory()->create(['sellable_status' => 'sellable']);
        $this->seedPicks($user, won: 9, lost: 11);   // 45%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('warned', $user->sellable_status);
        $this->assertNotNull($user->sellable_warned_at);
        Notification::assertSentTo($user, SellerWarnedNotification::class);
    }

    // ────────────── Warned → delisted ───────────────────────────────────

    public function test_warned_user_within_grace_period_stays_warned(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(10),
        ]);
        $this->seedPicks($user, won: 9, lost: 11);   // 45%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('warned', $user->sellable_status);
        Notification::assertNothingSent();
    }

    public function test_warned_user_past_grace_period_transitions_to_delisted(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(31),
        ]);
        $this->seedPicks($user, won: 9, lost: 11);   // 45%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('delisted', $user->sellable_status);
        Notification::assertSentTo($user, SellerDelistedNotification::class);
    }

    // ────────────── Warned → sellable ───────────────────────────────────

    public function test_warned_user_recovering_above_enter_threshold_becomes_sellable(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(5),
        ]);
        $this->seedPicks($user, won: 12, lost: 8);   // 60%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('sellable', $user->sellable_status);
        $this->assertNull($user->sellable_warned_at);
        Notification::assertSentTo($user, SellerPromotedNotification::class);
    }

    public function test_warned_user_in_hysteresis_band_stays_warned(): void
    {
        // Rate above stay threshold (0.50) but below enter threshold (0.55).
        // No state change either way.
        $user = User::factory()->create([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(5),
        ]);
        $this->seedPicks($user, won: 10, lost: 10);   // exactly 0.50

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('warned', $user->sellable_status);
        Notification::assertNothingSent();
    }

    // ────────────── Delisted states ─────────────────────────────────────

    public function test_delisted_user_stays_delisted_in_hysteresis_band(): void
    {
        $user = User::factory()->create(['sellable_status' => 'delisted']);
        $this->seedPicks($user, won: 10, lost: 10);   // 0.50 — in the band

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('delisted', $user->sellable_status);
        Notification::assertNothingSent();
    }

    public function test_delisted_user_recovers_to_sellable_when_rate_crosses_enter_threshold(): void
    {
        $user = User::factory()->create(['sellable_status' => 'delisted']);
        $this->seedPicks($user, won: 12, lost: 8);   // 60%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('sellable', $user->sellable_status);
        Notification::assertSentTo($user, SellerPromotedNotification::class);
    }

    // ────────────── Side effects ────────────────────────────────────────

    public function test_delisting_marks_live_sellable_betslips_as_delisted(): void
    {
        $user = User::factory()->create([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(31),
        ]);
        $this->seedPicks($user, won: 9, lost: 11);   // 45%

        $live1 = $this->makeLiveSellableBetslip($user);
        $live2 = $this->makeLiveSellableBetslip($user);
        $settled = $this->makeSettledBetslip($user);

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $this->assertTrue($live1->fresh()->is_delisted);
        $this->assertTrue($live2->fresh()->is_delisted);
        // Settled slips are not touched — they've already finished.
        $this->assertFalse($settled->fresh()->is_delisted);
    }

    public function test_delisting_skips_non_sellable_live_betslips(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);

        // Slip is created while the user is ineligible — the hook writes
        // is_sellable = false and price = 1. This slip was never in the
        // marketplace and must stay untouched by delisting.
        $neverSellable = Betslip::forceCreate([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'code' => strtoupper(Str::random(10)),
            'total_odds' => 2.00,
            'price' => 1,
            'status' => 'pending',
            'remaining' => 1,
            'is_winner' => false,
            'is_sellable' => false,
            'is_delisted' => false,
            'priority_score' => 1,
        ]);

        // Now the user falls on hard times. Update their status directly
        // (the recompute command will see the picks and delist them).
        $user->update([
            'sellable_status' => 'warned',
            'sellable_warned_at' => now()->subDays(31),
        ]);
        $this->seedPicks($user, won: 9, lost: 11);   // 45%

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        // Never-sellable slips are not flipped — they were never in the
        // marketplace to begin with.
        $this->assertFalse($neverSellable->fresh()->is_delisted);
    }

    // ────────────── Number recomputation ────────────────────────────────

    public function test_numbers_are_recomputed_regardless_of_status_change(): void
    {
        // Pre-existing user with a stale rate. New picks below min → the
        // numbers must be overwritten even though no state change fires.
        $user = User::factory()->create([
            'sellable_status' => 'sellable',
            'pick_win_rate_rolling' => 0.9999,
            'picks_settled_rolling' => 999,
        ]);
        $this->seedPicks($user, won: 3, lost: 2);   // 5 picks, below min

        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame(5, $user->picks_settled_rolling);
        $this->assertNull($user->pick_win_rate_rolling);
        // Status unchanged.
        $this->assertSame('sellable', $user->sellable_status);
    }

    // ────────────── Idempotency ─────────────────────────────────────────

    public function test_command_is_idempotent(): void
    {
        $user = User::factory()->create(['sellable_status' => 'ineligible']);
        $this->seedPicks($user, won: 12, lost: 8);

        $this->artisan('users:recompute-seller-status')->assertSuccessful();
        $this->artisan('users:recompute-seller-status')->assertSuccessful();

        $user->refresh();
        $this->assertSame('sellable', $user->sellable_status);
        Notification::assertSentToTimes($user, SellerPromotedNotification::class, 1);
    }

    // ────────────── Helpers ─────────────────────────────────────────────

    /**
     * Seed N settled betslip picks for a user.
     *
     * One fixture per seed. Each pick is a distinct (fixture_id, market_id)
     * pair so the deduplication key differs — we need every seeded pick to
     * count as unique.
     */
    private function seedPicks(
        User $user,
        int $won,
        int $lost,
        ?\Carbon\CarbonImmutable $settledAt = null,
    ): void {
        $settledAt = $settledAt ?? now()->subDays(1);
        $fixture = $this->makeFixture(self::$fixtureSeed++);

        $marketId = 1;
        for ($i = 0; $i < $won; $i++) {
            $odd = $this->makeOdd($fixture, $marketId++);
            $this->makeBetslipWithOdd($user, $odd, 'won', $settledAt);
        }
        for ($i = 0; $i < $lost; $i++) {
            $odd = $this->makeOdd($fixture, $marketId++);
            $this->makeBetslipWithOdd($user, $odd, 'lost', $settledAt);
        }
    }

    private function makeFixture(int $seed): Fixture
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 991000],
            [
                'sport_id' => 1,
                'name' => 'Recompute Test League',
                'country_id' => 1,
                'country' => 'Test',
                'season' => 2026,
            ],
        );

        $home = Team::firstOrCreate(
            ['id_on_api' => 992000 + $seed],
            ['name' => "Home {$seed}"],
        );
        $away = Team::firstOrCreate(
            ['id_on_api' => 993000 + $seed],
            ['name' => "Away {$seed}"],
        );

        return Fixture::forceCreate([
            'id_on_api' => 994000 + $seed,
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

    private function makeOdd(Fixture $fixture, int $marketId): Odd
    {
        return Odd::create([
            'fixture_id' => $fixture->id,
            'market_id' => $marketId,
            'value' => 'Home',
            'odd' => 2.00,
            'status' => 'pending',
        ]);
    }

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

    private function makeLiveSellableBetslip(User $user): Betslip
    {
        return Betslip::forceCreate([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'code' => strtoupper(Str::random(10)),
            'total_odds' => 2.00,
            'price' => 100.00,
            'status' => 'pending',
            'remaining' => 1,
            'is_winner' => false,
            'is_sellable' => true,
            'is_delisted' => false,
            'priority_score' => 1,
        ]);
    }

    private function makeSettledBetslip(User $user): Betslip
    {
        return Betslip::forceCreate([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'code' => strtoupper(Str::random(10)),
            'total_odds' => 2.00,
            'price' => 100.00,
            'status' => 'settled',
            'remaining' => 0,
            'is_winner' => true,
            'is_sellable' => true,
            'is_delisted' => false,
            'settled_at' => now()->subDay(),
            'priority_score' => 1,
        ]);
    }
}