<?php

namespace App\Console\Commands;

use App\Models\Market;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-markets')]
#[Description('Command description')]
class SeedMarkets extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $markets = [
            1 => "Match Winner",
            2 => "Home/Away",
            3 => "Second Half Winner",
            5 => "Goals Over/Under",
            6 => "Goals Over/Under First Half",
            7 => "HT/FT Double",
            8 => "Both Teams Score",
            9 => "Handicap Result",
            10 => "Exact Score",
            12 => "Double Chance",
            13 => "First Half Winner",
            14 => "Team To Score First",
            15 => "Team To Score Last",
            16 => "Total - Home",
            17 => "Total - Away",
            20 => "Double Chance - First Half",
            21 => "Odd/Even",
            22 => "Odd/Even - First Half",
            24 => "Results/Both Teams Score",
            25 => "Result/Total Goals",
            26 => "Goals Over/Under - Second Half",
            29 => "Win to Nil - Home",
            30 => "Win to Nil - Away",
            32 => "Win Both Halves",
            34 => "Both Teams Score - First Half",
            38 => "Exact Goals Number",
            40 => "Home Team Exact Goals Number",
            41 => "Away Team Exact Goals Number",
            42 => "Second Half Exact Goals Number",
            43 => "Home Team Score a Goal",
            44 => "Away Team Score a Goal",
            46 => "Exact Goals Number - First Half"
        ];
        
        foreach ($markets as $id => $name) {
            Market::updateOrCreate(
                ['id' => $id], 
                ['name' => $name]
            );
        }
    }
}
