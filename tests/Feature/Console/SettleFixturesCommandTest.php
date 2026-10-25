<?php

namespace Tests\Feature\Console;

use App\Models\Fixture;
use App\Services\MarketSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SettleFixturesCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    private static int $apiIdCounter = 600000;

    public function test_settles_finished_and_void_fixtures_but_not_others(): void
    {
        $ft   = $this->makeFixture(['status_short' => 'FT',   'settled' => false]);
        $canc = $this->makeFixture(['status_short' => 'CANC', 'settled' => false]);
        $ns   = $this->makeFixture(['status_short' => 'NS',   'settled' => false]);
        $live = $this->makeFixture(['status_short' => '1H',   'settled' => false]);
        $done = $this->makeFixture(['status_short' => 'FT',   'settled' => true]);

        $this->artisan('fixtures:settle')->assertSuccessful();

        $this->assertTrue($ft->fresh()->settled);
        $this->assertTrue($canc->fresh()->settled);
        $this->assertFalse($ns->fresh()->settled);
        $this->assertFalse($live->fresh()->settled);
        $this->assertTrue($done->fresh()->settled);
    }

    public function test_one_fixture_failure_does_not_stop_the_run(): void
    {
        $fixtureA = $this->makeFixture(['status_short' => 'FT', 'settled' => false]);
        $fixtureB = $this->makeFixture(['status_short' => 'FT', 'settled' => false]);

        $service = Mockery::mock(MarketSettlementService::class);
        $service->shouldReceive('settleFixture')
            ->andReturnUsing(function (Fixture $f) use ($fixtureA) {
                if ($f->id === $fixtureA->id) {
                    throw new \RuntimeException('boom');
                }
            });

        $this->app->instance(MarketSettlementService::class, $service);

        Log::spy();

        $this->artisan('fixtures:settle')->assertSuccessful();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => is_string($msg) && str_contains($msg, "Settlement failed for fixture {$fixtureA->id}"))
            ->once();
    }

    public function test_returns_success_when_there_is_nothing_to_settle(): void
    {
        $this->artisan('fixtures:settle')->assertSuccessful();
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    private function makeFixture(array $attributes = []): Fixture
    {
        return Fixture::forceCreate(array_merge([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now(),
            'timestamp'     => now()->timestamp,
            'status_short'  => 'FT',
            'status_long'   => 'Match Finished',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'home_winner'   => false,
            'settled'       => false,
        ], $attributes));
    }
}