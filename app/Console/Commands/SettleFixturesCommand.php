<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Services\MarketSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SettleFixturesCommand extends Command
{
    protected $signature = 'fixtures:settle';

    protected $description = 'Settle finished and voided fixtures from local data (no API call).';

    public function handle(MarketSettlementService $service): int
    {
        $terminalStatuses = array_merge(
            [MarketSettlementService::FINISHED_STATUS],
            MarketSettlementService::VOID_STATUSES,
        );

        $count = 0;

        Fixture::whereIn('status_short', $terminalStatuses)
            ->where('settled', false)
            ->orderBy('date')
            ->chunkById(50, function ($fixtures) use ($service, &$count) {
                foreach ($fixtures as $fixture) {
                    try {
                        $service->settleFixture($fixture);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::error("Settlement failed for fixture {$fixture->id}", [
                            'fixture_id' => $fixture->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Settled {$count} fixture(s).");

        return self::SUCCESS;
    }
}