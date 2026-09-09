<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Odd;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Country;
use App\Models\League;

#[Signature('app:seed-odds')]
#[Description('Command description')]
class SeedOdds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Simulation of the whole thing

        $leagues = League::get();
        foreach ($leagues as $league) {
            if ($league->Fixtures()->count() > 0) {
                $this->comment("Checking " . $league['name'] . "...\n");
                $fixtures = $league->fixtures;

                foreach ($fixtures as $fixture) {
                    \Log::info($fixture);
                    $home_team = $fixture->HomeTeam->name;
                    $away_team = $fixture->AwayTeam->name;
                    $this->error("Checking " . $home_team . " VS " . $away_team . "...\n");

                    // Make call to API to get the odds

                    $response = Http::withHeaders([
                        'x-apisports-key' => config('services.api_sports.key'),
                    ])->post(config('app.url') . '/api/odds');

                    if ($response->successful()) {
                        $data = $response->json();
                        $odds = $data['response'][0]['bookmakers'][0]['bets'];
                        foreach ($odds as $odd) {
                            $market = Market::query()->where('name', $odd['name'])->first();

                            $this->info("\tLooking for odds for the market: " . $market->name);
                            foreach ($odd['values'] as $selection) {
                                $new_odd = new Odd;
                                $new_odd->fixture_id = $fixture->id;
                                $new_odd->market_id = $market->id;
                                $new_odd->value = $selection['value'];
                                $new_odd->odd = $selection['odd'];
                                $new_odd->save();
                                $this->warn("\t\tJust added selection " . $new_odd->value . " with the odds " . $new_odd->odd);
                            }
                        }
                    } else {
                        // Handle errors (400s, 500s)
                        $statusCode = $response->status();
                        throw new \Exception("API request failed with status: {$statusCode}");
                    }



                    // // Generate a random number of microseconds between 200,000 and 1,500,000
                    // $microSeconds = random_int(200000000, 1500000000);

                    // // Sleep for the randomly generated duration
                    // usleep($microSeconds);

                }
            }
        }
    }
}
