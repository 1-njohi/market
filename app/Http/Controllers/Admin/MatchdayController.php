<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Services\ContestScoringService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MatchdayController extends Controller
{
    public function index(Request $request)
    {
        $query = Contest::query()
            ->where('is_matchday', true)
            ->withCount(['entries', 'legs'])
            ->withSum('entries', 'score_units'); // Proxy for total volume if you track it here, otherwise adjust

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($season = $request->get('season')) {
            $query->where('season', $season);
        }

        // Aggregate global metrics across all matchdays
        $baseQuery = Contest::where('is_matchday', true);
        
        $stats = [
            'total_matchdays' => (clone $baseQuery)->count(),
            'total_entries' => ContestEntry::whereIn('contest_id', clone $baseQuery->select('id'))->count(),
            'total_picks' => ContestPick::whereIn('contest_entry_id', ContestEntry::whereIn('contest_id', clone $baseQuery->select('id'))->select('id'))->count(),
            
            // Financials (Assuming entry fees or specific pricing logic. 
            // If matchdays are free to play, these will be zero. 
            // If they have a fixed entry fee, we sum that here.)
            'total_prize_pool' => 0, // Update if matchdays have a pool
        ];

        $matchdays = $query->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'name' => $c->name,
                'season' => $c->season,
                'league_code' => $c->league_code,
                'matchday_number' => $c->matchday_number,
                'status' => $c->status,
                'starts_at' => $c->starts_at?->toISOString(),
                'locks_at' => $c->locks_at?->toISOString(),
                'entries_count' => $c->entries_count,
                'legs_count' => $c->legs_count,
                'is_settled' => $c->status === 'settled',
                'pool_value' => 0, // Adjust based on your entry fee logic
            ]);

        return Inertia::render('admin/matchdays/Index', [
            'matchdays' => $matchdays,
            'stats' => $stats,
            'filters' => $request->only(['status', 'season']),
        ]);
    }

    public function show(Contest $matchday)
    {
        abort_if(!$matchday->is_matchday, 404);

        $matchday->load([
            'host:id,name,code',
            'legs.fixture.homeTeam',
            'legs.fixture.awayTeam',
            'legs.market'
        ]);

        // Top of the leaderboard
        $topEntries = ContestEntry::where('contest_id', $matchday->id)
            ->where('status', 'accepted')
            ->with('user:id,name,code')
            ->orderByDesc('score_correct')
            ->orderByDesc('score_units')
            ->limit(20)
            ->get();

        // Pick distribution (how many people picked Home, Draw, Away)
        $distribution = ContestPick::whereIn('contest_entry_id', ContestEntry::where('contest_id', $matchday->id)->select('id'))
            ->select('contest_leg_id', 'selection', DB::raw('COUNT(*) as count'))
            ->groupBy('contest_leg_id', 'selection')
            ->get()
            ->groupBy('contest_leg_id');

        return Inertia::render('admin/matchdays/Show', [
            'matchday' => [
                'id' => $matchday->id,
                'uuid' => $matchday->uuid,
                'name' => $matchday->name,
                'description' => $matchday->description,
                'status' => $matchday->status,
                'season' => $matchday->season,
                'league_code' => $matchday->league_code,
                'matchday_number' => $matchday->matchday_number,
                'starts_at' => $matchday->starts_at?->toISOString(),
                'locks_at' => $matchday->locks_at?->toISOString(),
                'settled_at' => $matchday->settled_at?->toISOString(),
                'host' => $matchday->host,
            ],
            'top_entries' => $topEntries->map(fn($e) => [
                'id' => $e->id,
                'user' => $e->user,
                'score_correct' => (int) $e->score_correct,
                'score_units' => (float) $e->score_units,
                'rank' => $e->rank_current,
            ]),
            'legs' => $matchday->legs->map(fn($leg) => [
                'id' => $leg->id,
                'fixture' => [
                    'home' => $leg->fixture?->homeTeam?->name ?? 'TBD',
                    'away' => $leg->fixture?->awayTeam?->name ?? 'TBD',
                    'date' => $leg->fixture?->date?->toISOString(),
                ],
                'market' => $leg->market?->name ?? 'Unknown',
                'status' => $leg->status,
                'result_selections' => $leg->result_selections,
                'distribution' => $distribution->get($leg->id, collect())->map(fn($d) => [
                    'selection' => $d->selection,
                    'count' => (int) $d->count,
                ]),
            ]),
        ]);
    }

    public function settle(Contest $matchday, ContestScoringService $scoring)
    {
        abort_if(!$matchday->is_matchday, 404);
        
        if ($matchday->status === 'settled') {
            return back()->with('error', 'This matchday is already settled.');
        }

        try {
            $scoring->scoreFinal($matchday);
            return back()->with('success', 'Matchday settled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to settle: ' . $e->getMessage());
        }
    }
}