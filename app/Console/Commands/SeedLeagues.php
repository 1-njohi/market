<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Models\Country;
use App\Models\League;

#[Signature('app:seed-leagues')]
#[Description('Command description')]
class SeedLeagues extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->get(config('services.api_sports.base_url') . '/leagues');

        \Log::info($response);


        if ($response->successful()) {
            $countries = Country::pluck('id', 'name');
            $data = $response->json();
            $leagues = $data['response'];

            foreach ($leagues as $league) {
                $api_country_name = $league['country']['name']; 
                // 2. Look up the ID from our collection map
                $country_id = $countries->get($api_country_name);

                League::create([
                    'country_id' => $country_id,
                    'id_on_api' => $league['league']['id'],
                    'name' => $league['league']['name'],
                    'type' => $league['league']['type'],
                    'logo' => $league['league']['logo']
                ]);

            }

        } else {
            // Handle errors (400s, 500s)
            $statusCode = $response->status();
            throw new \Exception("API request failed with status: {$statusCode}");
        }
    }
}
