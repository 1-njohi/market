<?php

namespace App\Services\Jackpot;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JackpotDigestService
{
    /**
     * Generates the post-settlement analysis for a specific user.
     */
    public function forUser(Contest $jackpot, User $user): array
    {
        $legs = ContestLeg::where('contest_id', $jackpot->id)
            ->with(['fixture.homeTeam', 'fixture.awayTeam', 'market'])
            ->orderBy('id')
            ->get();

        $userPicks = ContestPick::whereHas('entry', fn($q) => $q->where('contest_id', $jackpot->id)->where('user_id', $user->id))
            ->get()
            ->keyBy('contest_leg_id');

        // Get total crowd picks per leg
        $crowdCounts = ContestPick::whereIn('contest_leg_id', $legs->pluck('id'))
            ->select('contest_leg_id', 'selection', DB::raw('COUNT(*) as count'))
            ->groupBy('contest_leg_id', 'selection')
            ->get()
            ->groupBy('contest_leg_id');

        $totalEntrants = \App\Models\ContestEntry::where('contest_id', $jackpot->id)->where('status', 'accepted')->count();

        $legsData = [];
        $userScore = 0;
        $crowdScore = 0;
        $contrarianScore = 0;
        $contrarianWins = 0;
        $contrarianAttempts = 0;

        foreach ($legs as $leg) {
            $counts = $crowdCounts->get($leg->id, collect());

            // 1. Determine Crowd Majority Pick
            $majorityPick = $counts->sortByDesc('count')->first();
            $majoritySelection = $majorityPick ? $majorityPick->selection : null;
            $majorityCount = $majorityPick ? (int) $majorityPick->count : 0;
            $majorityPercent = $totalEntrants > 0 ? round(($majorityCount / $totalEntrants) * 100) : 0;

            // 2. Determine Outcomes
            $winningSelections = $leg->result_selections ?? [];
            if (!is_array($winningSelections))
                $winningSelections = [$winningSelections];

            $crowdWon = $majoritySelection && in_array($majoritySelection, $winningSelections, true);
            if ($crowdWon)
                $crowdScore++;

            // 3. Analyze the User's Pick
            $userPick = $userPicks->get($leg->id);
            $userSelection = $userPick ? $userPick->selection : null;
            $userWon = $userSelection && in_array($userSelection, $winningSelections, true);

            if ($userWon)
                $userScore++;

            // 4. Contrarian Analysis
            $isContrarian = $userSelection && $majoritySelection && $userSelection !== $majoritySelection;

            if ($isContrarian) {
                $contrarianAttempts++;
                if ($userWon) {
                    $contrarianScore++;
                    $contrarianWins++;
                }
            }

            // Format distribution for the UI
            $distribution = $counts->map(function ($c) use ($totalEntrants) {
                return [
                    'selection' => $c->selection,
                    'count' => (int) $c->count,
                    'percentage' => $totalEntrants > 0 ? round(($c->count / $totalEntrants) * 100) : 0,
                ];
            })->sortByDesc('count')->values()->all();

            $legsData[] = [
                'id' => $leg->id,
                'fixture' => [
                    'home' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                    'away' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                ],
                'market' => $leg->market?->name ?? 'Market',
                'winning_selections' => $winningSelections,
                'crowd_majority' => [
                    'selection' => $majoritySelection,
                    'percentage' => $majorityPercent,
                    'won' => $crowdWon,
                ],
                'user_pick' => [
                    'selection' => $userSelection,
                    'won' => $userWon,
                    'is_contrarian' => $isContrarian,
                ],
                'distribution' => $distribution,
            ];
        }

        return [
            'legs' => $legsData,
            'totals' => [
                'total_entrants' => $totalEntrants,
                'user_score' => $userScore,
                'crowd_score' => $crowdScore,
                'contrarian_attempts' => $contrarianAttempts,
                'contrarian_wins' => $contrarianWins,
                'contrarian_accuracy' => $contrarianAttempts > 0 ? round(($contrarianWins / $contrarianAttempts) * 100) : 0,
            ]
        ];
    }

