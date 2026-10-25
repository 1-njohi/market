<?php

namespace App\Services;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\Odd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContestLegResolver
{
    public function __construct(
        protected ContestScoringService $scoring,
    ) {
    }

    /**
     * Given a set of odds that have just been settled, update any matching
     * contest_legs and attempt scoring for the affected contests.
     *
     * Called from MarketSettlementService inside its transaction. The leg
     * updates participate in that transaction; scoring is deferred to after
     * the closure returns so a scoring failure can't roll back the fixture
     * settlement that triggered it.
     *
     * Odds are grouped by (fixture_id, market_id) before legs are updated:
     * a market is resolved once, using the winning odd's value as the leg's
     * result_selections. This makes resolution independent of the order the
     * odds were iterated — a losing odd processed before the winning one no
     * longer poisons the leg.
     *
     * @param  iterable<Odd>  $odds
     */
    public function resolveMany(iterable $odds): void
    {
        // Group only odds that have actually been resolved. Pending odds are
        // ignored: if every odd in a group is pending, no leg update happens
        // for that market.
        $groups = [];
        foreach ($odds as $odd) {
            if (!in_array($odd->status, ['won', 'lost', 'void'], true)) {
                continue;
            }

            $key = $odd->fixture_id . ':' . $odd->market_id;
            $groups[$key][] = $odd;
        }

        if (empty($groups)) {
            return;
        }

        $contestIdsToCheck = [];

        foreach ($groups as $group) {
            [$legStatus, $resultSelection] = $this->resolveGroupOutcome($group);

            $first = $group[0];
            $legs = ContestLeg::where('fixture_id', $first->fixture_id)
                ->where('market_id', $first->market_id)
                ->where('status', 'pending')
                ->get();

            foreach ($legs as $leg) {
                $leg->update([
                    'status' => $legStatus,
                    'result_selections' => $resultSelection,
                    'resolved_at' => now(),
                ]);

                $contestIdsToCheck[$leg->contest_id] = true;
            }
        }

        // Scoring happens after the caller's transaction commits — see the
        // deferred call in MarketSettlementService. We collect the ids here
        // and hand them back via a static pending list.
        if (!empty($contestIdsToCheck)) {
            static::queueContestIds(array_keys($contestIdsToCheck));
        }
    }

    /**
     * Determine a market's outcome from its resolved odds.
     *
     * Priority:
     *   1. Any 'won' odd(s) → leg is 'won', result is the set of winning values.
     *      Most markets have exactly one winner; Double Chance markets have two.
     *   2. Every odd 'void' → leg is 'void', result is [].
     *   3. Otherwise → leg is 'lost', result is [].
     *
     * @param  array<int, Odd>  $group
     * @return array{0: string, 1: array<int, string>}  [status, winning selections]
     */
    private function resolveGroupOutcome(array $group): array
    {
        $winningSelections = [];
        $allVoid = true;

        foreach ($group as $odd) {
            if ($odd->status === 'won') {
                $winningSelections[] = $odd->value;
                $allVoid = false;
                continue;
            }

            if ($odd->status !== 'void') {
                $allVoid = false;
            }
        }

        if (!empty($winningSelections)) {
            return ['won', array_values(array_unique($winningSelections))];
        }

        if ($allVoid) {
            return ['void', []];
        }

        return ['lost', []];
    }

    /**
     * Registry of contest IDs to attempt scoring for, populated during
     * leg resolution and drained after the surrounding transaction commits.
     *
     * @var array<int, true>
     */
    private static array $pendingContestIds = [];

    private static function queueContestIds(array $ids): void
    {
        foreach ($ids as $id) {
            self::$pendingContestIds[$id] = true;
        }
    }

    /**
     * Drain the pending queue and attempt scoring for every contest in it.
     * Called once per fixture settlement, after the transaction commits.
     */
    public function drainPendingScores(): void
    {
        $ids = array_keys(self::$pendingContestIds);
        self::$pendingContestIds = [];

        foreach ($ids as $id) {
            $this->attemptScore($id);
        }
    }

    /**
     * Score the contest.
     *
     * Called after leg resolution, once per fixture settle. Chooses
     * interim or final scoring based on whether any leg is still pending:
     *
     *   - Any pending leg  → interim pass. Updates scores and current
     *                        ranks; leaves the contest and its entries
     *                        open for further resolution.
     *   - No pending legs  → final pass. Writes final ranks, notifies,
     *                        flips the contest to 'settled'.
     *
     * Failures are logged, not thrown — a scoring error should never
     * break the fixture settlement that triggered it.
     */
    private function attemptScore(int $contestId): void
    {
        $contest = Contest::find($contestId);
        if (!$contest || $contest->status === 'settled') {
            return;
        }

        $hasPending = ContestLeg::where('contest_id', $contestId)
            ->where('status', 'pending')
            ->exists();

        try {
            if ($hasPending) {
                $this->scoring->scoreInterim($contest);
            } else {
                $this->scoring->scoreFinal($contest);
            }
        } catch (\Throwable $e) {
            Log::error('Contest scoring failed after leg resolution', [
                'contest_id' => $contestId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}