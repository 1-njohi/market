<?php

namespace Tests\Feature\Matchday;

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

class ConfirmPagePropsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SellerEligibilityService::class, fn () => new class {
            public function canHost(User $user): bool { return true; }
        });

        config()->set('marketplace.matchday.seasons', ['2026-27', '2027-28']);
        config()->set('marketplace.matchday.supported_league_codes', [
            'EPL' => 39,
            'UCL' => 2,
        ]);

        $this->travelTo(Carbon::parse('2026-08-01 00:00:00'));

        League::firstOrCreate(
            ['id' => 39],
            [
                'id_on_api'  => 39,
                'sport_id'   => 1,
                'name'       => 'Premier League',
                'country_id' => 1,
                'country'    => 'England',
                'season'     => 2026,
            ],
        );
    }

    private function seedDraft(User $host): void
    {
        $legs = [];

        for ($i = 0; $i < 5; $i++) {
            $home = Team::firstOrCreate(
                ['id_on_api' => 900000 + $i],
                ['name' => "H{$i}"],
            );
            $away = Team::firstOrCreate(
                ['id_on_api' => 910000 + $i],
                ['name' => "A{$i}"],
            );

            $fixture = Fixture::create([
                'id_on_api'    => 900000 + $i,
                'date'         => now()->addDays(3),
                'timestamp'    => now()->addDays(3)->timestamp,
                'status_short' => 'NS',
                'league_id'    => 39,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
            ]);

            Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

            Odd::create([
                'fixture_id' => $fixture->id,
                'market_id'  => 1,
                'value'      => 'Home',
                'odd'        => 2.00,
                'status'     => 'pending',
            ]);

            $legs[] = [
                'fixture_id' => $fixture->id,
                'market_id'  => 1,
                'selection'  => 'Home',
            ];
        }

        $this->actingAs($host)
            ->withSession(['contest_draft' => $legs]);
    }

    public function test_confirm_page_exposes_seasons_prop(): void
    {
        $host = User::factory()->create();
        $this->seedDraft($host);

        $response = $this->actingAs($host)->get(route('contests.confirm'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Contests/Confirm')
            ->where('seasons', ['2026-27', '2027-28'])
        );
    }

    public function test_confirm_page_seasons_prop_tracks_config(): void
    {
        config()->set('marketplace.matchday.seasons', ['2026-27']);

        $host = User::factory()->create();
        $this->seedDraft($host);

        $response = $this->actingAs($host)->get(route('contests.confirm'));

        $response->assertInertia(fn ($page) => $page
            ->where('seasons', ['2026-27'])
        );
    }
}