    /**
     * Produces a prose narrative summarizing the jackpot for the user.
     * Returns an array of talking points, each with a type (for styling)
     * and a sentence body.
     *
     * @return array<int, array{type: string, text: string}>
     */
    public function narrativeFor(array $digest, string $jackpotName): array
    {
        $legs = $digest['legs'];
        $totals = $digest['totals'];

        $points = [];

        // ── 1. Headline: user vs. crowd score ────────────────────────
        $userScore = $totals['user_score'];
        $crowdScore = $totals['crowd_score'];
        $totalLegs = count($legs);

        if ($userScore > $crowdScore) {
            $diff = $userScore - $crowdScore;
            $points[] = [
                'type' => 'positive',
                'text' => "You beat the crowd on {$jackpotName}. Your ticket scored {$userScore}/{$totalLegs}, while the crowd's consensus managed {$crowdScore}. That's a {$diff}-leg edge over the majority.",
            ];
        } elseif ($userScore < $crowdScore) {
            $diff = $crowdScore - $userScore;
            $points[] = [
                'type' => 'negative',
                'text' => "The crowd outscored you this time. Your ticket got {$userScore}/{$totalLegs} while the consensus got {$crowdScore}. A {$diff}-leg gap — not a bad result for a wide-open jackpot, but not quite enough to out-think the field.",
            ];
        } else {
            $points[] = [
                'type' => 'neutral',
                'text' => "You and the crowd finished level on {$jackpotName}. Both tickets scored {$userScore}/{$totalLegs}. Statistically, that's the most common outcome — but nobody wins bragging rights on a tie.",
            ];
        }

        // ── 2. Contrarian analysis ──────────────────────────────────
        $attempts = $totals['contrarian_attempts'];
        $wins = $totals['contrarian_wins'];

        if ($attempts > 0) {
            $pct = $totals['contrarian_accuracy'];
            if ($pct >= 60) {
                $points[] = [
                    'type' => 'positive',
                    'text' => "On the {$attempts} legs where you disagreed with the majority, you got {$wins} right ({$pct}% accuracy). That's a strong contrarian read — you saw what the crowd missed.",
                ];
            } elseif ($pct >= 40) {
                $points[] = [
                    'type' => 'neutral',
                    'text' => "You went against the crowd on {$attempts} legs and got {$wins} right ({$pct}%). A balanced contrarian performance — right about as often as the rest of the field.",
                ];
            } else {
                $points[] = [
                    'type' => 'negative',
                    'text' => "You disagreed with the majority {$attempts} times, but only got {$wins} right ({$pct}%). Worth reviewing: the crowd's read was stronger than yours on the contrarian calls this round.",
                ];
            }
        } else {
            $points[] = [
                'type' => 'neutral',
                'text' => "You matched the crowd on every leg — no contrarian bets. Sometimes the safest move is to ride the consensus.",
            ];
        }

        // ── 3. The crowd's biggest mistake ───────────────────────────
        // Find the leg with the highest majority % that the crowd got wrong.
        $worstCrowdLeg = null;
        $worstCrowdPct = 0;

        foreach ($legs as $i => $leg) {
            if (!$leg['crowd_majority']['won'] && $leg['crowd_majority']['percentage'] > $worstCrowdPct) {
                $worstCrowdPct = $leg['crowd_majority']['percentage'];
                $worstCrowdLeg = $leg;
            }
        }

        if ($worstCrowdLeg) {
            $fixture = "{$worstCrowdLeg['fixture']['home']} vs {$worstCrowdLeg['fixture']['away']}";
            $pick = $worstCrowdLeg['crowd_majority']['selection'];
            $pct = $worstCrowdLeg['crowd_majority']['percentage'];

            // Did the user catch this one?
            $userCaughtIt = $worstCrowdLeg['user_pick']['selection']
                && $worstCrowdLeg['user_pick']['won']
                && $worstCrowdLeg['user_pick']['selection'] !== $pick;

            if ($userCaughtIt) {
                $points[] = [
                    'type' => 'positive',
                    'text' => "The biggest crowd mistake was on {$fixture}. {$pct}% of entrants picked \"{$pick}\" and lost — but you caught it. That's the kind of read that separates you from the field.",
                ];
            } else {
                $points[] = [
                    'type' => 'negative',
                    'text' => "The biggest crowd mistake was on {$fixture}. {$pct}% of entrants picked \"{$pick}\" and it lost. You didn't catch this one either — but neither did most of the field.",
                ];
            }
        } else {
            $points[] = [
                'type' => 'positive',
                'text' => "Impressive round for the crowd: their majority pick won every single leg. Even a perfect contrarian wouldn't have out-scored the consensus this week.",
            ];
        }

        // ── 4. The user's best contrarian pick ───────────────────────
        $bestContrarian = null;
        $bestContrarianPct = 0;

        foreach ($legs as $leg) {
            if (
                $leg['user_pick']['is_contrarian']
                && $leg['user_pick']['won']
                && $leg['crowd_majority']['percentage'] > $bestContrarianPct
            ) {
                $bestContrarianPct = $leg['crowd_majority']['percentage'];
                $bestContrarian = $leg;
            }
        }

        if ($bestContrarian) {
            $fixture = "{$bestContrarian['fixture']['home']} vs {$bestContrarian['fixture']['away']}";
            $pick = $bestContrarian['user_pick']['selection'];
            $pct = $bestContrarian['crowd_majority']['percentage'];

            $points[] = [
                'type' => 'positive',
                'text' => "Your boldest winning call: on {$fixture}, only " . (100 - $pct) . "% of the crowd agreed with your \"{$pick}\" pick — and it came in. That's a genuine diamond in the rough.",
            ];
        }

        // ── 5. Perfect vs. missed legs ───────────────────────────────
        $perfectLegs = 0;
        $missedLegs = 0;
        foreach ($legs as $leg) {
            if ($leg['user_pick']['won'])
                $perfectLegs++;
            if ($leg['user_pick']['selection'] && !$leg['user_pick']['won'])
                $missedLegs++;
        }

        $blankLegs = $totalLegs - $perfectLegs - $missedLegs;

        if ($blankLegs > 0) {
            $points[] = [
                'type' => 'neutral',
                'text' => "You left {$blankLegs} leg" . ($blankLegs === 1 ? '' : 's') . " blank. In jackpots, an unpicked leg scores zero — so a full ticket always gives you the best shot.",
            ];
        }

        return $points;
    }

