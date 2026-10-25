<?php

namespace App\Services;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use Illuminate\Support\Facades\DB;
use App\Notifications\ContestSettledNotification;
use RuntimeException;

class ContestScoringService
{
    /**
     * Backwards-compatible alias for scoreFinal(). Existing callers —
     * principally ContestLegResolver::attemptScore — reach scoring
     * through this entry point and expect final semantics.
     */
    public function score(Contest $contest): void
    {
        $this->scoreFinal($contest);
    }

    /**
     * Interim scoring: called when at least one leg has resolved but the
     * contest still has legs to play. Writes score_correct, score_units,
     * rank_current (rolling the old value into rank_previous), and leaves
     * pending picks untouched. Does not notify, does not flip the
     * contest's status, does not set settled_at on entries.
     */
    public function scoreInterim(Contest $contest): void
    {
        if ($contest->status === 'settled') {
            return;
        }

        $this->runScoring($contest, isFinal: false);
    }

    /**
     * Final scoring: called when every leg has resolved. Same writes as
     * interim, plus per-entry notifications, settled_at, and the
     * transition of the contest itself to 'settled'.
     *
     * @throws RuntimeException  if any leg is still pending.
     */
    public function scoreFinal(Contest $contest): void
    {
        if ($contest->status === 'settled') {
            return;
        }

        $this->assertAllLegsResolved($contest);

        $this->runScoring($contest, isFinal: true);
    }

    /**
     * @throws RuntimeException
     */
    private function runScoring(Contest $contest, bool $isFinal): void
    {
        DB::transaction(function () use ($contest, $isFinal) {
            $legs = ContestLeg::where('contest_id', $contest->id)
                ->get()
                ->keyBy('id');

            $entries = ContestEntry::where('contest_id', $contest->id)
                ->where('status', 'accepted')
                ->with('picks')
                ->get();

            foreach ($entries as $entry) {
                [$correct, $units] = $this->scoreEntry($entry, $legs);

                $entry->update([
                    'score_correct' => $correct,
                    'score_units' => $units,
                    'settled_at' => $isFinal ? now() : $entry->settled_at,
                ]);
            }

            $ranked = ContestEntry::where('contest_id', $contest->id)
                ->where('status', 'accepted')
                ->orderByDesc('score_correct')
                ->orderByDesc('score_units')
                ->orderBy('joined_at')
                ->with('user')
                ->get();

            $totalParticipants = $ranked->count();

            $rank = 1;
            foreach ($ranked as $entry) {
                $previousRank = $entry->rank_current;

                $entry->update([
                    'rank_previous' => $previousRank,
                    'rank_current' => $rank,
                ]);

                if ($isFinal) {
                    if ($contest->is_matchday) {
                        $entry->user->notify(new \App\Notifications\Matchday\MatchdaySettledNotification(
                            contest: $contest,
                            correct: (int) $entry->score_correct,
                            total: $legs->count(),
                            matchdayNumber: (int) $contest->matchday_number,
                        ));
                    } else {
                        $entry->user->notify(new ContestSettledNotification(
                            contest: $contest,
                            rank: $rank,
                            correct: (int) $entry->score_correct,
                            units: (float) $entry->score_units,
                            totalParticipants: $totalParticipants,
                        ));
                    }
                }

                $rank++;
            }
            if ($isFinal) {
                $contest->update([
                    'status' => 'settled',
                    'settled_at' => now(),
                ]);

                if ($contest->is_matchday) {
                    $this->dispatchMatchdayTopThree($contest);
                }
            }

        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ContestLeg>  $legs
     * @return array{0: int, 1: string}  [correct count, units as a 2dp string]
     */
    private function scoreEntry(ContestEntry $entry, $legs): array
    {
        $correct = 0;
        $units = 1.0;  // multiplicative identity — one correct pick leaves this at the pick's odds

        foreach ($entry->picks as $pick) {
            $leg = $legs->get($pick->contest_leg_id);
            if (!$leg) {
                continue;
            }

            // Pending leg: leave the pick alone, contribute nothing.
            if ($leg->status === 'pending') {
                continue;
            }

            // Void leg: neutral — mark the pick, contribute nothing.
            if ($leg->status === 'void') {
                $pick->update(['status' => 'void', 'points' => 0]);
                continue;
            }

            $winningSelections = $leg->result_selections ?? [];
            if (!is_array($winningSelections)) {
                $winningSelections = [$winningSelections];
            }

            $isCorrect = in_array($pick->selection, $winningSelections, true);

            if ($isCorrect) {
                $correct++;
                $odds = (float) $pick->odds_at_pick;
                $units *= $odds;
                $pick->update(['status' => 'correct', 'points' => $odds]);
            } else {
                $pick->update(['status' => 'incorrect', 'points' => 0]);
            }
        }

        // No correct picks ⇒ 1.0 must not be reported. Zero is the
        // conventional value for "you won nothing".
        if ($correct === 0) {
            $units = 0.0;
        }

        return [$correct, number_format($units, 2, '.', '')];
    }
    private function assertAllLegsResolved(Contest $contest): void
    {
        $pending = ContestLeg::where('contest_id', $contest->id)
            ->where('status', 'pending')
            ->count();

        if ($pending > 0) {
            throw new RuntimeException(
                "Contest {$contest->uuid} has {$pending} unresolved leg(s); cannot score yet."
            );
        }
    }

    /**
     * Dispatch the per-sub-league top-three recap. Runs after the contest
     * flips to 'settled' so the leaderboard query sees this contest's
     * results. One notification per (user, sub-league) pair.
     */
    private function dispatchMatchdayTopThree(Contest $contest): void
    {
        $subLeagues = \App\Models\SubLeague::where('season', $contest->season)->get();

        $leaderboard = app(\App\Services\Matchday\MatchdayLeaderboardService::class);

        foreach ($subLeagues as $sub) {
            if (!$sub->coversLeague((string) $contest->league_code)) {
                continue;
            }

            $rows = $leaderboard->forSubLeague($sub);

            foreach (array_slice($rows, 0, 3) as $row) {
                $user = \App\Models\User::find($row['user_id']);
                $user?->notify(new \App\Notifications\Matchday\MatchdayTopThreeNotification(
                    contest: $contest,
                    subLeague: $sub,
                    rank: $row['rank'],
                    points: $row['points'],
                ));
            }
        }
    }
}