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
    ) {}

    /**
     * Given a set of odds that have just been settled, update any matching
     * contest_legs and attempt scoring for the affected contests.
     *
     * Called from MarketSettlementService inside its transaction. The leg
     * updates participate in that transaction; scoring is deferred to after
     * the closure returns so a scoring failure can't roll back the fixture
     * settlement that triggered it.
     *
     * @param  iterable<Odd>  $odds
     */
    public function resolveMany(iterable $odds): void
    {
        $contestIdsToCheck = [];

        foreach ($odds as $odd) {
            if (!in_array($odd->status, ['won', 'lost', 'void'], true)) {
                continue;
            }

            $legs = ContestLeg::where('fixture_id', $odd->fixture_id)
                ->where('market_id', $odd->market_id)
                ->where('status', 'pending')
                ->get();

            foreach ($legs as $leg) {
                $leg->update([
                    'status'           => $odd->status,
                    'result_selection' => $odd->status === 'won' ? $odd->value : null,
                    'resolved_at'      => now(),
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
     * Score the contest if every leg is resolved. Silent no-op otherwise.
     * Failures are logged, not thrown — a scoring error should never break
     * the settlement job that triggered it.
     */
    private function attemptScore(int $contestId): void
    {
        $contest = Contest::find($contestId);
        if (!$contest || $contest->status === 'settled') {
            return;
        }

        $pending = ContestLeg::where('contest_id', $contestId)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return;
        }

        try {
            $this->scoring->score($contest);
        } catch (\Throwable $e) {
            Log::error('Contest scoring failed after leg resolution', [
                'contest_id' => $contestId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}