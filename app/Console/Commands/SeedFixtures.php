<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Team;
use App\Models\Venue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:seed-fixtures')]
#[Description('Seed fixtures from the API into the consolidated schema')]
class SeedFixtures extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Example: Premier League 2022/23
        $leagueId = 39;
        $season = 2022;
        // $uri = config('services.api_sports.base_url') . '/fixtures?league=' . $leagueId . '&season=' . $season . '&timezone=Africa%2FNairobi';

        $uri = "https://v3.football.api-sports.io/fixtures?league=39&season=2022&from=2022-08-01&to=2022-08-08";
        $response = Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->get($uri);

        if (!$response->successful()) {
            $this->error('API request failed with status: ' . $response->status());
            return 1;
        }

        $data = $response->json();
        $fixtures = $data['response'];

        $this->info('Fetched ' . count($fixtures) . ' fixtures.');

        // Cache league mappings (optional)
        $leagues = League::pluck('id', 'id_on_api');

        foreach ($fixtures as $fixture) {
            $this->processFixture($fixture, $leagues);
        }

        $this->info('Fixtures seeded successfully.');
        return 0;
    }

    /**
     * Process a single fixture from the API response.
     */
    protected function processFixture(array $fixture, $leagues)
    {
        // 1. Venue
        $venueData = $fixture['fixture']['venue'] ?? null;
        if ($venueData) {
            $venue = Venue::updateOrCreate(
                ['id_on_api' => $venueData['id']],
                [
                    'name' => $venueData['name'],
                    'city' => $venueData['city'] ?? null,
                ]
            );
            $venueId = $venue->id;
        } else {
            $venueId = null;
        }

        // 2. League
        $leagueApiId = $fixture['league']['id'];
        $league = League::updateOrCreate(
            ['id_on_api' => $leagueApiId],
            [
                'name' => $fixture['league']['name'],
                'country' => $fixture['league']['country'] ?? null,
                'logo' => $fixture['league']['logo'] ?? null,
                'flag' => $fixture['league']['flag'] ?? null,
                'season' => $fixture['league']['season'] ?? null,
                'round' => $fixture['league']['round'] ?? null,
                'standings' => $fixture['league']['standings'] ?? false,
            ]
        );
        $leagueId = $league->id;

        // 3. Home Team
        $homeTeamData = $fixture['teams']['home'];
        $homeTeam = Team::updateOrCreate(
            ['id_on_api' => $homeTeamData['id']],
            [
                'name' => $homeTeamData['name'],
                'logo' => $homeTeamData['logo'] ?? null,
                // colors might be available in lineups but we skip for now
            ]
        );
        $homeTeamId = $homeTeamData['id'];

        // 4. Away Team
        $awayTeamData = $fixture['teams']['away'];
        $awayTeam = Team::updateOrCreate(
            ['id_on_api' => $awayTeamData['id']],
            [
                'name' => $awayTeamData['name'],
                'logo' => $awayTeamData['logo'] ?? null,
            ]
        );
        $awayTeamId = $awayTeamData['id'];

        // 5. Fixture
        $fixtureApiId = $fixture['fixture']['id'];

        // Determine if match has started or finished
        $status = $fixture['fixture']['status'];
        $statusShort = $status['short'] ?? 'NS'; // NS = Not Started

        // If match is not finished yet, we don't have goals/scores.
        // Store them as null (or 0) – but use null if not available.
        $goalsHome = 0; // $fixture['goals']['home'] ?? null;
        $goalsAway = 0; //$fixture['goals']['away'] ?? null;

        $halftimeHome = null; //$fixture['score']['halftime']['home'] ?? null;
        $halftimeAway = null; //$fixture['score']['halftime']['away'] ?? null;
        $fulltimeHome = null; //$fixture['score']['fulltime']['home'] ?? null;
        $fulltimeAway = null; //$fixture['score']['fulltime']['away'] ?? null;
        $extratimeHome = null; //$fixture['score']['extratime']['home'] ?? null;
        $extratimeAway = null; //$fixture['score']['extratime']['away'] ?? null;
        $penaltyHome =  null; //$fixture['score']['penalty']['home'] ?? null;
        $penaltyAway = null; //$fixture['score']['penalty']['away'] ?? null;

        // Determine home_winner if fulltime scores are available
        $homeWinner = false;
        if ($fulltimeHome !== null && $fulltimeAway !== null) {
            $homeWinner = $fulltimeHome > $fulltimeAway;
        }

        Fixture::updateOrCreate(
            ['id_on_api' => $fixtureApiId],
            [
                'referee' => $fixture['fixture']['referee'] ?? null,
                'timezone' => $fixture['fixture']['timezone'] ?? null,
                'date' => $fixture['fixture']['date'] ?? null,
                'timestamp' => $fixture['fixture']['timestamp'] ?? null,
                'period_first' => $fixture['fixture']['periods']['first'] ?? null,
                'period_second' => $fixture['fixture']['periods']['second'] ?? null,
                'venue_id' => $venueId,
                'league_id' => $leagueId,
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'status_long' => "Not Started", //$status['long'] ?? null,
                'status_short' => "NS", //$statusShort,
                'status_elapsed' => $status['elapsed'] ?? null,
                'status_extra' => $status['extra'] ?? null,
                'goals_home' => null,// $goalsHome,
                'goals_away' => null, //$goalsAway,
                'halftime_home' => $halftimeHome,
                'halftime_away' => $halftimeAway,
                'fulltime_home' => $fulltimeHome,
                'fulltime_away' => $fulltimeAway,
                'extratime_home' => $extratimeHome,
                'extratime_away' => $extratimeAway,
                'penalty_home' => $penaltyHome,
                'penalty_away' => $penaltyAway,
                'home_winner' => $homeWinner,
                'settled' => false, // always false for new fixtures
            ]
        );
    }
}