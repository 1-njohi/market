<?php

namespace Tests\Feature;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MarketSettlementService;
use App\Services\PlatformAccount;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The seller eligibility rolling window reads betslip_odd.updated_at as
 * "when this pick settled". For that to be meaningful, settlement must
 * actually touch the pivot's updated_at — not leave it at the moment the
 * pick was attached.
 */
class SettlementTimestampTest extends TestCase
{
    use RefreshDatabase;

    private static int $fixtureSeed = 0;

    public function test_settlement_writes_fresh_updated_at_on_the_pivot(): void
    {
        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        // A betslip created 10 days ago whose pivot was attached then.
        $betslip = Betslip::forceCreate([
            'id'             => (string) Str::uuid(),
            'user_id'        => $seller->id,
            'code'           => strtoupper(Str::random(10)),
            'total_odds'     => 2.00,
            'price'          => 100.00,
            'status'         => 'pending',
            'remaining'      => 1,
            'is_winner'      => false,
            'is_sellable'    => true,
            'is_delisted'    => false,
            'priority_score' => 1,
        ]);

        $fixture = $this->makeFixture();
        $odd     = Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'value'      => 'Home',
            'odd'        => 2.00,
            'status'     => 'pending',
        ]);

        $attachedAt = now()->subDays(10);

        DB::table('betslip_odd')->insert([
            'betslip_id'        => $betslip->id,
            'odd_id'            => $odd->id,
            'status'            => 'pending',
            'odd_value_at_time' => 2.00,
            'created_at'        => $attachedAt,
            'updated_at'        => $attachedAt,
        ]);

        // Settle the fixture. The odd resolves to 'won'.
        $fixture->update([
            'status_short'  => 'FT',
            'settled'       => false,
            'goals_home'    => 2,
            'goals_away'    => 0,
            'halftime_home' => 1,
            'halftime_away' => 0,
            'fulltime_home' => 2,
            'fulltime_away' => 0,
        ]);

        // Make the odd resolve: Man U-style, home wins, so 'Home' pick wins.
        $odd->update(['status' => 'won']);

        // Stub the platform account so wallet settlement doesn't explode.
        $platform = User::factory()->create();
        $mock = \Mockery::mock(PlatformAccount::class);
        $mock->shouldReceive('user')->andReturn($platform);
        $this->app->instance(PlatformAccount::class, $mock);

        // Escrow for the buyer so the seller is paid.
        $wallet = app(WalletService::class);
        $wallet->credit($buyer, 100.00, Transaction::TYPE_DEPOSIT);
        $wallet->hold($buyer, 100.00);

        DB::table('betslip_user_purchases')->insert([
            'betslip_id'     => $betslip->id,
            'buyer_id'       => $buyer->id,
            'seller_id'      => $seller->id,
            'purchase_price' => 100.00,
            'total_odds'     => 2.00,
            'status'         => 'pending',
            'purchased_at'   => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        app(MarketSettlementService::class)->settleFixture($fixture);

        $pivot = DB::table('betslip_odd')
            ->where('betslip_id', $betslip->id)
            ->where('odd_id', $odd->id)
            ->first();

        $this->assertSame('won', $pivot->status);

        // The pivot's updated_at must have moved to (approximately) now.
        // Give it a generous 60-second window — this test runs in <1s, so
        // any value more than a minute old means the settlement path
        // didn't touch it.
        $updatedAt = \Carbon\Carbon::parse($pivot->updated_at);
        $this->assertTrue(
            $updatedAt->gt(now()->subMinute()),
            "Pivot updated_at should be fresh; got {$updatedAt} which is more than a minute old.",
        );
        $this->assertNotSame(
            $attachedAt->toDateTimeString(),
            $updatedAt->toDateTimeString(),
            'Pivot updated_at should differ from the original attach timestamp.',
        );
    }

    private function makeFixture(): Fixture
    {
        $seed = self::$fixtureSeed++;
        $league = \App\Models\League::firstOrCreate(
            ['id_on_api' => 995100],
            [
                'sport_id'   => 1,
                'name'       => 'Timestamp Test League',
                'country_id' => 1,
                'country'    => 'Test',
                'season'     => 2026,
            ],
        );

        $home = \App\Models\Team::firstOrCreate(
            ['id_on_api' => 995200 + $seed],
            ['name' => "H{$seed}"],
        );
        $away = \App\Models\Team::firstOrCreate(
            ['id_on_api' => 995300 + $seed],
            ['name' => "A{$seed}"],
        );

        return Fixture::forceCreate([
            'id_on_api'    => 995400 + $seed,
            'date'         => now(),
            'timestamp'    => now()->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_winner'  => false,
            'settled'      => false,
        ]);
    }
}