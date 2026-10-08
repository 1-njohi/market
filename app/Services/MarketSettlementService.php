<?php

namespace App\Services;

use App\Jobs\BetslipSettlementJob;
use App\Models\Fixture;
use App\Models\Odd;
use App\Models\Betslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketSettlementService
{
    public const FINISHED_STATUS = 'FT';
    public const VOID_STATUSES = ['CANC', 'ABD', 'AWD', 'WO', 'PST'];

    public function __construct(
        protected ContestLegResolver $contestLegResolver,
    ) {}

    /**
     * Build the match-data array consumed by resolveMarket() from local DB
     * state only. No API call.
     *
     * Mirrors the shape previously built from the API-Sports fixture payload.
     */
    public function buildMatchDataFromDb(Fixture $fixture): array
    {
        $homeGoals = (int) ($fixture->goals_home ?? 0);
        $awayGoals = (int) ($fixture->goals_away ?? 0);
        $homeHT    = (int) ($fixture->halftime_home ?? 0);
        $awayHT    = (int) ($fixture->halftime_away ?? 0);
        $homeFT    = (int) ($fixture->fulltime_home ?? $homeGoals);
        $awayFT    = (int) ($fixture->fulltime_away ?? $awayGoals);

        $totalGoals = $homeGoals + $awayGoals;
        $htTotal    = $homeHT + $awayHT;
        $stTotal    = ($homeFT - $homeHT) + ($awayFT - $awayHT);

        $goalEvents = DB::table('fixture_events')
            ->where('fixture_id', $fixture->id)
            ->where('type', 'Goal')
            ->orderBy('time_elapsed')
            ->orderBy('time_extra')
            ->orderBy('id')
            ->pluck('team_id');

        $firstGoalTeam = $goalEvents->first();
        $lastGoalTeam  = $goalEvents->last();

        $isHome = fn ($teamId) => $teamId !== null && (int) $teamId === (int) $fixture->home_team_id;
        $isAway = fn ($teamId) => $teamId !== null && (int) $teamId === (int) $fixture->away_team_id;

        return [
            'home_goals'           => $homeGoals,
            'away_goals'           => $awayGoals,
            'home_ht_goals'        => $homeHT,
            'away_ht_goals'        => $awayHT,
            'home_ft_goals'        => $homeFT,
            'away_ft_goals'        => $awayFT,
            'total_goals'          => $totalGoals,
            'ht_total_goals'       => $htTotal,
            'st_total_goals'       => $stTotal,
            'both_scored'          => $homeGoals > 0 && $awayGoals > 0,
            'home_scored_first'    => $isHome($firstGoalTeam),
            'away_scored_first'    => $isAway($firstGoalTeam),
            'home_scored_last'     => $isHome($lastGoalTeam),
            'away_scored_last'     => $isAway($lastGoalTeam),
            'home_winner'          => $homeGoals > $awayGoals,
            'away_winner'          => $awayGoals > $homeGoals,
            'draw'                 => $homeGoals === $awayGoals,
            'halftime_home_winner' => $homeHT > $awayHT,
            'halftime_away_winner' => $awayHT > $homeHT,
            'halftime_draw'        => $homeHT === $awayHT,
            'score'                => "{$homeGoals}:{$awayGoals}",
        ];
    }

    /**
     * Settle a fixture from local DB state only. No API call.
     *
     * Idempotent: safe to call multiple times. Locks the fixture row and
     * re-checks the `settled` flag inside the transaction to prevent
     * double-settlement from overlapping scheduler runs.
     */
    public function settleFixture(Fixture $fixture): void
    {
        DB::transaction(function () use ($fixture) {
            $fixture = Fixture::whereKey($fixture->id)->lockForUpdate()->first();

            if (! $fixture || $fixture->settled) {
                return;
            }

            if (in_array($fixture->status_short, self::VOID_STATUSES, true)) {
                $this->voidFixture($fixture);
                $fixture->update(['settled' => true]);
                return;
            }

            if ($fixture->status_short !== self::FINISHED_STATUS) {
                return;
            }

            $match = $this->buildMatchDataFromDb($fixture);
            $odds  = $fixture->Odds;

            foreach ($odds as $odd) {
                $this->settleOdd($odd, $match);
            }

            if ($odds->isNotEmpty()) {
                $this->contestLegResolver->resolveMany($odds);
                $this->updateBetslips($fixture);
            }

            $fixture->update(['settled' => true]);
        });

        // Deferred scoring happens after commit — ContestLegResolver's contract.
        $this->contestLegResolver->drainPendingScores();
    }

    /**
     * Void every odd on a fixture whose status is in VOID_STATUSES.
     * Downstream cascades (betslips, contests) treat 'void' as refund.
     */
    protected function voidFixture(Fixture $fixture): void
    {
        $odds = $fixture->Odds;

        foreach ($odds as $odd) {
            if ($odd->status === 'pending') {
                $odd->update(['status' => 'void']);
            }
        }

        // Contest legs referencing these odds need to know about the void.
        if ($odds->isNotEmpty()) {
            $this->contestLegResolver->resolveMany($odds);
            $this->updateBetslips($fixture);
        }
    }
    /**
     * Settle a single odd.
     */
    protected function settleOdd(Odd $odd, array $match): void
    {
        // \Log::info("before: Market ID " . $odd->market_id . " Value: " . $odd->value . " Status:" . $odd->status);
        $winningValue = $this->resolveMarket($odd->market_id, $odd->value, $match);
        $isWinner = $winningValue !== null && (string) $odd->value === (string) $winningValue;

        $odd->status = $isWinner ? 'won' : 'lost';
        $odd->save();
        // \Log::info("after: Status " . $odd->status);
    }

    /**
     * Resolve a market based on ID, odd value, and match data.
     */
    protected function resolveMarket(int $marketId, string $oddValue, array $match): ?string
    {
        return match ($marketId) {
            1 => $this->resolveMatchWinner($match),
            2 => $this->resolveHomeAway($match),
            3 => $this->resolveSecondHalfWinner($match),
            5 => $this->resolveOverUnder($oddValue, $match['total_goals']),
            6 => $this->resolveOverUnder($oddValue, $match['ht_total_goals']),
            7 => $this->resolveHTFTDouble($match),
            8 => $this->resolveBothTeamsScore($match),
            9 => $this->resolveHandicapResult($oddValue, $match),
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
        if ($match['home_winner'])
            return 'Home';
        if ($match['away_winner'])
            return 'Away';
        return 'Draw';
    }

    protected function resolveHomeAway(array $match): string
    {
        if ($match['home_winner'] || $match['draw'])
            return 'Home/Draw';
        if ($match['away_winner'])
            return 'Draw/Away';
        return 'Home/Away';
    }

    protected function resolveSecondHalfWinner(array $match): string
    {
        $homeSH = $match['home_ft_goals'] - $match['home_ht_goals'];
        $awaySH = $match['away_ft_goals'] - $match['away_ht_goals'];
        if ($homeSH > $awaySH)
            return 'Home';
        if ($awaySH > $homeSH)
            return 'Away';
        return 'Draw';
    }

    protected function resolveOverUnder(string $oddValue, int $goals): ?string
    {
        // e.g., "Over 2.5" -> threshold = 2.5, isOver = true
        preg_match('/(Over|Under)\s+([\d.]+)/', $oddValue, $matches);
        if (count($matches) < 3)
            return $oddValue; // fallback

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
        if (count($matches) < 3)
            return null;

        $team = $matches[1];
        $handicap = (float) $matches[2];

        $homeAdjusted = $match['home_goals'] + ($team === 'Home' ? $handicap : 0);
        $awayAdjusted = $match['away_goals'] + ($team === 'Away' ? $handicap : 0);

        if ($homeAdjusted > $awayAdjusted)
            return 'Home';
        if ($awayAdjusted > $homeAdjusted)
            return 'Away';
        return 'Draw';
    }

    protected function resolveExactScore(array $match): string
    {
        return $match['score'];
    }

    protected function resolveDoubleChance(array $match): string
    {
        if ($match['home_winner'] || $match['draw'])
            return 'Home/Draw';
        if ($match['away_winner'])
            return 'Draw/Away';
        return 'Home/Away';
    }

    protected function resolveFirstHalfWinner(array $match): string
    {
        if ($match['halftime_home_winner'])
            return 'Home';
        if ($match['halftime_away_winner'])
            return 'Away';
        return 'Draw';
    }

    protected function resolveTeamToScoreFirst(array $match): string
    {
        if ($match['home_scored_first'])
            return 'Home';
        if ($match['away_scored_first'])
            return 'Away';
        return 'No Goal';
    }

    protected function resolveTeamToScoreLast(array $match): string
    {
        if ($match['home_scored_last'])
            return 'Home';
        if ($match['away_scored_last'])
            return 'Away';
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
        if ($match['halftime_home_winner'] || $match['halftime_draw'])
            return 'Home/Draw';
        if ($match['halftime_away_winner'])
            return 'Draw/Away';
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
        if ($htHomeWin && $ftHomeWin)
            return 'Home';
        if (!$htHomeWin && !$ftHomeWin)
            return 'Away';
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

    protected function updateBetslips(Fixture $fixture): void
    {
        // Find every betslip that has at least one odd belonging to this fixture
        $betslipIds = DB::table('betslip_odd')
            ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
            ->where('odds.fixture_id', $fixture->id)
            ->pluck('betslip_odd.betslip_id')
            ->unique();

        if ($betslipIds->isEmpty()) {
            Log::info("Fixture {$fixture->id} settled: no betslips affected.");
            return;
        }

        foreach ($betslipIds as $betslipId) {
            BetslipSettlementJob::dispatch($betslipId);
        }

        Log::info("Fixture {$fixture->id} settled: dispatched " . $betslipIds->count() . " betslip settlement jobs.");
    }
}
