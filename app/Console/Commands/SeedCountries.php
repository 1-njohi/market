<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:seed-countries')]
#[Description('Command description')]
class SeedCountries extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->get(config('services.api_sports.base_url') . '/countries');

        if ($response->successful()) {
            $data = $response->json();
            $countries = $data['response'];

            foreach ($countries as $country) {
                Country::create($country);
            }

        } else {
            // Handle errors (400s, 500s)
            $statusCode = $response->status();
            throw new \Exception("API request failed with status: {$statusCode}");
        }
    }
}
