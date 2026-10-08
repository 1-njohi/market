<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Team;
use App\Services\ApiSportsClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUpcomingFixturesCommand extends Command
{
    protected $signature = 'fixtures:sync-upcoming';

    protected $description = 'Pull fixtures for the next N days from API-Sports.';

    public function handle(ApiSportsClient $client): int
    {
        $days  = (int) config('services.api_sports.upcoming_days', 14);
        $sleep = (int) config('services.api_sports.sleep_between_requests', 1);

        $today     = CarbonImmutable::today('UTC');
        $created   = 0;
        $processed = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $today->addDays($i)->toDateString();

            try {
                $fixtures = $client->fixturesByDate($date);
                foreach ($fixtures as $item) {
                    if (empty($item['fixture']['id'])) {
                        continue;
                    }
                    if ($this->upsertFixture($item)) {
                        $created++;
                    }
                    $processed++;
                }
            } catch (\Throwable $e) {
                Log::error("Failed to sync fixtures for {$date}", [
                    'date'  => $date,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($sleep > 0 && $i < $days - 1) {
                sleep($sleep);
            }
        }

        $updated = $processed - $created;
        $this->info("Processed {$processed} fixture(s) across {$days} day(s): {$created} created, {$updated} updated.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return bool  true if the fixture was created, false if updated
     */
    protected function upsertFixture(array $item): bool
    {
        $f          = $item['fixture'] ?? [];
        $teams      = $item['teams']   ?? [];
        $leagueData = $item['league']  ?? [];
        $goals      = $item['goals']   ?? [];
        $score      = $item['score']   ?? [];
        $status     = $f['status']     ?? [];

        if (empty($f['id'])) {
            return false;
        }

        $homeTeamApiId = isset($teams['home']['id']) ? (int) $teams['home']['id'] : null;
        $awayTeamApiId = isset($teams['away']['id']) ? (int) $teams['away']['id'] : null;

        if ($homeTeamApiId !== null) {
            $this->upsertTeam($homeTeamApiId, $teams['home']);
        }
        if ($awayTeamApiId !== null) {
            $this->upsertTeam($awayTeamApiId, $teams['away']);
        }

        $leagueLocalId = $this->upsertLeague($leagueData);

        $fixture = Fixture::updateOrCreate(
            ['id_on_api' => $f['id']],
            [
                'referee'        => $f['referee']        ?? null,
                'timezone'       => $f['timezone']       ?? null,
                'date'           => $f['date']           ?? null,
                'timestamp'      => $f['timestamp']      ?? null,
                'period_first'   => $f['periods']['first']  ?? null,
                'period_second'  => $f['periods']['second'] ?? null,
                'home_team_id'   => $homeTeamApiId,
                'away_team_id'   => $awayTeamApiId,
                'league_id'      => $leagueLocalId,
                'status_long'    => $status['long']      ?? null,
                'status_short'   => $status['short']     ?? null,
                'status_elapsed' => $status['elapsed']   ?? null,
                'status_extra'   => $status['extra']     ?? null,
                'goals_home'     => $goals['home']       ?? null,
                'goals_away'     => $goals['away']       ?? null,
                'halftime_home'  => $score['halftime']['home']  ?? null,
                'halftime_away'  => $score['halftime']['away']  ?? null,
                'fulltime_home'  => $score['fulltime']['home']  ?? null,
                'fulltime_away'  => $score['fulltime']['away']  ?? null,
                'extratime_home' => $score['extratime']['home'] ?? null,
                'extratime_away' => $score['extratime']['away'] ?? null,
                'penalty_home'   => $score['penalty']['home']   ?? null,
                'penalty_away'   => $score['penalty']['away']   ?? null,
                'home_winner'    => (bool) ($teams['home']['winner'] ?? false),
            ]
        );

        return $fixture->wasRecentlyCreated;
    }

    /**
     * @param  array<string, mixed>  $team
     */
    protected function upsertTeam(int $apiId, array $team): void
    {
        Team::updateOrCreate(
            ['id_on_api' => $apiId],
            [
                'name' => $team['name'] ?? 'Unknown',
                'logo' => $team['logo'] ?? null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $league
     * @return int|null  local leagues.id, or null if payload is missing
     */
    protected function upsertLeague(array $league): ?int
    {
        if (empty($league['id'])) {
            return null;
        }

        $row = League::updateOrCreate(
            ['id_on_api' => (int) $league['id']],
            [
                'name'       => $league['name']      ?? 'Unknown',
                'country'    => $league['country']   ?? null,
                'logo'       => $league['logo']      ?? null,
                'flag'       => $league['flag']      ?? null,
                'season'     => $league['season']    ?? null,
                'round'      => $league['round']     ?? null,
                'standings'  => (bool) ($league['standings'] ?? false),
                // country_id left null — resolved only when a countries sync exists.
            ]
        );

        return $row->id;
    }
}