<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Odd;
use App\Models\Betslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketSettlementService
{
    /**
     * Settle all pending odds for a fixture.
     */
    public function settleFixture(Fixture $fixture, array $apiData): void
    {
        if (($apiData['fixture']['status']['short'] ?? '') !== 'FT') {
            Log::info("Fixture {$fixture->id} not finished.");
            return;
        }

        $match = $this->extractMatchData($apiData);
        $odds = $fixture->Odds;

        \Log::info($match);

        if ($odds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($odds, $match, $fixture) {
            foreach ($odds as $odd) {
                $this->settleOdd($odd, $match);
            }
            // $this->updateBetslips($fixture);
        });
    }

    /**
     * Settle a single odd.
     */
    protected function settleOdd(Odd $odd, array $match): void
    {
        \Log::info("before: Market ID ". $odd -> market_id. " Value: " . $odd -> value . " Status:" . $odd -> status);
        $winningValue = $this->resolveMarket($odd->market_id, $odd->value, $match);
        $isWinner = $winningValue !== null && (string) $odd->value === (string) $winningValue;

        $odd->status = $isWinner ? 'won' : 'lost';
        $odd->save();
        \Log::info("after: Status " . $odd -> status);
    }

    /**
     * Resolve a market based on ID, odd value, and match data.
     */
    protected function resolveMarket(int $marketId, string $oddValue, array $match): ?string
    {
        return match ($marketId) {
            1  => $this->resolveMatchWinner($match),
            2  => $this->resolveHomeAway($match),
            3  => $this->resolveSecondHalfWinner($match),
            5  => $this->resolveOverUnder($oddValue, $match['total_goals']),
            6  => $this->resolveOverUnder($oddValue, $match['ht_total_goals']),
            7  => $this->resolveHTFTDouble($match),
            8  => $this->resolveBothTeamsScore($match),
            9  => $this->resolveHandicapResult($oddValue, $match),
            10 => $this->resolveExactScore($match),
            12 => $this->resolveDoubleChance($match),
            13 => $this->resolveFirstHalfWinner($match),
            14 => $this->resolveTeamToScoreFirst($match),
            15 => $this->resolveTeamToScoreLast($match),
            16 => $this->resolveTotalHome($oddValue, $match['home_goals']),
            17 => $this->resolveTotalAway($oddValue, $match['away_goals']),
            20 => $this->resolveDoubleChanceFirstHalf($match),
            21 => $this->resolveOddEven($match['total_goals']),
            22 => $this->resolveOddEven($match['ht_total_goals']),
            24 => $this->resolveResultsBothTeamsScore($match),
            25 => $this->resolveResultTotalGoals($match),
            26 => $this->resolveOverUnder($oddValue, $match['st_total_goals']),
            29 => $this->resolveWinToNilHome($match),
            30 => $this->resolveWinToNilAway($match),
            32 => $this->resolveWinBothHalves($match),
            34 => $this->resolveBothTeamsScoreFirstHalf($match),
            38 => $this->resolveExactGoalsNumber($oddValue, $match['total_goals']),
            40 => $this->resolveExactGoalsNumber($oddValue, $match['home_goals']),
            41 => $this->resolveExactGoalsNumber($oddValue, $match['away_goals']),
            42 => $this->resolveExactGoalsNumber($oddValue, $match['st_total_goals']),
            43 => $this->resolveTeamScoreGoal($match['home_goals']),
            44 => $this->resolveTeamScoreGoal($match['away_goals']),
            46 => $this->resolveExactGoalsNumber($oddValue, $match['ht_total_goals']),
            default => null,
        };
    }

    // ------------------------------------------------------------------------
    // Market Resolver Implementations
    // ------------------------------------------------------------------------

    protected function resolveMatchWinner(array $match): string
    {
        if ($match['home_winner']) return 'Home';
        if ($match['away_winner']) return 'Away';
        return 'Draw';
    }

    protected function resolveHomeAway(array $match): string
    {
        if ($match['home_winner'] || $match['draw']) return 'Home/Draw';
        if ($match['away_winner']) return 'Draw/Away';
        return 'Home/Away';
    }

    protected function resolveSecondHalfWinner(array $match): string
    {
        $homeSH = $match['home_ft_goals'] - $match['home_ht_goals'];
        $awaySH = $match['away_ft_goals'] - $match['away_ht_goals'];
        if ($homeSH > $awaySH) return 'Home';
        if ($awaySH > $homeSH) return 'Away';
        return 'Draw';
    }

    protected function resolveOverUnder(string $oddValue, int $goals): ?string
    {
        // e.g., "Over 2.5" -> threshold = 2.5, isOver = true
        preg_match('/(Over|Under)\s+([\d.]+)/', $oddValue, $matches);
        if (count($matches) < 3) return $oddValue; // fallback

        $threshold = (float) $matches[2];
        $isOver = $matches[1] === 'Over';

        $won = $isOver ? ($goals > $threshold) : ($goals < $threshold);
        return $won ? $oddValue : null;
    }

    protected function resolveHTFTDouble(array $match): string
    {
        $ht = $match['halftime_home_winner'] ? 'Home' : ($match['halftime_away_winner'] ? 'Away' : 'Draw');
        $ft = $match['home_winner'] ? 'Home' : ($match['away_winner'] ? 'Away' : 'Draw');
        return "{$ht}/{$ft}";
    }

    protected function resolveBothTeamsScore(array $match): string
    {
        return $match['both_scored'] ? 'Yes' : 'No';
    }

    protected function resolveHandicapResult(string $oddValue, array $match): ?string
    {
        // Assume oddValue format: "Home -1.5", "Away +1.5", etc.
        // We need to extract handicap and determine winner.
        preg_match('/(Home|Away)\s+([+-]?[\d.]+)/', $oddValue, $matches);
        if (count($matches) < 3) return null;

        $team = $matches[1];
        $handicap = (float) $matches[2];

        $homeAdjusted = $match['home_goals'] + ($team === 'Home' ? $handicap : 0);
        $awayAdjusted = $match['away_goals'] + ($team === 'Away' ? $handicap : 0);

        if ($homeAdjusted > $awayAdjusted) return 'Home';
        if ($awayAdjusted > $homeAdjusted) return 'Away';
        return 'Draw';
    }

    protected function resolveExactScore(array $match): string
    {
        return $match['score'];
    }

    protected function resolveDoubleChance(array $match): string
    {
        if ($match['home_winner'] || $match['draw']) return 'Home/Draw';
        if ($match['away_winner']) return 'Draw/Away';
        return 'Home/Away';
    }

    protected function resolveFirstHalfWinner(array $match): string
    {
        if ($match['halftime_home_winner']) return 'Home';
        if ($match['halftime_away_winner']) return 'Away';
        return 'Draw';
    }

    protected function resolveTeamToScoreFirst(array $match): string
    {
        if ($match['home_scored_first']) return 'Home';
        if ($match['away_scored_first']) return 'Away';
        return 'No Goal';
    }

    protected function resolveTeamToScoreLast(array $match): string
    {
        if ($match['home_scored_last']) return 'Home';
        if ($match['away_scored_last']) return 'Away';
        return 'No Goal';
    }

    protected function resolveTotalHome(string $oddValue, int $homeGoals): ?string
    {
        return $this->resolveOverUnder($oddValue, $homeGoals);
    }

    protected function resolveTotalAway(string $oddValue, int $awayGoals): ?string
    {
        return $this->resolveOverUnder($oddValue, $awayGoals);
    }

    protected function resolveDoubleChanceFirstHalf(array $match): string
    {
        if ($match['halftime_home_winner'] || $match['halftime_draw']) return 'Home/Draw';
        if ($match['halftime_away_winner']) return 'Draw/Away';
        return 'Home/Away';
    }

    protected function resolveOddEven(int $goals): string
    {
        return ($goals % 2 === 0) ? 'Even' : 'Odd';
    }

    protected function resolveResultsBothTeamsScore(array $match): string
    {
        $result = $this->resolveMatchWinner($match);
        $bts = $this->resolveBothTeamsScore($match);
        return "{$result} & {$bts}";
    }

    protected function resolveResultTotalGoals(array $match): string
    {
        $result = $this->resolveMatchWinner($match);
        $overUnder = $match['total_goals'] > 2.5 ? 'Over' : 'Under';
        return "{$result} & {$overUnder}";
    }

    protected function resolveWinToNilHome(array $match): string
    {
        return ($match['home_goals'] > 0 && $match['away_goals'] === 0) ? 'Yes' : 'No';
    }

    protected function resolveWinToNilAway(array $match): string
    {
        return ($match['away_goals'] > 0 && $match['home_goals'] === 0) ? 'Yes' : 'No';
    }

    protected function resolveWinBothHalves(array $match): string
    {
        $htHomeWin = $match['home_ht_goals'] > $match['away_ht_goals'];
        $ftHomeWin = $match['home_goals'] > $match['away_goals'];
        if ($htHomeWin && $ftHomeWin) return 'Home';
        if (!$htHomeWin && !$ftHomeWin) return 'Away';
        return null; // draw or split
    }

    protected function resolveBothTeamsScoreFirstHalf(array $match): string
    {
        return ($match['home_ht_goals'] > 0 && $match['away_ht_goals'] > 0) ? 'Yes' : 'No';
    }

    protected function resolveExactGoalsNumber(string $oddValue, int $goals): ?string
    {
        // e.g., "0", "1", "2", "3", "4", "5", "6+"
        $expected = $oddValue;
        if ($expected === '6+') {
            return ($goals >= 6) ? $oddValue : null;
        }
        return ((int) $expected === $goals) ? $oddValue : null;
    }

    protected function resolveTeamScoreGoal(int $goals): string
    {
        return $goals > 0 ? 'Yes' : 'No';
    }

    // ------------------------------------------------------------------------
    // Helper Methods
    // ------------------------------------------------------------------------

    protected function extractMatchData(array $apiData): array
    {
        $fixture = $apiData['fixture'];
        $teams = $apiData['teams'];
        $goals = $apiData['goals'];
        $score = $apiData['score'];
        $events = $apiData['events'] ?? [];

        $homeGoals = (int) $goals['home'];
        $awayGoals = (int) $goals['away'];
        $homeHT = (int) $score['halftime']['home'];
        $awayHT = (int) $score['halftime']['away'];
        $homeFT = (int) $score['fulltime']['home'];
        $awayFT = (int) $score['fulltime']['away'];

        $totalGoals = $homeGoals + $awayGoals;
        $htTotal = $homeHT + $awayHT;
        $stTotal = ($homeFT - $homeHT) + ($awayFT - $awayHT);

        // Determine first/last goal
        $firstGoalTeam = null;
        $lastGoalTeam = null;
        foreach ($events as $event) {
            if ($event['type'] === 'Goal') {
                $teamId = $event['team']['id'];
                $isHome = $teamId == $teams['home']['id'];
                $team = $isHome ? 'home' : 'away';
                if ($firstGoalTeam === null) {
                    $firstGoalTeam = $team;
                }
                $lastGoalTeam = $team;
            }
        }

        $bothScored = ($homeGoals > 0 && $awayGoals > 0);

        return [
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'home_ht_goals' => $homeHT,
            'away_ht_goals' => $awayHT,
            'home_ft_goals' => $homeFT,
            'away_ft_goals' => $awayFT,
            'total_goals' => $totalGoals,
            'ht_total_goals' => $htTotal,
            'st_total_goals' => $stTotal,
            'both_scored' => $bothScored,
            'home_scored_first' => $firstGoalTeam === 'home',
            'away_scored_first' => $firstGoalTeam === 'away',
            'home_scored_last' => $lastGoalTeam === 'home',
            'away_scored_last' => $lastGoalTeam === 'away',
            'home_winner' => $homeGoals > $awayGoals,
            'away_winner' => $awayGoals > $homeGoals,
            'draw' => $homeGoals === $awayGoals,
            'halftime_home_winner' => $homeHT > $awayHT,
            'halftime_away_winner' => $awayHT > $homeHT,
            'halftime_draw' => $homeHT === $awayHT,
            'score' => "{$homeGoals}:{$awayGoals}",
        ];
    }

    protected function updateBetslips(Fixture $fixture): void
    {
        // Find all betslips that have odds for this fixture and update their status
        $betslipIds = $fixture->odds()->pluck('betslip_id')->unique();

        foreach ($betslipIds as $betslipId) {
            $betslip = Betslip::find($betslipId);
            if (!$betslip) continue;

            $pendingOdds = $betslip->odds()->wherePivot('status', 'pending')->count();
            if ($pendingOdds > 0) continue; // not all resolved

            $lostOdds = $betslip->odds()->wherePivot('status', 'lost')->count();
            $isWinner = $lostOdds === 0;

            $betslip->status = 'settled';
            $betslip->is_winner = $isWinner;
            $betslip->save();

            // Trigger payout/refund logic here (to be implemented)
            // $this->settleBetslipTransactions($betslip);
        }
    }
}
