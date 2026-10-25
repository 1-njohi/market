<?php

namespace App\Services\Jackpot;

use App\Models\Contest;
use App\Models\ContestLeg;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JackpotDistributionService
{
    /**
     * Returns percentage per option, per leg, ONLY for legs the viewer has
     * already picked. Hidden legs are omitted from the payload entirely.
     *
     * @return array<int, array<string, int>>   leg_id => ['Home' => 65, ...]
     */
    public function distributionFor(Contest $contest, User $viewer): array
    {
        // Legs the viewer has revealed (picked) — these are eligible for display.
        $revealedLegIds = DB::table('contest_picks as cp')
            ->join('contest_entries as ce', 'ce.id', '=', 'cp.contest_entry_id')
            ->where('ce.contest_id', $contest->id)
            ->where('ce.user_id', $viewer->id)
            ->where('ce.status', 'accepted')
            ->whereNotNull('cp.revealed_at')
            ->pluck('cp.contest_leg_id')
            ->unique()
            ->values()
            ->all();

        if (empty($revealedLegIds)) {
            return [];
        }

        // Fetch each revealed leg's underlying fixture so we can look up the
        // valid option set (from the odds table).
        $revealedLegs = \App\Models\ContestLeg::whereIn('id', $revealedLegIds)
            ->get(['id', 'fixture_id', 'market_id'])
            ->keyBy('id');

        $optionsByLeg = [];
        foreach ($revealedLegs as $leg) {
            $optionsByLeg[$leg->id] = DB::table('odds')
                ->where('fixture_id', $leg->fixture_id)
                ->where('market_id', $leg->market_id)
                ->pluck('value')
                ->unique()
                ->values()
                ->all();
        }

        // Aggregate accepted picks per (leg, selection).
        $counts = DB::table('contest_picks as cp')
            ->join('contest_entries as ce', 'ce.id', '=', 'cp.contest_entry_id')
            ->where('ce.contest_id', $contest->id)
            ->where('ce.status', 'accepted')
            ->whereIn('cp.contest_leg_id', $revealedLegIds)
            ->selectRaw('cp.contest_leg_id as leg_id, cp.selection, COUNT(*) as c')
            ->groupBy('cp.contest_leg_id', 'cp.selection')
            ->get()
            ->groupBy('leg_id');

        $distribution = [];

        foreach ($revealedLegIds as $legId) {
            $options = $optionsByLeg[$legId] ?? [];
            $countsForLeg = $counts->get($legId, collect());

            // Fallback: if odds aren't seeded for this fixture/market (common
            // in tests, or after a purge), derive the option set from what
            // people actually picked. Missing options show as 0%.
            if (empty($options)) {
                $options = $countsForLeg->pluck('selection')->unique()->values()->all();
            }

            $total = (int) $countsForLeg->sum('c');

            if ($total === 0) {
                continue;
            }
            $row = [];
            foreach ($options as $option) {
                $count = (int) ($countsForLeg->firstWhere('selection', $option)?->c ?? 0);

                $row[$option] = [
                    'percentage' => (int) round($count / $total * 100),
                    'fraction' => "{$count}/{$total}",
                ];
            }

            // Sort the row keys to enforce Home -> Draw -> Away (or the standard
            // order of other markets). This prevents DB grouping order from
            // leaking into the UI.
            $order = ['Home' => 0, 'Draw' => 1, 'Away' => 2];
            uksort($row, function ($a, $b) use ($order) {
                // If both are in the known order map, use that.
                if (isset($order[$a]) && isset($order[$b])) {
                    return $order[$a] <=> $order[$b];
                }
                // If one is known, put it first.
                if (isset($order[$a]))
                    return -1;
                if (isset($order[$b]))
                    return 1;
                // Otherwise fall back to alphabetical.
                return strcmp($a, $b);
            });

            $distribution[$legId] = $row;
        }

        return $distribution;
    }
    /**
     * Count of legs where the crowd's majority pick was correct.
     */
    public function consensusScore(Contest $contest): int
    {
        $legs = ContestLeg::where('contest_id', $contest->id)
            ->where('status', '!=', 'pending')
            ->get();

        if ($legs->isEmpty()) {
            return 0;
        }

        $counts = DB::table('contest_picks as cp')
            ->join('contest_entries as ce', 'ce.id', '=', 'cp.contest_entry_id')
            ->where('ce.contest_id', $contest->id)
            ->where('ce.status', 'accepted')
            ->whereIn('cp.contest_leg_id', $legs->pluck('id'))
            ->selectRaw('cp.contest_leg_id as leg_id, cp.selection, COUNT(*) as c')
            ->groupBy('cp.contest_leg_id', 'cp.selection')
            ->get()
            ->groupBy('leg_id');

        $score = 0;

        foreach ($legs as $leg) {
            $countsForLeg = $counts->get($leg->id, collect());
            if ($countsForLeg->isEmpty())
                continue;

            $majority = $countsForLeg->sortByDesc('c')->first();
            if (!$majority)
                continue;

            $winning = $leg->result_selections ?? [];
            if (!is_array($winning))
                $winning = [$winning];

            if (in_array($majority->selection, $winning, true)) {
                $score++;
            }
        }

        return $score;
    }
}