<?php

namespace App\Console\Commands;

use app\Services\AlertDispatcher;
use app\Services\SystemHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckSystemHealth extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Run system integrity checks and report any drift';

    public function handle(SystemHealthService $health, AlertDispatcher $dispatcher): int
    {
        try {
            $result = $health->all();
        } catch (Throwable $e) {
            // If the checks themselves crash (DB unreachable, etc.) we
            // synthesize a fail result so the dispatcher still alerts.
            $result = [
                'overall' => SystemHealthService::STATUS_FAIL,
                'checked_at' => now()->toIso8601String(),
                'checks' => [
                    [
                        'key' => 'health_check_exception',
                        'label' => 'Health check crashed',
                        'status' => SystemHealthService::STATUS_FAIL,
                        'value' => null,
                        'context' => $e->getMessage(),
                        'details' => [],
                    ]
                ],
            ];

            Log::error('health:check crashed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // ── Console output ──
        $this->line('');
        $this->line(sprintf(
            '  System health: <fg=%s>%s</> — checked %s',
            $this->colorFor($result['overall']),
            strtoupper($result['overall']),
            now()->toDateTimeString(),
        ));
        $this->line('');

        foreach ($result['checks'] as $check) {
            $icon = match ($check['status']) {
                SystemHealthService::STATUS_OK => '✓',
                SystemHealthService::STATUS_WARN => '!',
                SystemHealthService::STATUS_FAIL => '✗',
            };

            $this->line(sprintf(
                '  <fg=%s>%s</> %-32s %s',
                $this->colorFor($check['status']),
                $icon,
                $check['label'],
                $check['context'],
            ));
        }

        $this->line('');

        // ── Alert dispatch ──
        // Runs on every invocation. No-ops when the state hasn't changed
        // and no cooldown has elapsed.
        $dispatcher->dispatchHealthResult($result);

        $problems = collect($result['checks'])
            ->reject(fn($c) => $c['status'] === SystemHealthService::STATUS_OK);

        if ($problems->isEmpty()) {
            Log::info('System health: all checks passing');
            return self::SUCCESS;
        }

        foreach ($problems as $check) {
            Log::warning('System health issue', [
                'check' => $check['key'],
                'status' => $check['status'],
                'value' => $check['value'],
                'context' => $check['context'],
            ]);
        }

        return $result['overall'] === SystemHealthService::STATUS_FAIL
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function colorFor(string $status): string
    {
        return match ($status) {
            SystemHealthService::STATUS_OK => 'green',
            SystemHealthService::STATUS_WARN => 'yellow',
            SystemHealthService::STATUS_FAIL => 'red',
            default => 'gray',
        };
    }
}