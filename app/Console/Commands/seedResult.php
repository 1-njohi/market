<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\FixtureGoals;
use App\Models\FixtureScore;
use App\Models\FixtureStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

#[Signature('app:seed-result {fixture_id}')]
#[Description('Command description')]
class seedResult extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fixture_id = $this->argument('fixture_id');
        $response = Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->post(config('app.url') . '/api/result', [
                    'fixture_id' => $fixture_id
                ]);
        $fixture = $response['fixture'];
        $status = $fixture['status'];
        $goals = $response['goals'];
        $score = $response['score'];

        if ($status['short'] == 'FT') {
            $this->line("Match complete");
            $fixture_on_db = Fixture::query()->where('id_on_api', $fixture_id)->first();

            // Record Fixture Status
            $fixture_status = FixtureStatus::query()->where('fixture_id', $fixture_on_db->id)->first();
            $fixture_status->fixture_id = $fixture_on_db->id;
            $fixture_status->long = $fixture['status']['long'];
            $fixture_status->short = $fixture['status']['short'];
            $fixture_status->elapsed = $fixture['status']['elapsed'];
            $fixture_status->save();

            // Record Fixture Goals
            $fixture_goals = FixtureGoals::query()->where('fixture_id', $fixture_on_db->id)->first();
            $fixture_goals->fixture_id = $fixture_on_db->id;
            $fixture_goals->home = $goals['home'];
            $fixture_goals->away = $goals['away'];
            $fixture_goals->save();

            // Record Scores
            $fixture_score = FixtureScore::query()->where('fixture_id', $fixture_on_db->id)->first();
            $fixture_score->fixture_id = $fixture_on_db->id;
            $fixture_score->halftime_home = $score['halftime']['home'];
            $fixture_score->halftime_away = $score['halftime']['away'];
            $fixture_score->fulltime_home = $score['fulltime']['home'];
            $fixture_score->fulltime_away = $score['fulltime']['away'];
            $fixture_score->extratime_home = $score['extratime']['home'];
            $fixture_score->extratime_away = $score['extratime']['away'];
            $fixture_score->penalty_home = $score['penalty']['home'];
            $fixture_score->penalty_away = $score['penalty']['away'];
            $fixture_score->save();
        } else {
            $this->line("Match not finished");
        }
        // $fixture_id = $this->argument('fixture_id');
        // \Log::info($fixture_id);
        // \Log::info($response);

    }
}