    /**
     * Generates a live narrative for a jackpot that has locked but is not
     * yet settled. Returns null if the jackpot hasn't locked yet.
     *
     * @return array{
     *     state: string,
     *     scoreboard: array{
     *         user_correct: int,
     *         crowd_correct: int,
     *         legs_resolved: int,
     *         legs_pending: int,
     *         total_legs: int
     *     },
     *     swing_legs: array<int, array{
     *         leg_id: int,
     *         fixture: string,
     *         user_pick: string,
     *         crowd_pick: string,
     *         crowd_pct: int,
     *         kickoff: string|null
     *     }>,
     *     talking_points: array<int, array{type: string, text: string}>
     * }|null
     */
    public function liveNarrativeFor(Contest $jackpot, User $user): ?array
    {
        if ($jackpot->locks_at === null || now()->lt($jackpot->locks_at)) {
            return null;   // pre-lock, don't reveal anything
        }

        $legs = ContestLeg::where('contest_id', $jackpot->id)
            ->with(['fixture.homeTeam', 'fixture.awayTeam', 'market'])
            ->orderBy('id')
            ->get();

        $userPicks = ContestPick::whereHas(
            'entry',
            fn($q) => $q->where('contest_id', $jackpot->id)->where('user_id', $user->id)
        )->get()->keyBy('contest_leg_id');

        $crowdCounts = ContestPick::whereIn('contest_leg_id', $legs->pluck('id'))
            ->select('contest_leg_id', 'selection', DB::raw('COUNT(*) as count'))
            ->groupBy('contest_leg_id', 'selection')
            ->get()
            ->groupBy('contest_leg_id');

        $totalEntrants = ContestEntry::where('contest_id', $jackpot->id)
            ->where('status', 'accepted')
            ->count();

        $userCorrect = 0;
        $crowdCorrect = 0;
        $resolved = 0;
        $swingLegs = [];

        foreach ($legs as $leg) {
            $isResolved = $leg->status !== 'pending';
            if ($isResolved) {
                $resolved++;
            }

            $counts = $crowdCounts->get($leg->id, collect());
            $majorityPick = $counts->sortByDesc('count')->first();
            $majoritySelection = $majorityPick?->selection;
            $majorityPct = ($totalEntrants > 0 && $majorityPick)
                ? (int) round(($majorityPick->count / $totalEntrants) * 100)
                : 0;

            $winning = $leg->result_selections ?? [];
            if (!is_array($winning)) {
                $winning = [$winning];
            }

            $userPick = $userPicks->get($leg->id);
            $userSelection = $userPick?->selection;

            if ($isResolved) {
                if ($userSelection && in_array($userSelection, $winning, true)) {
                    $userCorrect++;
                }
                if ($majoritySelection && in_array($majoritySelection, $winning, true)) {
                    $crowdCorrect++;
                }
            } else {
                // Pending leg — capture swing legs (user and crowd disagree)
                if ($userSelection && $majoritySelection && $userSelection !== $majoritySelection) {
                    $swingLegs[] = [
                        'leg_id' => (int) $leg->id,
                        'fixture' => ($leg->fixture?->homeTeam?->name ?? 'TBD')
                            . ' vs '
                            . ($leg->fixture?->awayTeam?->name ?? 'TBD'),
                        'user_pick' => $userSelection,
                        'crowd_pick' => $majoritySelection,
                        'crowd_pct' => $majorityPct,
                        'kickoff' => $leg->fixture?->date?->toIso8601String(),
                    ];
                }
            }
        }

        $totalLegs = $legs->count();
        $pending = $totalLegs - $resolved;

        if ($resolved === 0) {
            $state = 'locked_no_results';
        } elseif ($pending === 0) {
            $state = 'settled';
        } elseif ($resolved <= 3) {
            $state = 'early';
        } elseif ($pending <= 2) {
            $state = 'late';
        } else {
            $state = 'mid';
        }

        $talkingPoints = $this->buildLiveTalkingPoints(
            $state,
            $userCorrect,
            $crowdCorrect,
            $resolved,
            $pending,
            $totalLegs,
            $swingLegs,
            $totalEntrants,
            $jackpot,
        );

        return [
            'state' => $state,
            'scoreboard' => [
                'user_correct' => $userCorrect,
                'crowd_correct' => $crowdCorrect,
                'legs_resolved' => $resolved,
                'legs_pending' => $pending,
                'total_legs' => $totalLegs,
            ],
            'swing_legs' => $swingLegs,
            'talking_points' => $talkingPoints,
        ];
    }

