<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Odd;
use App\Models\Team;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FootballDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Countries ───
        $countries = [
            ['name' => 'England', 'code' => 'GB', 'flag' => 'https://media.api-sports.io/flags/gb-eng.svg'],
            ['name' => 'Spain', 'code' => 'ES', 'flag' => 'https://media.api-sports.io/flags/es.svg'],
            ['name' => 'Germany', 'code' => 'DE', 'flag' => 'https://media.api-sports.io/flags/de.svg'],
        ];
        foreach ($countries as $c) {
            Country::updateOrCreate(['name' => $c['name']], $c);
        }

        // ─── Leagues (matches SeedLeagues shape: id_on_api, name, country, type, logo) ───
        $leaguesData = [
            ['id_on_api' => 39, 'name' => 'Premier League', 'country' => 'England', 'season' => 2022, 'round' => 'Regular Season - 1'],
            ['id_on_api' => 140, 'name' => 'La Liga', 'country' => 'Spain', 'season' => 2022, 'round' => 'Regular Season - 1'],
            ['id_on_api' => 78, 'name' => 'Bundesliga', 'country' => 'Germany', 'season' => 2022, 'round' => 'Regular Season - 1'],
        ];
        foreach ($leaguesData as $l) {
            $country = Country::where('name', $l['country'])->first();

            League::updateOrCreate(
                ['id_on_api' => $l['id_on_api']],
                [
                    'name' => $l['name'],
                    'country_id' => $country?->id,
                    'logo' => "https://media.api-sports.io/football/leagues/{$l['id_on_api']}.png",
                    'priority' => 1,
                ]
            );
        }

        // ─── Teams ───
        $teamsData = [
            ['id' => 52, 'name' => 'Crystal Palace'],
            ['id' => 42, 'name' => 'Arsenal'],
            ['id' => 33, 'name' => 'Manchester United'],
            ['id' => 40, 'name' => 'Liverpool'],
            ['id' => 529, 'name' => 'Barcelona'],
            ['id' => 541, 'name' => 'Real Madrid'],
            ['id' => 157, 'name' => 'Bayern Munich'],
            ['id' => 165, 'name' => 'Borussia Dortmund'],
        ];
        foreach ($teamsData as $t) {
            Team::updateOrCreate(
                ['id_on_api' => $t['id']],
                [
                    'name' => $t['name'],
                    'logo' => "https://media.api-sports.io/football/teams/{$t['id']}.png",
                    'colors' => null,
                ]
            );
        }

        // ─── Venues ───
        $venuesData = [
            ['id' => 525, 'name' => 'Selhurst Park', 'city' => 'London'],
            ['id' => 494, 'name' => 'Emirates Stadium', 'city' => 'London'],
            ['id' => 556, 'name' => 'Old Trafford', 'city' => 'Manchester'],
            ['id' => 550, 'name' => 'Anfield', 'city' => 'Liverpool'],
            ['id' => 19939, 'name' => 'Spotify Camp Nou', 'city' => 'Barcelona'],
            ['id' => 145, 'name' => 'Santiago Bernabéu', 'city' => 'Madrid'],
            ['id' => 700, 'name' => 'Allianz Arena', 'city' => 'Munich'],
            ['id' => 720, 'name' => 'Signal Iduna Park', 'city' => 'Dortmund'],
        ];
        foreach ($venuesData as $v) {
            Venue::updateOrCreate(['id_on_api' => $v['id']], ['name' => $v['name'], 'city' => $v['city']]);
        }

        // ─── Fixtures ───
        // home_team_id / away_team_id store API team IDs, not local FKs (per your schema)
        $fixturesData = [
            ['id' => 867946, 'home' => 52, 'away' => 42, 'league' => 39, 'venue' => 525, 'gh' => 0, 'ga' => 2, 'hh' => 0, 'ha' => 1, 'status' => 'FT'],
            ['id' => 867951, 'home' => 33, 'away' => 40, 'league' => 39, 'venue' => 556, 'gh' => 2, 'ga' => 1, 'hh' => 1, 'ha' => 1, 'status' => 'FT'],
            ['id' => 867955, 'home' => 529, 'away' => 541, 'league' => 140, 'venue' => 19939, 'gh' => 1, 'ga' => 3, 'hh' => 0, 'ha' => 2, 'status' => 'FT'],
            ['id' => 867960, 'home' => 157, 'away' => 165, 'league' => 78, 'venue' => 700, 'gh' => 3, 'ga' => 3, 'hh' => 2, 'ha' => 1, 'status' => 'FT'],
            // Upcoming
            ['id' => 900001, 'home' => 42, 'away' => 33, 'league' => 39, 'venue' => 494, 'days' => 2, 'status' => 'NS'],
            ['id' => 900002, 'home' => 40, 'away' => 52, 'league' => 39, 'venue' => 550, 'days' => 3, 'status' => 'NS'],
            ['id' => 900003, 'home' => 541, 'away' => 529, 'league' => 140, 'venue' => 145, 'days' => 4, 'status' => 'NS'],
            ['id' => 900004, 'home' => 165, 'away' => 157, 'league' => 78, 'venue' => 720, 'days' => 5, 'status' => 'NS'],
            ['id' => 900005, 'home' => 33, 'away' => 42, 'league' => 39, 'venue' => 556, 'days' => 6, 'status' => 'NS'],
            ['id' => 900006, 'home' => 52, 'away' => 40, 'league' => 39, 'venue' => 525, 'days' => 7, 'status' => 'NS'],
        ];

        foreach ($fixturesData as $t) {
            $date = isset($t['days'])
                ? Carbon::now()->addDays($t['days'])->setTime(19, 0)
                : Carbon::parse('2022-08-05 19:00:00');

            $league = League::where('id_on_api', $t['league'])->first();
            $venue = Venue::where('id_on_api', $t['venue'])->first();

            $hg = $t['gh'] ?? null;
            $ag = $t['ga'] ?? null;
            $homeWinner = ($hg !== null && $ag !== null) ? $hg > $ag : false;

            $fixture = Fixture::updateOrCreate(
                ['id_on_api' => $t['id']],
                [
                    'referee' => 'A. Taylor',
                    'timezone' => 'UTC',
                    'date' => $date,
                    'timestamp' => $date->timestamp,
                    'venue_id' => $venue?->id,
                    'league_id' => $league?->id,
                    'home_team_id' => $t['home'],   // API ID
                    'away_team_id' => $t['away'],   // API ID
                    'status_long' => $t['status'] === 'FT' ? 'Match Finished' : 'Not Started',
                    'status_short' => $t['status'],
                    'status_elapsed' => $t['status'] === 'FT' ? 90 : null,
                    'goals_home' => $hg,
                    'goals_away' => $ag,
                    'halftime_home' => $t['hh'] ?? null,
                    'halftime_away' => $t['ha'] ?? null,
                    'fulltime_home' => $hg,
                    'fulltime_away' => $ag,
                    'home_winner' => $homeWinner,
                    'settled' => false,
                ]
            );

            $this->seedOddsForFixture($fixture, $t);
        }

        $this->command->info("✓ Football data seeded (" . count($fixturesData) . " fixtures)");
    }

    /**
     * Generate a realistic set of odds for a fixture.
     * For finished fixtures, odd.status is set based on the final result.
     */
    private function seedOddsForFixture(Fixture $fixture, array $t): void
    {
        $seed = $fixture->id_on_api;
        $r = fn($min, $max) => round($min + (($seed * 13 + rand(1, 97)) % 100) / 100 * ($max - $min), 2);

        $rows = [
            // Market 1 — Match Winner
            ['m' => 1, 'v' => 'Home', 'o' => $r(1.5, 6.0)],
            ['m' => 1, 'v' => 'Draw', 'o' => $r(2.8, 4.5)],
            ['m' => 1, 'v' => 'Away', 'o' => $r(1.5, 6.0)],
            // Market 5 — Goals Over/Under (2.5)
            ['m' => 5, 'v' => 'Over 2.5', 'o' => $r(1.6, 2.4)],
            ['m' => 5, 'v' => 'Under 2.5', 'o' => $r(1.5, 2.2)],
            // Market 8 — BTTS
            ['m' => 8, 'v' => 'Yes', 'o' => $r(1.5, 2.2)],
            ['m' => 8, 'v' => 'No', 'o' => $r(1.5, 2.2)],
            // Market 12 — Double Chance
            ['m' => 12, 'v' => 'Home/Draw', 'o' => $r(1.2, 2.0)],
            ['m' => 12, 'v' => 'Draw/Away', 'o' => $r(1.2, 2.0)],
            ['m' => 12, 'v' => 'Home/Away', 'o' => $r(1.2, 2.0)],
            // Market 13 — First Half Winner
            ['m' => 13, 'v' => 'Home', 'o' => $r(2.5, 4.5)],
            ['m' => 13, 'v' => 'Draw', 'o' => $r(2.0, 3.5)],
            ['m' => 13, 'v' => 'Away', 'o' => $r(2.5, 4.5)],
            // Market 14 — Team To Score First
            ['m' => 14, 'v' => 'Home', 'o' => $r(1.5, 2.5)],
            ['m' => 14, 'v' => 'Away', 'o' => $r(1.5, 2.5)],
            // Market 21 — Odd/Even
            ['m' => 21, 'v' => 'Odd', 'o' => $r(1.8, 2.0)],
            ['m' => 21, 'v' => 'Even', 'o' => $r(1.8, 2.0)],
            // Market 38 — Exact Goals Number
            ['m' => 38, 'v' => '0', 'o' => $r(7, 12)],
            ['m' => 38, 'v' => '1', 'o' => $r(4, 6)],
            ['m' => 38, 'v' => '2', 'o' => $r(3, 5)],
            ['m' => 38, 'v' => '3', 'o' => $r(4, 6)],
            ['m' => 38, 'v' => '4', 'o' => $r(7, 12)],
            ['m' => 38, 'v' => '5', 'o' => $r(12, 20)],
        ];

        foreach ($rows as $row) {
            $status = 'pending';
            if ($t['status'] === 'FT' && isset($t['gh'], $t['ga'])) {
                $status = $this->resolveOddStatus($row['m'], $row['v'], $t);
            }

            Odd::updateOrCreate(
                ['fixture_id' => $fixture->id, 'market_id' => $row['m'], 'value' => $row['v']],
                ['odd' => $row['o'], 'status' => $status]
            );
        }
    }

    private function resolveOddStatus(int $marketId, string $value, array $t): string
    {
        $hg = $t['gh'];
        $ag = $t['ga'];
        $hh = $t['hh'] ?? null;
        $ha = $t['ha'] ?? null;
        $total = $hg + $ag;

        $winner = match ($marketId) {
            1 => $hg > $ag ? 'Home' : ($ag > $hg ? 'Away' : 'Draw'),
            5 => $total > 2.5 ? 'Over 2.5' : 'Under 2.5',
            8 => ($hg > 0 && $ag > 0) ? 'Yes' : 'No',
            12 => $hg > $ag ? 'Home/Draw' : ($ag > $hg ? 'Draw/Away' : 'Home/Draw'),
            13 => ($hh !== null && $ha !== null)
                ? ($hh > $ha ? 'Home' : ($ha > $hh ? 'Away' : 'Draw'))
                : null,
            14 => null, // requires event data
            21 => $total % 2 === 0 ? 'Even' : 'Odd',
            38 => $total <= 5 ? (string) $total : '5',
            default => null,
        };

        if ($winner === null)
            return 'pending';
        return $value === $winner ? 'won' : 'lost';
    }
}