<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\FixtureGoals;
use App\Models\FixtureScore;
use App\Models\FixtureStatus;
use App\Models\League;
use App\Models\Team;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:seed-fixtures')]
#[Description('Command description')]
class SeedFixtures extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uri = config('services.api_sports.base_url') . '/fixtures?league=' . 39 . '&season=' . 2022 . '&timezone=Africa%2FNairobi';

        $response = Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->get($uri);

        \Log::info($response);

        if ($response->successful()) {
            $data = $response->json();
            $fixtures = $data['response'];

            $leagues = League::pluck('id', 'id_on_api');

            foreach ($fixtures as $fixture) {
                // Check if teams exist, if not, create
                $home_team_id_on_api = $fixture['teams']['home']['id'];
                $home_team_exists = Team::where('id_on_api', $home_team_id_on_api)->exists();
                if (!$home_team_exists) {
                    $home_team = new Team;
                    $home_team->id_on_api = $home_team_id_on_api;
                    $home_team->name = $fixture['teams']['home']['name'];
                    $home_team->logo = $fixture['teams']['home']['logo'];
                    $home_team->save();
                }

                $away_team_id_on_api = $fixture['teams']['away']['id'];
                $away_team_exists = Team::where('id_on_api', $away_team_id_on_api)->exists();
                if (!$away_team_exists) {
                    $away_team = new Team;
                    $away_team->id_on_api = $away_team_id_on_api;
                    $away_team->name = $fixture['teams']['away']['name'];
                    $away_team->logo = $fixture['teams']['away']['logo'];
                    $away_team->save();
                }

                $api_league_id = $fixture['league']['id'];
                $leagueId = $leagues->get($api_league_id);

                // Check if fixure exists, if not, create
                $fixture_id_on_api = $fixture['fixture']['id'];
                $fixture_exists = Fixture::where('id_on_api', $fixture_id_on_api)->exists();

                if (!$fixture_exists) {
                    $new_fixture = new Fixture;
                    $new_fixture->id_on_api = $fixture_id_on_api;
                    $new_fixture->date = $fixture['fixture']['date'];
                    $new_fixture->timestamp = $fixture['fixture']['timestamp'];
                    $new_fixture->league_id = $leagueId; //id on api
                    $new_fixture->home_team_id = $home_team_id_on_api;
                    $new_fixture->away_team_id = $fixture['teams']['away']['id'];
                    $new_fixture->save();

                    // Create fixture status
                    $fixture_status = new FixtureStatus;
                    $fixture_status->fixture_id = $new_fixture->id;
                    $fixture_status->long = $fixture['fixture']['status']['long'];
                    $fixture_status->short = $fixture['fixture']['status']['short'];
                    $fixture_status->elapsed = $fixture['fixture']['status']['elapsed'];
                    $fixture_status->save();

                    // Create fixture goals
                    $fixture_goals = new FixtureGoals;
                    $fixture_goals->fixture_id = $new_fixture->id;
                    $fixture_goals->home = $fixture['goals']['home'];
                    $fixture_goals->away = $fixture['goals']['away'];
                    $fixture_goals->save();

                    // Create fixture score
                    $fixture_score = new FixtureScore;
                    $fixture_score->fixture_id = $new_fixture->id;
                    $fixture_score->halftime_home = $fixture['score']['halftime']['home'];
                    $fixture_score->halftime_away = $fixture['score']['halftime']['away'];
                    $fixture_score->fulltime_home = $fixture['score']['fulltime']['home'];
                    $fixture_score->fulltime_away = $fixture['score']['fulltime']['away'];
                    $fixture_score->extratime_home = $fixture['score']['extratime']['home'];
                    $fixture_score->extratime_away = $fixture['score']['extratime']['away'];
                    $fixture_score->penalty_home = $fixture['score']['penalty']['home'];
                    $fixture_score->penalty_away = $fixture['score']['penalty']['away'];
                    $fixture_score -> save();
                }
            }

        } else {
            // Handle errors (400s, 500s)
            $statusCode = $response->status();
            throw new \Exception("API request failed with status: {$statusCode}");
        }
    }
}
