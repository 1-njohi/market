<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The 32 markets the settlement engine resolves. Kept in sync with
     * MarketSettlementService::SETTLEABLE_MARKET_IDS and the sync
     * command's filter. Data migration so fresh test databases and new
     * environments are populated without a manual seed step.
     */
    private const MARKETS = [
        1  => 'Match Winner',
        2  => 'Home/Away',
        3  => 'Second Half Winner',
        5  => 'Goals Over/Under',
        6  => 'Goals Over/Under First Half',
        7  => 'HT/FT Double',
        8  => 'Both Teams Score',
        9  => 'Handicap Result',
        10 => 'Exact Score',
        12 => 'Double Chance',
        13 => 'First Half Winner',
        14 => 'Team To Score First',
        15 => 'Team To Score Last',
        16 => 'Total - Home',
        17 => 'Total - Away',
        20 => 'Double Chance - First Half',
        21 => 'Odd/Even',
        22 => 'Odd/Even - First Half',
        24 => 'Results/Both Teams Score',
        25 => 'Result/Total Goals',
        26 => 'Goals Over/Under - Second Half',
        29 => 'Win to Nil - Home',
        30 => 'Win to Nil - Away',
        32 => 'Win Both Halves',
        34 => 'Both Teams Score - First Half',
        38 => 'Exact Goals Number',
        40 => 'Home Team Exact Goals Number',
        41 => 'Away Team Exact Goals Number',
        42 => 'Second Half Exact Goals Number',
        43 => 'Home Team Score a Goal',
        44 => 'Away Team Score a Goal',
        46 => 'Exact Goals Number - First Half',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::MARKETS as $id => $name) {
            DB::table('markets')->updateOrInsert(
                ['id' => $id],
                ['name' => $name, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('markets')->whereIn('id', array_keys(self::MARKETS))->delete();
    }
};