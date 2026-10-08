<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\ApiSportsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLiveFixturesCommand extends Command
{
    protected $signature = 'fixtures:sync-live';

    protected $description = 'Poll status and scores for fixtures currently in play.';

    /** Statuses we care to poll. */
    private const CANDIDATE_STATUSES = ['NS', '1H', 'HT', '2H', 'ET', 'P'];

    /** Statuses after which `home_winner` is meaningful. */
    private const TERMINAL_STATUSES = ['FT', 'CANC', 'ABD', 'AWD', 'WO', 'PST'];

    public function handle(ApiSportsClient $client): int
    {
        $lookback  = (int) config('services.api_sports.live_lookback_hours', 3);
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
                $candidates->pluck('id_on_api')->map(fn ($id) => (int) $id)->all()
            );
        } catch (\Throwable $e) {
            Log::error('Failed to sync live fixtures', ['error' => $e->getMessage()]);
            return self::SUCCESS;
        }

        $byApiId = $candidates->keyBy('id_on_api');
        $updated = 0;

        foreach ($response as $item) {
            $apiId = $item['fixture']['id'] ?? null;
            if ($apiId === null || ! $byApiId->has($apiId)) {
                continue;
            }

            try {
                $this->applyPayload($byApiId->get($apiId), $item);
                $updated++;
            } catch (\Throwable $e) {
                Log::error("Failed to apply live update for fixture {$apiId}", [
                    'id_on_api' => $apiId,
                    'error'     => $e->getMessage(),
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
        $f      = $item['fixture'] ?? [];
        $teams  = $item['teams']   ?? [];
        $goals  = $item['goals']   ?? [];
        $score  = $item['score']   ?? [];
        $status = $f['status']     ?? [];
        $statusShort = $status['short'] ?? null;

        DB::transaction(function () use ($fixture, $f, $teams, $goals, $score, $status, $statusShort, $item) {
            $attributes = [
                'status_long'    => $status['long']    ?? null,
                'status_short'   => $statusShort,
                'status_elapsed' => $status['elapsed'] ?? null,
                'status_extra'   => $status['extra']   ?? null,
                'goals_home'     => $goals['home'] ?? null,
                'goals_away'     => $goals['away'] ?? null,
                'halftime_home'  => $score['halftime']['home']  ?? null,
                'halftime_away'  => $score['halftime']['away']  ?? null,
                'fulltime_home'  => $score['fulltime']['home']  ?? null,
                'fulltime_away'  => $score['fulltime']['away']  ?? null,
                'extratime_home' => $score['extratime']['home'] ?? null,
                'extratime_away' => $score['extratime']['away'] ?? null,
                'penalty_home'   => $score['penalty']['home']   ?? null,
                'penalty_away'   => $score['penalty']['away']   ?? null,
            ];

            // Only write home_winner when the API's status is terminal —
            // during a live match the API returns null and we must not
            // clobber an already-known winner.
            if ($statusShort !== null && in_array($statusShort, self::TERMINAL_STATUSES, true)) {
                $attributes['home_winner'] = (bool) ($teams['home']['winner'] ?? false);
            }

            $fixture->update($attributes);

            // Replace events wholesale — idempotent, keeps local state in
            // lockstep with the API's truth.
            DB::table('fixture_events')->where('fixture_id', $fixture->id)->delete();

            foreach ($item['events'] ?? [] as $event) {
                if (($event['type'] ?? null) === null) {
                    continue;
                }

                DB::table('fixture_events')->insert([
                    'fixture_id'       => $fixture->id,
                    'team_id'          => $event['team']['id']   ?? null,
                    'player_id'        => $event['player']['id'] ?? null,
                    'assist_player_id' => $event['assist']['id'] ?? null,
                    'type'             => $event['type'],
                    'detail'           => $event['detail']           ?? null,
                    'comments'         => $event['comments']         ?? null,
                    'time_elapsed'     => $event['time']['elapsed']  ?? null,
                    'time_extra'       => $event['time']['extra']    ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        });
    }
}