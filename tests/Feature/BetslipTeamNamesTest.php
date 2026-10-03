<?php

namespace Tests\Feature;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Tests\TestCase;

class BetslipTeamNamesTest extends TestCase
{
    use DatabaseMigrations;

    public function test_show_page_renders_actual_team_names(): void
    {
        $home = Team::create(['id_on_api' => 900001, 'name' => 'Home FC']);
        $away = Team::create(['id_on_api' => 900002, 'name' => 'Away FC']);

        $league = League::create([
            'id_on_api'  => 900100,
            'sport_id'   => 1,
            'name'       => 'Test League',
            'country_id' => 1,
            'country'    => 'Test Country',
            'season'     => 2026,
        ]);

        $fixture = Fixture::create([
            'id_on_api'    => 900999,
            'date'         => now()->addHours(2)->toDateTimeString(),
            'timestamp'    => now()->addHours(2)->timestamp,
            'status_short' => 'NS',
            'league_id'    => $league->id,
            'home_team_id' => $home->id_on_api,
            'away_team_id' => $away->id_on_api,
        ]);

        $market = Market::firstOrCreate(['id' => 1], ['name' => 'Match Winner']);

        $odd = Odd::create([
            'fixture_id' => $fixture->id,
            'market_id'  => $market->id,
            'value'      => 'Home',
            'odd'        => 2.50,
            'status'     => 'pending',
        ]);

        $seller = User::factory()->create([
            'name' => 'Seller',
            'code' => strtoupper(Str::random(8)),
        ]);

        $betslip = Betslip::create([
            'user_id'    => $seller->id,
            'code'       => 'ABC-DEFG-HIJ',
            'price'      => 100,
            'total_odds' => 2.50,
            'status'     => 'pending',
            'remaining'  => 1,
            'is_winner'  => false,
        ]);

        $betslip->odds()->attach($odd->id, [
            'odd_value_at_time' => 2.50,
            'status'            => 'pending',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $response = $this->get('/betslip/view/g/ABC-DEFG-HIJ');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Betslip')
            ->where('betslip.legs.0.fixture.home_team', 'Home FC')
            ->where('betslip.legs.0.fixture.away_team', 'Away FC')
        );
    }
}