<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\Odd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('app:resolve-bets {fixture_id_on_api}')]
#[Description('Command description')]
class resolveBets extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fixture:resolve {fixture_id_on_api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve all odds for a fixture based on the final result';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get fixture ID from argument
        $fixture_id_on_api = $this->argument('fixture_id_on_api');

        // Find the fixture
        $fixture = Fixture::where('id_on_api', $fixture_id_on_api)->first();

        if (!$fixture) {
            $this->error("Fixture with id_on_api {$fixture_id_on_api} not found.");
            return 1;
        }

        // Get the goals (you need to define how to get these)
        // Assuming you have these fields or relations
        $fulltime_home = $fixture->score->fulltime_home ?? $fixture->score->fulltime_home ?? null;
        $fulltime_away = $fixture->score->fulltime_away ?? $fixture->score ->fulltime_away ?? null;

        if ($fulltime_home === null || $fulltime_away === null) {
            $this->error("Fixture {$fixture_id_on_api} does not have fulltime scores.");
            return 1;
        }

        $this->info("Resolving odds for fixture {$fixture_id_on_api} (Home: {$fulltime_home}, Away: {$fulltime_away})");

        // Resolve all markets
        $this->resolveMarket1($fixture, $fulltime_home, $fulltime_away);
        $this->resolveMarket7($fixture, $fulltime_home, $fulltime_away);
        // Add other market resolvers here

        $this->info("Odds resolved successfully for fixture {$fixture_id_on_api}");

