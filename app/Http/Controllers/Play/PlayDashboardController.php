<?php

namespace App\Http\Controllers\Play;

use App\Http\Controllers\Controller;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestPick;
use App\Models\PredictionPointLog;
use App\Models\SubLeague;
use App\Models\User;
use App\Services\Matchday\MatchdayLeaderboardService;
use App\Services\SellerEligibilityService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlayDashboardController extends Controller
{
    private const LEADERBOARD_PREVIEW_LIMIT = 5;
    private const RECENT_SETTLED_LIMIT = 10;
    private const PORTFOLIO_LOGS_LIMIT = 10;

    public function __construct(
        protected MatchdayLeaderboardService $leaderboard,
        protected SellerEligibilityService $sellerEligibility,
    ) {}

    public function index(): Response
    {
        $user = Auth::user();
        $season = config('marketplace.matchday.seasons.0');

        $betslips  = $this->betslipsFor($user);
        $contests  = $this->contestsFor($user);
        $matchdays = $this->matchdaysFor($user, $season);
        $jackpots  = $this->jackpotsFor();
        $portfolio = $this->portfolioFor($user);

        return Inertia::render('play/Dashboard', [
            'play_data' => [
                'user'            => $this->userPayload($user),
                'overview'        => $this->overviewFor($user, $betslips, $contests, $matchdays, $jackpots, $portfolio),
                'betslips'        => $betslips,
                'contests'        => $contests,
                'matchdays'       => $matchdays,
                'jackpots'        => $jackpots,
                'portfolio'       => $portfolio,
                'seller_progress' => $this->sellerEligibility->progress($user),
                'seller_status'   => $user->sellable_status,
                'generated_at'    => now()->toIso8601String(),
            ],
        ]);
    }

    /* ─────────────────────  USER  ───────────────────── */

    private function userPayload(User $user): array
    {
        return [
            'id'              => (int) $user->id,
            'name'            => $user->name,
            'code'            => $user->code,
            'avatar'          => $user->profile_picture_url
                ?? 'https://api.dicebear.com/10.x/thumbs/svg?seed=' . urlencode($user->name),
            'is_verified'     => $user->email_verified_at !== null,
            'joined_ago'      => $user->joined_ago,
            'sellable_status' => $user->sellable_status,
            'has_purchased'   => $this->hasPurchased($user),
        ];
    }

    private function hasPurchased(User $user): bool
    {
        return BetslipUserPurchase::where('buyer_id', $user->id)->exists();
    }

    /* ─────────────────────  OVERVIEW  ───────────────────── */

    private function overviewFor(User $user, array $betslips, array $contests, array $matchdays, array $jackpots, array $portfolio): array
    {
        $needsAttention = [];

        foreach ($matchdays['unacknowledged_settlements'] as $s) {
            $needsAttention[] = [
                'key'   => 'settlement-' . $s['entry_id'],
                'tone'  => 'amber',
                'title' => "Matchday {$s['matchday_number']} settled",
                'body'  => "You ranked #{$s['rank']} of {$s['total_participants']} · {$s['correct']} correct",
                'href'  => '/matchday',
            ];
        }

        foreach ($contests['created'] as $c) {
            if ($c['pending_entries'] > 0) {
                $needsAttention[] = [
                    'key'   => 'contest-pending-' . $c['id'],
                    'tone'  => 'sky',
                    'title' => "{$c['pending_entries']} pending " . ($c['pending_entries'] === 1 ? 'entry' : 'entries'),
                    'body'  => $c['name'],
                    'href'  => "/contests/{$c['uuid']}/manage",
                ];
            }
        }

        foreach ($matchdays['open'] as $m) {
            if ($m['is_locked'] || $m['status'] !== 'open') continue;
            if ($m['is_entered'] && $m['picks_submitted'] < $m['picks_required']) {
                $needsAttention[] = [
                    'key'   => 'matchday-pending-' . $m['uuid'],
                    'tone'  => 'rose',
                    'title' => "Picks pending for MD{$m['matchday_number']}",
                    'body'  => "{$m['picks_submitted']}/{$m['picks_required']} submitted",
                    'href'  => "/contests/{$m['uuid']}/picks",
                ];
            }
        }

        return [
            'stats' => [
                'active_betslips'   => $betslips['counts']['active'],
                'settled_betslips'  => $betslips['counts']['settled'],
                'created_contests'  => count($contests['created']),
                'entered_contests'  => count($contests['entered']),
                'open_matchdays'    => count($matchdays['open']),
                'open_jackpots'     => count($jackpots['open']),
                'reputation_points' => $portfolio['stats']['current_points'],
                'global_rank'       => $portfolio['stats']['global_rank'],
                'total_ranked'      => $portfolio['stats']['total_ranked'],
                'accuracy'          => $portfolio['stats']['accuracy'],
            ],
            'needs_attention' => array_slice($needsAttention, 0, 5),
        ];
    }

    /* ─────────────────────  BETSLIPS  ───────────────────── */

    private function betslipsFor(User $user): array
    {
        $base = fn () => Betslip::query()
            ->where('user_id', $user->id)
            ->where('is_sellable', false);

        $active = $base()
            ->whereIn('status', ['pending', 'underway'])
            ->orderByDesc('created_at')
            ->withCount('Odds as legs_count')
            ->get()
            ->map(fn (Betslip $b) => $this->betslipRow($b))
            ->values()
            ->all();

        $recentSettled = $base()
            ->where('status', 'settled')
            ->orderByDesc('settled_at')
            ->limit(self::RECENT_SETTLED_LIMIT)
            ->withCount('Odds as legs_count')
            ->get()
            ->map(fn (Betslip $b) => $this->betslipRow($b))
            ->values()
            ->all();

        $counts = [
            'active'  => $base()->whereIn('status', ['pending', 'underway'])->count(),
            'settled' => $base()->where('status', 'settled')->count(),
            'won'     => $base()->where('status', 'settled')->where('is_winner', true)->count(),
            'lost'    => $base()->where('status', 'settled')->where('is_winner', false)->count(),
        ];

        return [
            'active'         => $active,
            'recent_settled' => $recentSettled,
            'counts'         => $counts,
        ];
    }

    private function betslipRow(Betslip $b): array
    {
        return [
            'id'         => (int) $b->id,
            'code'       => $b->code,
            'status'     => $b->status,
            'total_odds' => (float) $b->total_odds,
            'legs_count' => (int) ($b->legs_count ?? 0),
            'is_winner'  => (bool) $b->is_winner,
            'created_at' => $b->created_at?->toISOString(),
            'settled_at' => $b->settled_at?->toISOString(),
            'caption'    => $b->caption,
        ];
    }

    /* ─────────────────────  CONTESTS  ───────────────────── */

    private function contestsFor(User $user): array
    {
        $created = Contest::where('host_id', $user->id)
            ->where('is_matchday', false)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->withCount([
                'entries as pending_entries'  => fn ($q) => $q->where('status', 'pending'),
                'entries as accepted_entries' => fn ($q) => $q->where('status', 'accepted'),
            ])
            ->get()
            ->map(fn (Contest $c) => [
                'id'                => (int) $c->id,
                'uuid'              => $c->uuid,
                'name'              => $c->name,
                'status'            => $c->status,
                'visibility'        => $c->visibility,
                'entry_deadline_at' => $c->entry_deadline_at?->toISOString(),
                'starts_at'         => $c->starts_at?->toISOString(),
                'ends_at'           => $c->ends_at?->toISOString(),
                'pending_entries'   => (int) $c->pending_entries,
                'accepted_entries'  => (int) $c->accepted_entries,
                'created_at'        => $c->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        $enteredIds = ContestEntry::where('user_id', $user->id)
            ->whereIn('status', ['accepted', 'pending'])
            ->pluck('contest_id');

        $entered = Contest::whereIn('id', $enteredIds)
            ->where('is_matchday', false)
            ->whereIn('status', ['open', 'locked', 'underway', 'settled'])
            ->orderByDesc('locks_at')
            ->withCount([
                'entries as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
                'legs',
            ])
            ->get()
            ->map(function (Contest $c) use ($user) {
                $entry = ContestEntry::where('contest_id', $c->id)
                    ->where('user_id', $user->id)
                    ->first();

                $picksCount = $entry
                    ? ContestPick::where('contest_entry_id', $entry->id)->count()
                    : 0;

                return [
                    'id'              => (int) $c->id,
                    'uuid'            => $c->uuid,
                    'name'            => $c->name,
                    'status'          => $c->status,
                    'locks_at'        => $c->locks_at?->toISOString(),
                    'settled_at'      => $c->settled_at?->toISOString(),
                    'is_locked'       => $c->locks_at !== null && now()->gte($c->locks_at),
                    'legs_count'      => (int) ($c->legs_count ?? 0),
                    'picks_submitted' => (int) $picksCount,
                    'entry_status'    => $entry?->status,
                    'rank'            => $entry?->rank_current,
                    'score_correct'   => $entry?->score_correct,
                    'participants'    => (int) ($c->accepted_count ?? 0),
                ];
            })
            ->values()
            ->all();

        return ['created' => $created, 'entered' => $entered];
    }

    /* ─────────────────────  MATCHDAYS  ───────────────────── */

    private function matchdaysFor(User $user, string $season): array
    {
        $subLeagues = SubLeague::query()
            ->where('season', $season)
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('members')
            ->orderBy('name')
            ->get()
            ->map(function (SubLeague $sub) {
                $rows = $this->leaderboard->forSubLeague($sub);

                return [
                    'id'                  => (int) $sub->id,
                    'name'                => $sub->name,
                    'invite_code'         => $sub->invite_code,
                    'member_count'        => (int) $sub->members_count,
                    'max_members'         => (int) $sub->max_members,
                    'is_owner'            => (int) $sub->owner_id === (int) Auth::id(),
                    'leaderboard_preview' => array_slice($rows, 0, self::LEADERBOARD_PREVIEW_LIMIT),
                ];
            })
            ->values()
            ->all();

        $open = $this->openMatchdaysFor($user, $season);
        $summary = $this->leaderboard->summaryForUser($user, $season);

        return [
            'season'                     => $season,
            'open'                       => $open,
            'sub_leagues'                => $subLeagues,
            'summary'                    => $summary['matchdays_settled'] > 0 ? $summary : null,
            'unacknowledged_settlements' => $this->unacknowledgedSettlements($user, $season),
            'live_rows'                  => $this->leaderboard->liveForUser($user, $season),
            'form_guide'                 => $this->leaderboard->formGuideForUser($user, $season),
        ];
    }

    private function openMatchdaysFor(User $user, string $season): array
    {
        $contests = Contest::query()
            ->where('is_matchday', true)
            ->where('season', $season)
            ->whereNotIn('status', ['settled', 'cancelled'])
            ->orderBy('locks_at')
            ->with(['legs'])
            ->get();

        if ($contests->isEmpty()) return [];

        $entries = ContestEntry::query()
            ->whereIn('contest_id', $contests->pluck('id'))
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->get()
            ->keyBy('contest_id');

        $pickCounts = ContestPick::query()
            ->whereIn('contest_entry_id', $entries->pluck('id'))
            ->selectRaw('contest_entry_id, COUNT(*) as c')
            ->groupBy('contest_entry_id')
            ->pluck('c', 'contest_entry_id');

        return $contests->map(function (Contest $contest) use ($entries, $pickCounts) {
            $entry = $entries->get($contest->id);
            $picksSubmitted = $entry ? (int) ($pickCounts[$entry->id] ?? 0) : 0;

            return [
                'uuid'            => $contest->uuid,
                'name'            => $contest->name,
                'matchday_number' => (int) $contest->matchday_number,
                'league_code'     => $contest->league_code,
                'locks_at'        => $contest->locks_at?->toISOString(),
                'is_locked'       => $contest->locks_at !== null && now()->gte($contest->locks_at),
                'status'          => $contest->status,
                'picks_required'  => $contest->legs->count(),
                'picks_submitted' => $picksSubmitted,
                'is_entered'      => $entry !== null,
            ];
        })->values()->all();
    }

    private function unacknowledgedSettlements(User $user, string $season): array
    {
        $rows = \DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->where('ce.user_id', $user->id)
            ->where('ce.status', 'accepted')
            ->whereNull('ce.dismissed_settlement_at')
            ->whereNotNull('ce.settled_at')
            ->where('c.is_matchday', true)
            ->where('c.season', $season)
            ->where('c.status', 'settled')
            ->orderByDesc('c.settled_at')
            ->limit(3)
            ->get([
                'ce.id as entry_id',
                'ce.score_correct',
                'ce.rank_current',
                'c.uuid as contest_uuid',
                'c.name as contest_name',
                'c.matchday_number',
                'c.league_code',
                'c.settled_at',
            ]);

        if ($rows->isEmpty()) return [];

        $totals = \DB::table('contest_entries as ce')
            ->join('contests as c', 'c.id', '=', 'ce.contest_id')
            ->whereIn('c.uuid', $rows->pluck('contest_uuid')->all())
            ->where('ce.status', 'accepted')
            ->selectRaw('c.uuid, COUNT(*) as total')
            ->groupBy('c.uuid')
            ->pluck('total', 'uuid');

        return $rows->map(fn ($r) => [
            'entry_id'           => (int) $r->entry_id,
            'uuid'               => $r->contest_uuid,
            'name'               => $r->contest_name,
            'matchday_number'    => (int) $r->matchday_number,
            'league_code'        => $r->league_code,
            'correct'            => (int) $r->score_correct,
            'rank'               => (int) $r->rank_current,
            'total_participants' => (int) ($totals[$r->contest_uuid] ?? 0),
            'settled_at'         => \Carbon\Carbon::parse($r->settled_at)->toIso8601String(),
        ])->values()->all();
    }

    /* ─────────────────────  JACKPOTS  ───────────────────── */

    private function jackpotsFor(): array
    {
        $open = Contest::query()
            ->where('type', 'jackpot')
            ->whereIn('status', ['open', 'locked', 'underway'])
            ->orderBy('locks_at')
            ->withCount(['entries as participant_count' => fn ($q) => $q->where('status', 'accepted')])
            ->withCount('legs')
            ->get();

        $recent = Contest::query()
            ->where('type', 'jackpot')
            ->where('status', 'settled')
            ->orderByDesc('settled_at')
            ->limit(10)
            ->withCount(['entries as participant_count' => fn ($q) => $q->where('status', 'accepted')])
            ->withCount('legs')
            ->get();

        return [
            'open'   => $this->jackpotRows($open),
            'recent' => $this->jackpotRows($recent),
        ];
    }

    private function jackpotRows($rows): array
    {
        return $rows->map(fn (Contest $c) => [
            'uuid'              => $c->uuid,
            'name'              => $c->name,
            'status'            => $c->status,
            'locks_at'          => $c->locks_at?->toISOString(),
            'settled_at'        => $c->settled_at?->toISOString(),
            'is_locked'         => $c->locks_at !== null && now()->gte($c->locks_at),
            'legs_count'        => (int) ($c->legs_count ?? 0),
            'participant_count' => (int) ($c->participant_count ?? 0),
        ])->values()->all();
    }

    /* ─────────────────────  PORTFOLIO  ───────────────────── */

    private function portfolioFor(User $user): array
    {
        $logs = PredictionPointLog::where('user_id', $user->id)
            ->latest()
            ->limit(self::PORTFOLIO_LOGS_LIMIT)
            ->get()
            ->map(fn ($log) => [
                'id'            => (int) $log->id,
                'amount'        => (int) $log->amount,
                'balance_after' => (int) $log->balance_after,
                'type'          => $log->type,
                'description'   => $log->description,
                'created_at'    => $log->created_at->toISOString(),
                'created_ago'   => $log->created_at->diffForHumans(),
            ])
            ->values()
            ->all();

        $rank = User::where('prediction_points', '>', $user->prediction_points)->count() + 1;
        $totalRanked = User::where('prediction_points', '>', 0)->count();

        $won  = $user->predictions()->where('status', 'won')->count();
        $lost = $user->predictions()->where('status', 'lost')->count();
        $total = $won + $lost;
        $accuracy = $total === 0 ? null : round(($won / $total) * 100, 1);

        return [
            'stats' => [
                'current_points' => (int) $user->prediction_points,
                'global_rank'    => $user->prediction_points > 0 ? $rank : null,
                'total_ranked'   => (int) $totalRanked,
                'accuracy'       => $accuracy,
            ],
            'logs' => $logs,
        ];
    }
}