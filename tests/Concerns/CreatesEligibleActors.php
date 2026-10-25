<?php

namespace Tests\Concerns;

use App\Models\Betslip;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds users with enough settled picks to clear the seller eligibility
 * threshold (20 picks, 55% win rate).
 *
 * Self-contained: builds its own league, teams, and fixtures. Market ids
 * for the seeded odds start at 100+ so this trait never collides with the
 * market 1 odds that a consuming test's own fixture helper may have
 * inserted.
 *
 * Every eligible-pick odds row is placed at (fixture_id, market_id=100+i,
 * value='Home'). No collision with `Market 1 Home` or `Market 2 Home`.
 */
trait CreatesEligibleActors
{
    /**
     * Give the user a rolling record that clears the hosting threshold:
     * 20 settled picks, 12 won, 8 lost → 60% win rate.
     */
    protected function makeEligibleHost(User $host): void
    {
        $league = League::firstOrCreate(
            ['id_on_api' => 999100],
            [
                'sport_id'   => 1,
                'name'       => 'Eligible Actors League',
                'country_id' => 1,
                'country'    => 'Test',
                'season'     => 2026,
            ]
        );

        for ($i = 0; $i < 20; $i++) {
            $home = Team::firstOrCreate(
                ['id_on_api' => 990000 + $i * 2],
                ['name' => "ActorHome {$i}"],
            );
            $away = Team::firstOrCreate(
                ['id_on_api' => 990001 + $i * 2],
                ['name' => "ActorAway {$i}"],
            );

            $fixture = Fixture::forceCreate([
                'id_on_api'    => 991000 + $i,
                'date'         => now()->subDays(2),
                'timestamp'    => now()->subDays(2)->timestamp,
                'status_short' => 'FT',
                'league_id'    => $league->id,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'home_winner'  => false,
                'settled'      => true,
            ]);

            $odd = Odd::create([
                'fixture_id' => $fixture->id,
                'market_id'  => 100 + $i,
                'value'      => 'Home',
                'odd'        => 2.00,
                'status'     => 'pending',
            ]);

            $slip = Betslip::forceCreate([
                'id'             => (string) Str::uuid(),
                'user_id'        => $host->id,
                'code'           => strtoupper(Str::random(10)),
                'total_odds'     => 2.00,
                'price'          => 100,
                'status'         => 'settled',
                'remaining'      => 0,
                'is_winner'      => $i < 12,
                'is_sellable'    => false,
                'is_delisted'    => false,
                'priority_score' => 1,
            ]);

            DB::table('betslip_odd')->insert([
                'betslip_id'        => $slip->id,
                'odd_id'            => $odd->id,
                'status'            => $i < 12 ? 'won' : 'lost',
                'odd_value_at_time' => 2.00,
                'created_at'        => now()->subDay(),
                'updated_at'        => now()->subDay(),
            ]);
        }
    }
}