<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Market;
use App\Models\Odd;
use App\Services\ApiSportsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUpcomingOddsCommand extends Command
{
    protected $signature = 'odds:sync-upcoming';

    protected $description = 'Refresh odds for NS fixtures kicking off in the next 48 hours.';

    public function handle(ApiSportsClient $client): int
    {
        $sleep = (int) config('services.api_sports.sleep_between_requests', 1);

        $fixtures = Fixture::where('status_short', 'NS')
            ->whereBetween('date', [now(), now()->addHours(48)])
            ->orderBy('date')
            ->get();

        if ($fixtures->isEmpty()) {
            $this->info('No NS fixtures in the next 48h. Nothing to do.');
            return self::SUCCESS;
        }

        $touched = 0;

        foreach ($fixtures as $fixture) {
            try {
                $response = $client->oddsByFixture((int) $fixture->id_on_api);
                $this->upsertOddsForFixture($fixture, $response);
                $touched++;
            } catch (\Throwable $e) {
                Log::error("Failed to sync odds for fixture {$fixture->id}", [
                    'fixture_id'   => $fixture->id,
                    'id_on_api'    => $fixture->id_on_api,
                    'error'        => $e->getMessage(),
                ]);
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->info("Refreshed odds for {$touched} fixture(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $response
     */
    protected function upsertOddsForFixture(Fixture $fixture, array $response): void
    {
        if (empty($response)) {
            return;
        }

        // Single bookmaker model: use the first bookmaker returned. Merging
        // across bookmakers would require a bookmaker column on `odds` to
        // avoid collisions; not in scope yet.
        $bookmakers = $response[0]['bookmakers'] ?? [];
        if (empty($bookmakers)) {
            return;
        }

        $bets = $bookmakers[0]['bets'] ?? [];

        foreach ($bets as $bet) {
            $marketId = $bet['id']   ?? null;
            $name     = $bet['name'] ?? null;
            if (!$marketId || !$name) {
                continue;
            }

            Market::updateOrCreate(
                ['id' => (int) $marketId],
                ['name' => (string) $name],
            );

            foreach ($bet['values'] ?? [] as $entry) {
                $value = $entry['value'] ?? null;
                $price = $entry['odd']   ?? null;
                if ($value === null || $price === null) {
                    continue;
                }

                Odd::updateOrCreate(
                    [
                        'fixture_id' => $fixture->id,
                        'market_id'  => (int) $marketId,
                        'value'      => (string) $value,
                    ],
                    [
                        'odd' => (float) $price,
                        // Status deliberately not overwritten — a resync of
                        // an in-flight fixture must not reset a settled odd.
                    ]
                );
            }
        }
    }
}