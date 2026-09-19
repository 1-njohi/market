<?php

namespace App\Console\Commands;

use App\Services\BusinessMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\AlertDispatcher;
use Throwable;

class WarmBusinessMetrics extends Command
{
    protected $signature = 'metrics:warm';

    protected $description = 'Recompute the business metrics dashboard and refresh its cache';

    public function handle(BusinessMetricsService $metrics, AlertDispatcher $dispatcher, ): int
    {
        $this->info('Warming business metrics…');
        $overallStart = microtime(true);
        $failures = 0;

        foreach (BusinessMetricsService::WINDOWS as $days) {
            $start = microtime(true);

            try {
                $summary = $metrics->refresh($days);
                $elapsed = (int) round((microtime(true) - $start) * 1000);

                $this->line(sprintf(
                    '  ✓ %2dd window — %5dms · unlock %s%% · repeat %s%% · activate %s%%',
                    $days,
                    $elapsed,
                    $summary['unlock_rate']['value'] ?? '–',
                    $summary['buyer_repeat_rate']['value'] ?? '–',
                    $summary['seller_activation']['value'] ?? '–',
                ));

                Log::info('Business metrics warmed', [
                    'window_days' => $days,
                    'duration_ms' => $elapsed,
                    'unlock_rate' => $summary['unlock_rate']['value'] ?? null,
                    'buyer_repeat_rate' => $summary['buyer_repeat_rate']['value'] ?? null,
                    'seller_activation' => $summary['seller_activation']['value'] ?? null,
                    'median_time_to_first_sale' => $summary['median_time_to_first_sale']['value'] ?? null,
                ]);
            } catch (Throwable $e) {
                $failures++;

                $this->error(sprintf('  ✗ %2dd window — %s', $days, $e->getMessage()));

                Log::error('Business metrics warm failed', [
                    'window_days' => $days,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $dispatcher->dispatchJobFailure(
                    "metrics:warm ({$days}d)",
                    $e->getMessage()
                );
            }
        }

        $totalSeconds = round(microtime(true) - $overallStart, 2);

        if ($failures > 0) {
            $this->error("Warm finished with {$failures} failure(s) in {$totalSeconds}s");
            return self::FAILURE;
        }

        $this->info("Warm complete in {$totalSeconds}s");
        return self::SUCCESS;
    }
}