        return 0;
    }

    /**
     * Resolve Market 1 (Match Winner)
     */
    private function resolveMarket1($fixture, $fulltime_home, $fulltime_away)
    {
        DB::beginTransaction();

        try {
            // Determine the winner value
            if ($fulltime_home > $fulltime_away) {
                $winnerValue = 'Home';
            } elseif ($fulltime_home < $fulltime_away) {
                $winnerValue = 'Away';
            } else {
                $winnerValue = 'Draw';
            }

            // Find the winning odd
            $wonOdd = $fixture->odds()
                ->where('market_id', 1)
                ->where('value', $winnerValue)
                ->first();

            if ($wonOdd) {
                $wonOdd->status = 'won';
                $wonOdd->save();
                $this->info("Market 1 - Winner: {$winnerValue} (Odd ID: {$wonOdd->id})");
            }

            // Update all other odds to 'lost'
            $lostCount = $fixture->odds()
                ->where('market_id', 1)
                ->where('status', 'pending')
                ->where('id', '!=', $wonOdd?->id)
                ->update(['status' => 'lost']);

            $this->info("Market 1 - Lost odds updated: {$lostCount}");

            DB::commit();

            // Update betslips that contain these odds
            $this->updateBetslipsForOdd($wonOdd?->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving Market 1 for fixture {$fixture->id_on_api}: " . $e->getMessage());
            $this->error("Error resolving Market 1: " . $e->getMessage());
        }
    }

    /**
     * Resolve Market 7 (Both Teams to Score)
     */
    private function resolveMarket7($fixture, $fulltime_home, $fulltime_away)
    {
        DB::beginTransaction();

        try {
            $bothTeamsScored = $fulltime_home > 0 && $fulltime_away > 0;
            $winnerValue = $bothTeamsScored ? 'Yes' : 'No';

            $wonOdd = $fixture->odds()
                ->where('market_id', 7)
                ->where('value', $winnerValue)
                ->first();

            if ($wonOdd) {
                $wonOdd->status = 'won';
                $wonOdd->save();
                $this->info("Market 7 - Winner: {$winnerValue} (Odd ID: {$wonOdd->id})");
            }

            $lostCount = $fixture->odds()
                ->where('market_id', 7)
                ->where('status', 'pending')
                ->where('id', '!=', $wonOdd?->id)
                ->update(['status' => 'lost']);

            $this->info("Market 7 - Lost odds updated: {$lostCount}");

            DB::commit();

            // Update betslips
            $this->updateBetslipsForOdd($wonOdd?->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving Market 7 for fixture {$fixture->id_on_api}: " . $e->getMessage());
            $this->error("Error resolving Market 7: " . $e->getMessage());
        }
    }

    /**
     * Resolve Market 14 (Total Goals - Home)
     */
    private function resolveMarket14($fixture, $fulltime_home)
    {
        DB::beginTransaction();

        try {
            // Get all over/under odds for market 14
            $odds = $fixture->odds()
                ->where('market_id', 14)
                ->where('status', 'pending')
                ->get();

            foreach ($odds as $odd) {
                $value = $odd->value;
                $isOver = strpos($value, 'Over') !== false;
                $threshold = (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $isWinner = false;
                if ($isOver) {
                    $isWinner = $fulltime_home > $threshold;
                } else {
                    $isWinner = $fulltime_home < $threshold;
                }

                $odd->status = $isWinner ? 'won' : 'lost';
                $odd->save();

                if ($isWinner) {
                    $this->info("Market 14 - Winner: {$value} (Odd ID: {$odd->id})");
                    $this->updateBetslipsForOdd($odd->id);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving Market 14 for fixture {$fixture->id_on_api}: " . $e->getMessage());
            $this->error("Error resolving Market 14: " . $e->getMessage());
        }
    }

    /**
     * Resolve Market 4 (Goals Over/Under)
     */
    private function resolveMarket15($fixture, $fulltime_home, $fulltime_away)
    {
        $totalGoals = $fulltime_home + $fulltime_away;

        DB::beginTransaction();

        try {
            $odds = $fixture->odds()
                ->where('market_id', 15)
                ->where('status', 'pending')
                ->get();

            foreach ($odds as $odd) {
                $value = $odd->value;
                $isOver = strpos($value, 'Over') !== false;
                $threshold = (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $isWinner = false;
                if ($isOver) {
                    $isWinner = $totalGoals > $threshold;
                } else {
                    $isWinner = $totalGoals < $threshold;
                }

                $odd->status = $isWinner ? 'won' : 'lost';
                $odd->save();

                if ($isWinner) {
                    $this->info("Market 4 - Winner: {$value} (Odd ID: {$odd->id})");
                    $this->updateBetslipsForOdd($odd->id);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving Market 15 for fixture {$fixture->id_on_api}: " . $e->getMessage());
            $this->error("Error resolving Market 15: " . $e->getMessage());
        }
    }

    /**
     * Resolve Exact Goals Number (Market 26)
     */
    private function resolveMarket26($fixture, $fulltime_home, $fulltime_away)
    {
        $totalGoals = $fulltime_home + $fulltime_away;
        $goalKey = $totalGoals >= 10 ? '10+' : (string) $totalGoals;

        DB::beginTransaction();

        try {
            $wonOdd = $fixture->odds()
                ->where('market_id', 26)
                ->where('value', $goalKey)
                ->first();

            if ($wonOdd) {
                $wonOdd->status = 'won';
                $wonOdd->save();
                $this->info("Market 26 - Winner: {$goalKey} (Odd ID: {$wonOdd->id})");
            }

            $lostCount = $fixture->odds()
                ->where('market_id', 26)
                ->where('status', 'pending')
                ->where('id', '!=', $wonOdd?->id)
                ->update(['status' => 'lost']);

            $this->info("Market 26 - Lost odds updated: {$lostCount}");

            DB::commit();

            $this->updateBetslipsForOdd($wonOdd?->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving Market 26 for fixture {$fixture->id_on_api}: " . $e->getMessage());
            $this->error("Error resolving Market 26: " . $e->getMessage());
        }
    }

    /**
     * Resolve all markets for a fixture
     */
    private function resolveAllMarkets($fixture, $fulltime_home, $fulltime_away)
    {
        $this->resolveMarket1($fixture, $fulltime_home, $fulltime_away);
        $this->resolveMarket4($fixture, $fulltime_home, $fulltime_away);
        $this->resolveMarket7($fixture, $fulltime_home, $fulltime_away);
        $this->resolveMarket14($fixture, $fulltime_home);
        $this->resolveMarket15($fixture, $fulltime_away);
        $this->resolveMarket17($fixture, $fulltime_home, $fulltime_away);
        $this->resolveMarket26($fixture, $fulltime_home, $fulltime_away);
        // Add more markets as needed
    }

    /**
     * Update betslips that contain a resolved odd
     */
    private function updateBetslipsForOdd($oddId)
    {
        if (!$oddId) {
            return;
        }

        // Find all betslips containing this odd
        $betslips = \App\Models\Betslip::whereHas('odds', function ($query) use ($oddId) {
            $query->where('odd_id', $oddId);
        })->get();

        foreach ($betslips as $betslip) {
            // Check if all odds in the betslip are resolved
            $pendingOdds = $betslip->odds()
                ->wherePivot('status', 'pending')
                ->count();

            if ($pendingOdds === 0) {
                // All odds are resolved, check if betslip is a winner
                $lostOdds = $betslip->odds()
                    ->wherePivot('status', 'lost')
                    ->count();

                if ($lostOdds === 0) {
                    $betslip->status = 'completed';
                    $betslip->is_winner = true;
                    $this->info("Betslip {$betslip->code} is a WINNER!");
                } else {
                    $betslip->status = 'completed';
                    $betslip->is_winner = false;
                    $this->info("Betslip {$betslip->code} is a LOSER.");
                }

                $betslip->save();
            }
        }
    }

}
