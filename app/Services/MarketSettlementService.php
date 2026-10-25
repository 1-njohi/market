<?php

namespace App\Services;

use App\Enums\OddOutcome;
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

    /**
     * Markets the settlement engine knows how to resolve. Single source of
     * truth shared with SyncUpcomingOddsCommand — the sync writes odds only
     * for these IDs, so resolveMarket() never sees anything outside them.
     */
    public const SETTLEABLE_MARKET_IDS = [
        1,
        2,
        3,
        5,
        6,
        7,
        8,
        9,
        10,
        12,
        13,
        14,
        15,
        16,
        17,
        20,
        21,
        22,
        24,
        25,
        26,
        29,
        30,
        32,
        34,
        38,
        40,
        41,
        42,
        43,
        44,
        46,
    ];

    public function __construct(
        protected ContestLegResolver $contestLegResolver,
        protected PredictionScoringService $predictionScoring,
    ) {
    }

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
        $homeHT = (int) ($fixture->halftime_home ?? 0);
        $awayHT = (int) ($fixture->halftime_away ?? 0);
        $homeFT = (int) ($fixture->fulltime_home ?? $homeGoals);
        $awayFT = (int) ($fixture->fulltime_away ?? $awayGoals);

        $totalGoals = $homeGoals + $awayGoals;
        $htTotal = $homeHT + $awayHT;
        $stTotal = ($homeFT - $homeHT) + ($awayFT - $awayHT);

        $goalEvents = DB::table('fixture_events')
            ->where('fixture_id', $fixture->id)
            ->where('type', 'Goal')
            ->orderBy('time_elapsed')
            ->orderBy('time_extra')
            ->orderBy('id')
            ->pluck('team_id');

        $firstGoalTeam = $goalEvents->first();
        $lastGoalTeam = $goalEvents->last();

        $isHome = fn($teamId) => $teamId !== null && (int) $teamId === (int) $fixture->home_team_id;
        $isAway = fn($teamId) => $teamId !== null && (int) $teamId === (int) $fixture->away_team_id;

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
            'both_scored' => $homeGoals > 0 && $awayGoals > 0,
            'home_scored_first' => $isHome($firstGoalTeam),
            'away_scored_first' => $isAway($firstGoalTeam),
            'home_scored_last' => $isHome($lastGoalTeam),
            'away_scored_last' => $isAway($lastGoalTeam),
            'home_winner' => $homeGoals > $awayGoals,
            'away_winner' => $awayGoals > $homeGoals,
            'draw' => $homeGoals === $awayGoals,
            'halftime_home_winner' => $homeHT > $awayHT,
            'halftime_away_winner' => $awayHT > $homeHT,
            'halftime_draw' => $homeHT === $awayHT,
            'score' => "{$homeGoals}:{$awayGoals}",
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

            if (!$fixture || $fixture->settled) {
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
            $odds = $fixture->Odds;

            foreach ($odds as $odd) {
                $this->settleOdd($odd, $match);
            }

            if ($odds->isNotEmpty()) {
                $this->contestLegResolver->resolveMany($odds);
                $this->predictionScoring->scoreFixture($fixture->id);
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
        $outcome = $this->resolveMarket($odd->market_id, $odd->value, $match);

        $odd->status = match ($outcome) {
            OddOutcome::Won => 'won',
            OddOutcome::Lost => 'lost',
            OddOutcome::Void => 'void',
        };
        $odd->save();
    }

    /**
     * Resolve a market based on ID, odd value, and match data.
     *
     * The market IDs listed in SETTLEABLE_MARKET_IDS are the only ones the
     * sync command writes odds for. If anything else reaches this method,
     * something upstream has drifted — log and mark as lost rather than
     * silently swallowing it.
     */
    protected function resolveMarket(int $marketId, string $oddValue, array $match): OddOutcome
    {
        if (!in_array($marketId, self::SETTLEABLE_MARKET_IDS, true)) {
            Log::warning('Settlement reached an unhandled market; marking as lost.', [
                'market_id' => $marketId,
                'value' => $oddValue,
            ]);

            return OddOutcome::Lost;
        }

        return match ($marketId) {
            1 => $this->resolveMatchWinner($oddValue, $match),
            2 => $this->resolveHomeAway($oddValue, $match),
            3 => $this->resolveSecondHalfWinner($oddValue, $match),
            5 => $this->resolveOverUnder($oddValue, $match['total_goals']),
            6 => $this->resolveOverUnder($oddValue, $match['ht_total_goals']),
            7 => $this->resolveHTFTDouble($oddValue, $match),
            8 => $this->resolveBothTeamsScore($oddValue, $match),
            9 => $this->resolveHandicapResult($oddValue, $match),
            10 => $this->resolveExactScore($oddValue, $match),
            12 => $this->resolveDoubleChance($oddValue, $match),
            13 => $this->resolveFirstHalfWinner($oddValue, $match),
            14 => $this->resolveTeamToScoreFirst($oddValue, $match),
            15 => $this->resolveTeamToScoreLast($oddValue, $match),
            16 => $this->resolveTotalHome($oddValue, $match['home_goals']),
            17 => $this->resolveTotalAway($oddValue, $match['away_goals']),
            20 => $this->resolveDoubleChanceFirstHalf($oddValue, $match),
            21 => $this->resolveOddEven($oddValue, $match['total_goals']),
            22 => $this->resolveOddEven($oddValue, $match['ht_total_goals']),
            24 => $this->resolveResultsBothTeamsScore($oddValue, $match),
            25 => $this->resolveResultTotalGoals($oddValue, $match),
            26 => $this->resolveOverUnder($oddValue, $match['st_total_goals']),
            29 => $this->resolveWinToNilHome($oddValue, $match),
            30 => $this->resolveWinToNilAway($oddValue, $match),
            32 => $this->resolveWinBothHalves($oddValue, $match),
            34 => $this->resolveBothTeamsScoreFirstHalf($oddValue, $match),
            38 => $this->resolveExactGoalsNumber($oddValue, $match['total_goals']),
            40 => $this->resolveExactGoalsNumber($oddValue, $match['home_goals']),
            41 => $this->resolveExactGoalsNumber($oddValue, $match['away_goals']),
            42 => $this->resolveExactGoalsNumber($oddValue, $match['st_total_goals']),
            43 => $this->resolveTeamScoreGoal($oddValue, $match['home_goals']),
            44 => $this->resolveTeamScoreGoal($oddValue, $match['away_goals']),
            46 => $this->resolveExactGoalsNumber($oddValue, $match['ht_total_goals']),
        };
    }

    // ------------------------------------------------------------------------
    // Market Resolver Implementations
    //
    // Every resolver receives the odd's value plus whatever match data it
    // needs, and returns an OddOutcome for that specific odd. The "one
    // winning string compared at the call site" contract is gone — see
    // compare() below.
    //
    // The bodies here are a faithful translation of the pre-Phase-1
    // behaviour. Markets with known format/logic bugs carry a `Phase N`
    // comment identifying the phase that fixes them.
    // ------------------------------------------------------------------------

    protected function resolveMatchWinner(string $oddValue, array $match): OddOutcome
    {
        $winner = $match['home_winner'] ? 'Home'
            : ($match['away_winner'] ? 'Away' : 'Draw');

        return $this->compare($oddValue, $winner);
    }

    protected function resolveSecondHalfWinner(string $oddValue, array $match): OddOutcome
    {
        $homeSH = $match['home_ft_goals'] - $match['home_ht_goals'];
        $awaySH = $match['away_ft_goals'] - $match['away_ht_goals'];

        $winner = $homeSH > $awaySH ? 'Home'
            : ($awaySH > $homeSH ? 'Away' : 'Draw');

        return $this->compare($oddValue, $winner);
    }

    protected function resolveOverUnder(string $oddValue, int $goals): OddOutcome
    {
        // API format: "Over 2.5", "Under 3.5", occasionally "Over 2" for
        // integer lines. Anchored so trailing garbage can't sneak through.
        if (!preg_match('/^(Over|Under)\s+([\d.]+)$/', $oddValue, $m)) {
            // Malformed input: we cannot judge — void, don't award.
            return OddOutcome::Void;
        }

        $threshold = (float) $m[2];
        $isOver = $m[1] === 'Over';

        // Integer line hit exactly → push (stake refunded).
        if ($threshold == floor($threshold) && $goals === (int) $threshold) {
            return OddOutcome::Void;
        }

        $hit = $isOver ? ($goals > $threshold) : ($goals < $threshold);

        return $hit ? OddOutcome::Won : OddOutcome::Lost;
    }

    protected function resolveHTFTDouble(string $oddValue, array $match): OddOutcome
    {
        $ht = $match['halftime_home_winner'] ? 'Home'
            : ($match['halftime_away_winner'] ? 'Away' : 'Draw');

        $ft = $match['home_winner'] ? 'Home'
            : ($match['away_winner'] ? 'Away' : 'Draw');

        return $this->compare($oddValue, "{$ht}/{$ft}");
    }

    protected function resolveBothTeamsScore(string $oddValue, array $match): OddOutcome
    {
        return $this->compare($oddValue, $match['both_scored'] ? 'Yes' : 'No');
    }

    protected function resolveExactScore(string $oddValue, array $match): OddOutcome
    {
        return $this->compare($oddValue, $match['score']);
    }

    protected function resolveFirstHalfWinner(string $oddValue, array $match): OddOutcome
    {
        $winner = $match['halftime_home_winner'] ? 'Home'
            : ($match['halftime_away_winner'] ? 'Away' : 'Draw');

        return $this->compare($oddValue, $winner);
    }

    protected function resolveTeamToScoreFirst(string $oddValue, array $match): OddOutcome
    {
        // API offers "Home" / "Away" / "No goal" (lowercase g). Some books
        // use "Draw" as a synonym for a scoreless match. Accept both.
        return match (true) {
            $oddValue === 'Home' => $match['home_scored_first'] ? OddOutcome::Won : OddOutcome::Lost,
            $oddValue === 'Away' => $match['away_scored_first'] ? OddOutcome::Won : OddOutcome::Lost,
            in_array($oddValue, ['No goal', 'No Goal', 'Draw'], true)
            => (!$match['home_scored_first'] && !$match['away_scored_first'])
                ? OddOutcome::Won
                : OddOutcome::Lost,
            default => OddOutcome::Void,
        };
    }

    protected function resolveTeamToScoreLast(string $oddValue, array $match): OddOutcome
    {
        return match (true) {
            $oddValue === 'Home' => $match['home_scored_last'] ? OddOutcome::Won : OddOutcome::Lost,
            $oddValue === 'Away' => $match['away_scored_last'] ? OddOutcome::Won : OddOutcome::Lost,
            in_array($oddValue, ['No goal', 'No Goal', 'Draw'], true)
            => (!$match['home_scored_last'] && !$match['away_scored_last'])
                ? OddOutcome::Won
                : OddOutcome::Lost,
            default => OddOutcome::Void,
        };
    }

    protected function resolveTotalHome(string $oddValue, int $homeGoals): OddOutcome
    {
        return $this->resolveOverUnder($oddValue, $homeGoals);
    }

    protected function resolveTotalAway(string $oddValue, int $awayGoals): OddOutcome
    {
        return $this->resolveOverUnder($oddValue, $awayGoals);
    }

    protected function resolveHomeAway(string $oddValue, array $match): OddOutcome
    {
        // Two-way market: the API offers "Home" and "Away" only.
        // A draw refunds both sides — the equivalent of Draw No Bet.
        if ($match['draw']) {
            return OddOutcome::Void;
        }

        if ($oddValue !== 'Home' && $oddValue !== 'Away') {
            return OddOutcome::Void;
        }

        $winner = $match['home_winner'] ? 'Home' : 'Away';

        return $this->compare($oddValue, $winner);
    }

    protected function resolveHandicapResult(string $oddValue, array $match): OddOutcome
    {
        // API format: "Home -1", "Draw -1", "Away -1", "Home +2", etc.
        // The handicap applies to the home side uniformly; the three
        // selections describe the adjusted result.
        if (!preg_match('/^(Home|Draw|Away)\s+([+-]?\d+)$/', $oddValue, $m)) {
            return OddOutcome::Void;
        }

        $selection = $m[1];
        $handicap = (float) $m[2];
        $adjustedHome = $match['home_goals'] + $handicap;
        $adjustedAway = $match['away_goals'];

        $winner = $adjustedHome > $adjustedAway ? 'Home'
            : ($adjustedHome < $adjustedAway ? 'Away' : 'Draw');

        return $this->compare($selection, $winner);
    }

    protected function resolveDoubleChance(string $oddValue, array $match): OddOutcome
    {
        // Multi-winner: a Home win makes BOTH "Home/Draw" and "Home/Away" win.
        $valid = ['Home/Draw', 'Home/Away', 'Draw/Away'];
        if (!in_array($oddValue, $valid, true)) {
            return OddOutcome::Void;
        }

        $winners = $this->doubleChanceWinners($match);

        return in_array($oddValue, $winners, true)
            ? OddOutcome::Won
            : OddOutcome::Lost;
    }

    protected function resolveDoubleChanceFirstHalf(string $oddValue, array $match): OddOutcome
    {
        $valid = ['Home/Draw', 'Home/Away', 'Draw/Away'];
        if (!in_array($oddValue, $valid, true)) {
            return OddOutcome::Void;
        }

        $winners = $this->doubleChanceFirstHalfWinners($match);

        return in_array($oddValue, $winners, true)
            ? OddOutcome::Won
            : OddOutcome::Lost;
    }

    protected function resolveOddEven(string $oddValue, int $goals): OddOutcome
    {
        return $this->compare($oddValue, ($goals % 2 === 0) ? 'Even' : 'Odd');
    }

    protected function resolveResultsBothTeamsScore(string $oddValue, array $match): OddOutcome
    {
        // API format: "Home/Yes", "Draw/No", "Away/Yes", etc.
        // The separator is "/", not " & ".
        $valid = ['Home/Yes', 'Draw/Yes', 'Away/Yes', 'Home/No', 'Draw/No', 'Away/No'];
        if (!in_array($oddValue, $valid, true)) {
            return OddOutcome::Void;
        }

        $resultStr = $match['home_winner'] ? 'Home'
            : ($match['away_winner'] ? 'Away' : 'Draw');
        $btsStr = $match['both_scored'] ? 'Yes' : 'No';

        return $this->compare($oddValue, "{$resultStr}/{$btsStr}");
    }

    protected function resolveResultTotalGoals(string $oddValue, array $match): OddOutcome
    {
        // API format: "Home/Over 2.5", "Draw/Under 3.5", etc.
        // The threshold varies (1.5–5.5 across books); parse it from the
        // odd's own value rather than assuming a fixed line.
        if (!preg_match('/^(Home|Draw|Away)\/(Over|Under)\s+([\d.]+)$/', $oddValue, $m)) {
            return OddOutcome::Void;
        }

        [, $resultSel, $ouSel, $threshold] = $m;
        $threshold = (float) $threshold;

        $resultStr = $match['home_winner'] ? 'Home'
            : ($match['away_winner'] ? 'Away' : 'Draw');

        // If the result component is wrong, the whole odd loses — a push
        // on the O/U leg can't rescue a losing result leg.
        if ($resultSel !== $resultStr) {
            return OddOutcome::Lost;
        }

        // Result matches. Integer O/U line hit exactly → push.
        // Standard bookmaker rule reduces the odd to its result leg;
        // since we can't pay at reduced odds, void is the conservative
        // approximation. Half-lines (2.5, 3.5, …) can never push.
        if ($threshold == floor($threshold) && $match['total_goals'] === (int) $threshold) {
            return OddOutcome::Void;
        }

        $ouMatches = $ouSel === 'Over'
            ? $match['total_goals'] > $threshold
            : $match['total_goals'] < $threshold;

        return $ouMatches ? OddOutcome::Won : OddOutcome::Lost;
    }

    protected function resolveWinToNilHome(string $oddValue, array $match): OddOutcome
    {
        $hit = $match['home_goals'] > 0 && $match['away_goals'] === 0;
        return $this->compare($oddValue, $hit ? 'Yes' : 'No');
    }

    protected function resolveWinToNilAway(string $oddValue, array $match): OddOutcome
    {
        $hit = $match['away_goals'] > 0 && $match['home_goals'] === 0;
        return $this->compare($oddValue, $hit ? 'Yes' : 'No');
    }

    protected function resolveWinBothHalves(string $oddValue, array $match): OddOutcome
    {
        // Standard bookmaker rule: "Win Both Halves" means the team was
        // leading at half-time AND leading at full-time. Both Home and
        // Away can lose simultaneously — a draw, or a lead that changes
        // hands, means neither selection was correct.
        $htHomeWin = $match['halftime_home_winner'];
        $htAwayWin = $match['halftime_away_winner'];
        $ftHomeWin = $match['home_winner'];
        $ftAwayWin = $match['away_winner'];

        if ($htHomeWin && $ftHomeWin) {
            return $this->compare($oddValue, 'Home');
        }

        if ($htAwayWin && $ftAwayWin) {
            return $this->compare($oddValue, 'Away');
        }

        // No team led at both checkpoints — both selections lose.
        return OddOutcome::Lost;
    }

    protected function resolveBothTeamsScoreFirstHalf(string $oddValue, array $match): OddOutcome
    {
        $hit = $match['home_ht_goals'] > 0 && $match['away_ht_goals'] > 0;
        return $this->compare($oddValue, $hit ? 'Yes' : 'No');
    }

    protected function resolveExactGoalsNumber(string $oddValue, int $goals): OddOutcome
    {
        // Three value formats appear across books:
        //   - exact integer:  "0", "1", "2", ...
        //   - "more N":       "more 6", "more 4", ...  (>= N)
        //   - legacy "N+":    "6+"                      (>= N)
        // Malformed values void rather than award.
        if (ctype_digit($oddValue)) {
            return ((int) $oddValue === $goals) ? OddOutcome::Won : OddOutcome::Lost;
        }

        if (preg_match('/^more\s+(\d+)$/', $oddValue, $m)) {
            return ($goals >= (int) $m[1]) ? OddOutcome::Won : OddOutcome::Lost;
        }

        if (preg_match('/^(\d+)\+$/', $oddValue, $m)) {
            return ($goals >= (int) $m[1]) ? OddOutcome::Won : OddOutcome::Lost;
        }

        return OddOutcome::Void;
    }

    protected function resolveTeamScoreGoal(string $oddValue, int $goals): OddOutcome
    {
        return $this->compare($oddValue, $goals > 0 ? 'Yes' : 'No');
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

    /**
     * The two winning selections for the full-time Double Chance market.
     * Exactly two of the three always win — a Home win means both
     * "Home/Draw" and "Home/Away" are correct.
     *
     * @return array{0: string, 1: string}
     */
    protected function doubleChanceWinners(array $match): array
    {
        if ($match['home_winner']) {
            return ['Home/Draw', 'Home/Away'];
        }

        if ($match['away_winner']) {
            return ['Home/Away', 'Draw/Away'];
        }

        return ['Home/Draw', 'Draw/Away'];
    }

    /**
     * The two winning selections for the half-time Double Chance market.
     *
     * @return array{0: string, 1: string}
     */
    protected function doubleChanceFirstHalfWinners(array $match): array
    {
        if ($match['halftime_home_winner']) {
            return ['Home/Draw', 'Home/Away'];
        }

        if ($match['halftime_away_winner']) {
            return ['Home/Away', 'Draw/Away'];
        }

        return ['Home/Draw', 'Draw/Away'];
    }

    /**
     * Compare an odd's stored value against the winning selection the
     * resolver computed for its market. Exact string match — no trimming,
     * no case folding; those are resolver concerns, not plumbing concerns.
     */
    protected function compare(string $oddValue, string $winningSelection): OddOutcome
    {
        return $oddValue === $winningSelection
            ? OddOutcome::Won
            : OddOutcome::Lost;
    }
}