    /**
     * @return array<int, array{type: string, text: string}>
     */
    private function buildLiveTalkingPoints(
        string $state,
        int $userCorrect,
        int $crowdCorrect,
        int $resolved,
        int $pending,
        int $totalLegs,
        array $swingLegs,
        int $totalEntrants,
        Contest $jackpot,
    ): array {
        $points = [];

        if ($state === 'locked_no_results') {
            $points[] = [
                'type' => 'neutral',
                'text' => "The crowd has spoken. {$totalEntrants} tickets locked in for {$jackpot->name}. The first fixtures kick off soon — you'll see the crowd's score update leg by leg.",
            ];

            if (!empty($swingLegs)) {
                $count = count($swingLegs);
                $points[] = [
                    'type' => 'neutral',
                    'text' => "You and the crowd disagree on {$count} leg" . ($count === 1 ? '' : 's') . ". Those are your swing legs — every one of them is a chance to pull ahead or fall behind.",
                ];
            }

            return $points;
        }

        $diff = $userCorrect - $crowdCorrect;

        // ── Opening paragraph: relative position ──────────────────────
        if ($state === 'early') {
            if ($diff > 0) {
                $points[] = [
                    'type' => 'positive',
                    'text' => "Early days, and you're ahead. You: {$userCorrect} correct · Crowd: {$crowdCorrect}, with {$resolved} of {$totalLegs} legs resolved.",
                ];
            } elseif ($diff < 0) {
                $points[] = [
                    'type' => 'negative',
                    'text' => "The crowd is off to a strong start. Crowd: {$crowdCorrect} · You: {$userCorrect}, after {$resolved} of {$totalLegs} legs.",
                ];
            } else {
                $points[] = [
                    'type' => 'neutral',
                    'text' => "Level pegging. Both you and the crowd are on {$userCorrect} correct after {$resolved} leg" . ($resolved === 1 ? '' : 's') . ".",
                ];
            }
        }

        if ($state === 'mid') {
            if ($diff > 0) {
                $points[] = [
                    'type' => 'positive',
                    'text' => "You're {$diff} ahead of the crowd — {$userCorrect} correct against their {$crowdCorrect}, with {$pending} legs still to play. The pressure is on them.",
                ];
            } elseif ($diff < 0) {
                $points[] = [
                    'type' => 'urgent',
                    'text' => "You're " . abs($diff) . " behind the crowd — {$userCorrect} correct against their {$crowdCorrect}, with {$pending} legs still to play. Closeable, but only if the remaining calls go your way.",
                ];
            } else {
                $points[] = [
                    'type' => 'neutral',
                    'text' => "All square at {$userCorrect} correct each, with {$pending} legs left. Every remaining pick is a decider.",
                ];
            }
        }

        if ($state === 'late') {
            $gap = abs($diff);
            if ($diff > 0) {
                $points[] = [
                    'type' => 'positive',
                    'text' => "One leg from glory. You're {$gap} ahead with {$pending} leg" . ($pending === 1 ? '' : 's') . " still to play. Hold your nerve and this is yours.",
                ];
            } elseif ($diff < 0) {
                $points[] = [
                    'type' => 'urgent',
                    'text' => "It comes down to the wire. You need to claw back {$gap} correct with only {$pending} leg" . ($pending === 1 ? '' : 's') . " remaining. Every call matters.",
                ];
            } else {
                $points[] = [
                    'type' => 'neutral',
                    'text' => "Dead heat with {$pending} leg" . ($pending === 1 ? '' : 's') . " left. Whoever gets this next pick right wins the bragging rights.",
                ];
            }
        }

        // ── Swing legs paragraph (names the specific fixture) ─────────
        if (!empty($swingLegs)) {
            $count = count($swingLegs);
            $first = $swingLegs[0];

            if ($state === 'late') {
                // In the late stage, name the exact fixture and what must happen.
                $points[] = [
                    'type' => 'urgent',
                    'text' => "All eyes on {$first['fixture']}. You picked \"{$first['user_pick']}\", the crowd picked \"{$first['crowd_pick']}\" ({$first['crowd_pct']}%). Whoever gets this one right wins the head-to-head.",
                ];
            } elseif ($count === 1) {
                $points[] = [
                    'type' => 'urgent',
                    'text' => "Everything hinges on {$first['fixture']}. You went with {$first['user_pick']}, but {$first['crowd_pct']}% of the crowd backed {$first['crowd_pick']}. If you're right, you gain a leg on the majority.",
                ];
            } else {
                $points[] = [
                    'type' => 'positive',
                    'text' => "{$count} swing legs remain — legs where your pick differs from the crowd's majority. The biggest is {$first['fixture']}: you picked {$first['user_pick']}, the crowd picked {$first['crowd_pick']} ({$first['crowd_pct']}%). Those are the ones to watch.",
                ];
            }
        }

        return $points;
    }
}