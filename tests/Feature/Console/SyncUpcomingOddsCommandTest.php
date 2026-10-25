<?php

namespace Tests\Feature\Console;

use App\Models\Fixture;
use App\Models\Odd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncUpcomingOddsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_TEAM_ID = 100;
    private const AWAY_TEAM_ID = 200;

    private static int $apiIdCounter = 500000;

    public function test_creates_odds_and_markets_for_ns_fixtures_within_48_hours(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'date'      => now()->addHours(2),
        ]);

        Http::fake([
            '*/odds*' => Http::response($this->oddsEnvelope(1001, [
                [
                    'id'     => 1,
                    'name'   => 'Match Winner',
                    'values' => [
                        ['value' => 'Home', 'odd' => '2.2'],
                        ['value' => 'Draw', 'odd' => '3.0'],
                        ['value' => 'Away', 'odd' => '2.6'],
                    ],
                ],
                [
                    'id'     => 2,
                    'name'   => 'Both Teams Score',
                    'values' => [
                        ['value' => 'Yes', 'odd' => '1.8'],
                        ['value' => 'No',  'odd' => '2.3'],
                    ],
                ],
            ]), 200),
        ]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $this->assertSame(5, Odd::where('fixture_id', $fixture->id)->count());

        $this->assertSame(32, \App\Models\Market::count());
        
        $home = Odd::where('fixture_id', $fixture->id)
            ->where('market_id', 1)
            ->where('value', 'Home')
            ->first();
        $this->assertNotNull($home);
        $this->assertSame(2.20, (float) $home->odd);
    }

    public function test_skips_fixtures_beyond_48_hours(): void
    {
        $this->makeFixture([
            'id_on_api' => 1001,
            'date'      => now()->addDays(5),
        ]);

        Http::fake(['*' => Http::response('should not be called', 500)]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, Odd::count());
    }

    public function test_skips_fixtures_that_are_not_ns(): void
    {
        $this->makeFixture([
            'id_on_api'    => 1001,
            'status_short' => '1H',
            'date'         => now()->addHours(1),
        ]);

        Http::fake(['*' => Http::response('should not be called', 500)]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_updates_existing_odd_price_instead_of_duplicating(): void
    {
        $fixture = $this->makeFixture([
            'id_on_api' => 1001,
            'date'      => now()->addHours(2),
        ]);

        Odd::forceCreate([
            'fixture_id' => $fixture->id,
            'market_id'  => 1,
            'value'      => 'Home',
            'odd'        => 2.20,
            'status'     => 'pending',
        ]);

        Http::fake([
            '*/odds*' => Http::response($this->oddsEnvelope(1001, [
                [
                    'id'     => 1,
                    'name'   => 'Match Winner',
                    'values' => [
                        ['value' => 'Home', 'odd' => '2.75'],
                    ],
                ],
            ]), 200),
        ]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $this->assertSame(1, Odd::where('fixture_id', $fixture->id)
            ->where('market_id', 1)
            ->where('value', 'Home')
            ->count());

        $home = Odd::where('fixture_id', $fixture->id)
            ->where('market_id', 1)
            ->where('value', 'Home')
            ->first();
        $this->assertSame(2.75, (float) $home->odd);
    }

    public function test_continues_after_one_fixture_fails(): void
    {
        $bad  = $this->makeFixture(['id_on_api' => 1001, 'date' => now()->addHours(1)]);
        $good = $this->makeFixture(['id_on_api' => 1002, 'date' => now()->addHours(2)]);

        Http::fake([
            '*/odds?fixture=1001*' => Http::response('boom', 500),
            '*/odds?fixture=1002*' => Http::response($this->oddsEnvelope(1002, [
                [
                    'id'     => 1,
                    'name'   => 'Match Winner',
                    'values' => [['value' => 'Home', 'odd' => '2.0']],
                ],
            ]), 200),
        ]);

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        $this->assertSame(0, Odd::where('fixture_id', $bad->id)->count());
        $this->assertSame(1, Odd::where('fixture_id', $good->id)->count());
    }

    public function test_logs_error_on_api_failure(): void
    {
        $this->makeFixture(['id_on_api' => 1001, 'date' => now()->addHours(1)]);

        Http::fake(['*/odds*' => Http::response('boom', 500)]);

        Log::spy();

        $this->artisan('odds:sync-upcoming')->assertSuccessful();

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg) => is_string($msg) && str_contains($msg, 'Failed to sync odds for fixture'))
            ->atLeast()->once();
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    private function makeFixture(array $attributes = []): Fixture
    {
        return Fixture::forceCreate(array_merge([
            'id_on_api'     => self::$apiIdCounter++,
            'date'          => now()->addHours(2),
            'timestamp'     => now()->addHours(2)->timestamp,
            'status_short'  => 'NS',
            'status_long'   => 'Not Started',
            'home_team_id'  => self::HOME_TEAM_ID,
            'away_team_id'  => self::AWAY_TEAM_ID,
            'home_winner'   => false,
            'settled'       => false,
        ], $attributes));
    }

    /**
     * @param  array<int, array{id:int, name:string, values:array<int, array{value:string, odd:string}>}>  $bets
     */
    private function oddsEnvelope(int $fixtureId, array $bets): array
    {
        return [
            'get'      => 'odds',
            'results'  => 1,
            'response' => [
                [
                    'league'  => ['id' => 39, 'name' => 'Premier League', 'season' => 2026],
                    'fixture' => ['id' => $fixtureId, 'timestamp' => now()->timestamp],
                    'bookmakers' => [
                        [
                            'id'   => 1,
                            'name' => 'MockBook',
                            'bets' => $bets,
                        ],
                    ],
                ],
            ],
        ];
    }
}