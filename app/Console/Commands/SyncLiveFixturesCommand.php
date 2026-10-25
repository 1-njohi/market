<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Contest;
use App\Services\ApiSportsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SyncLiveFixturesCommand extends Command
{
    protected $signature = 'fixtures:sync-live';

    protected $description = 'Poll status and scores for fixtures currently in play.';

    /** Statuses we care to poll. */
    private const CANDIDATE_STATUSES = ['NS', '1H', 'HT', '2H', 'ET', 'P'];

    /** Statuses after which `home_winner` is meaningful. */
    private const TERMINAL_STATUSES = ['FT', 'CANC', 'ABD', 'AWD', 'WO', 'PST'];

    /**
     * Statuses that mean the fixture is in play. Any transition from
     * outside this set into it counts as "kick-off" for the purpose of
     * moving pending betslips to 'underway'.
     *
     * BT (break time), SUSP (suspended), and INT (interrupted) are
     * included defensively — a fixture we first learn about mid-play is
     * still effectively underway.
     */
    private const LIVE_STATUSES = ['1H', 'HT', '2H', 'ET', 'BT', 'P', 'SUSP', 'INT'];

    public function handle(ApiSportsClient $client): int
    {
        $lookback = (int) config('services.api_sports.live_lookback_hours', 3);
        $lookahead = (int) config('services.api_sports.live_lookahead_minutes', 15);

        $candidates = Fixture::whereIn('status_short', self::CANDIDATE_STATUSES)
            ->whereBetween('date', [now()->subHours($lookback), now()->addMinutes($lookahead)])
            ->orderBy('date')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No live candidates. Skipping API call.');
            return self::SUCCESS;
        }

        try {
            $response = $client->fixturesByIds(
                $candidates->pluck('id_on_api')->map(fn($id) => (int) $id)->all()
            );
        } catch (\Throwable $e) {
            Log::error('Failed to sync live fixtures', ['error' => $e->getMessage()]);
            return self::SUCCESS;
        }

        $byApiId = $candidates->keyBy('id_on_api');
        $updated = 0;

        foreach ($response as $item) {
            $apiId = $item['fixture']['id'] ?? null;
            if ($apiId === null || !$byApiId->has($apiId)) {
                continue;
            }

            try {
                $this->applyPayload($byApiId->get($apiId), $item);
                $updated++;
            } catch (\Throwable $e) {
                Log::error("Failed to apply live update for fixture {$apiId}", [
                    'id_on_api' => $apiId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Updated {$updated} fixture(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function applyPayload(Fixture $fixture, array $item): void
    {
        $f = $item['fixture'] ?? [];
        $teams = $item['teams'] ?? [];
        $goals = $item['goals'] ?? [];
        $score = $item['score'] ?? [];
        $status = $f['status'] ?? [];
        $statusShort = $status['short'] ?? null;

        // Capture the previous state before we write the new one. The
        // betslip hook needs to know whether this particular payload is
        // the moment the fixture entered play.
        $previousStatus = $fixture->status_short;

        DB::transaction(function () use ($fixture, $f, $teams, $goals, $score, $status, $statusShort, $previousStatus, $item) {
            $attributes = [
                'status_long' => $status['long'] ?? null,
                'status_short' => $statusShort,
                'status_elapsed' => $status['elapsed'] ?? null,
                'status_extra' => $status['extra'] ?? null,
                'goals_home' => $goals['home'] ?? null,
                'goals_away' => $goals['away'] ?? null,
                'halftime_home' => $score['halftime']['home'] ?? null,
                'halftime_away' => $score['halftime']['away'] ?? null,
                'fulltime_home' => $score['fulltime']['home'] ?? null,
                'fulltime_away' => $score['fulltime']['away'] ?? null,
                'extratime_home' => $score['extratime']['home'] ?? null,
                'extratime_away' => $score['extratime']['away'] ?? null,
                'penalty_home' => $score['penalty']['home'] ?? null,
                'penalty_away' => $score['penalty']['away'] ?? null,
            ];

            // Only write home_winner when the API's status is terminal —
            // during a live match the API returns null and we must not
            // clobber an already-known winner.
            if ($statusShort !== null && in_array($statusShort, self::TERMINAL_STATUSES, true)) {
                $attributes['home_winner'] = (bool) ($teams['home']['winner'] ?? false);
            }

            $fixture->update($attributes);

            // If this payload is the transition from "not yet in play" to
            // "in play", any pending betslip holding an odd on this fixture
            // is no longer buyable — mark it underway. Idempotent: guarded
            // by the transition check, and by a status='pending' filter in
            // the helper, so a re-run of the same payload does nothing.
            $wasLive = in_array($previousStatus, self::LIVE_STATUSES, true);
            $isLive = $statusShort !== null && in_array($statusShort, self::LIVE_STATUSES, true);

            if (!$wasLive && $isLive) {
                $this->markBetslipsUnderway($fixture);
                $this->markContestsUnderway($fixture);
            }

            // Replace events wholesale — idempotent, keeps local state in
            // lockstep with the API's truth.
            DB::table('fixture_events')->where('fixture_id', $fixture->id)->delete();

            foreach ($item['events'] ?? [] as $event) {
                if (($event['type'] ?? null) === null) {
                    continue;
                }

                DB::table('fixture_events')->insert([
                    'fixture_id' => $fixture->id,
                    'team_id' => $event['team']['id'] ?? null,
                    'player_id' => $event['player']['id'] ?? null,
                    'assist_player_id' => $event['assist']['id'] ?? null,
                    'type' => $event['type'],
                    'detail' => $event['detail'] ?? null,
                    'comments' => $event['comments'] ?? null,
                    'time_elapsed' => $event['time']['elapsed'] ?? null,
                    'time_extra' => $event['time']['extra'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
    /**
     * Flip every pending betslip that holds an odd on this fixture to
     * 'underway', and mirror the transition on each of its pending
     * purchases.
     *
     * Called once per fixture, the moment it enters play. Idempotent by
     * construction: the transition check in applyPayload means we don't
     * reach here on subsequent syncs, and the status='pending' filters
     * make a redundant call a no-op.
     *
     * Runs inside the caller's transaction — if the fixture write rolls
     * back, so do the betslip and purchase flips.
     */
    protected function markBetslipsUnderway(Fixture $fixture): void
    {
        $betslipIds = DB::table('betslip_odd')
            ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
            ->where('odds.fixture_id', $fixture->id)
            ->distinct()
            ->pluck('betslip_odd.betslip_id');

        if ($betslipIds->isEmpty()) {
            return;
        }

        // Narrow to the betslips we will actually transition. A betslip
        // already underway or settled is left alone, and so are its
        // purchases — they should already be past 'pending' via the same
        // transition or via settlement.
        $pendingBetslipIds = Betslip::whereIn('id', $betslipIds)
            ->where('status', 'pending')
            ->pluck('id');

        if ($pendingBetslipIds->isEmpty()) {
            return;
        }

        Betslip::whereIn('id', $pendingBetslipIds)
            ->update(['status' => 'underway']);

        BetslipUserPurchase::whereIn('betslip_id', $pendingBetslipIds)
            ->where('status', 'pending')
            ->update(['status' => 'underway']);
    }

        /**
     * Flip every locked contest that holds a leg on this fixture to
     * 'underway'. Called once per fixture, the moment it enters play.
     *
     * A contest still in 'open' state when a fixture kicks off means the
     * scheduler lagged or the deadline was misconfigured — user-facing
     * reality (the fixture is in play) beats the state label, so we
     * transition anyway and log a warning.
     *
     * Idempotent: guarded by the transition check in applyPayload, and by
     * the status filter, so a redundant call is a no-op.
     *
     * Runs inside the caller's transaction — if the fixture write rolls
     * back, so does the contest flip.
     */
    protected function markContestsUnderway(Fixture $fixture): void
    {
        $contestIds = DB::table('contest_legs')
            ->where('fixture_id', $fixture->id)
            ->distinct()
            ->pluck('contest_id');

        if ($contestIds->isEmpty()) {
            return;
        }

        $flippable = Contest::whereIn('id', $contestIds)
            ->whereIn('status', ['locked', 'open'])
            ->get(['id', 'status']);

        if ($flippable->isEmpty()) {
            return;
        }

        $openIds = $flippable->where('status', 'open')->pluck('id')->all();

        if (! empty($openIds)) {
            Log::warning('Contest entering underway before its lock transition', [
                'fixture_id'  => $fixture->id,
                'contest_ids' => $openIds,
            ]);
        }

        Contest::whereIn('id', $flippable->pluck('id'))
            ->update(['status' => 'underway']);
    }

}