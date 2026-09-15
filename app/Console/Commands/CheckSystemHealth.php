<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSystemHealth extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Run system integrity checks and report any drift';

    public function handle(SystemHealthService $health): int
    {
        $result = $health->all();

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

        $problems = collect($result['checks'])
            ->reject(fn ($c) => $c['status'] === SystemHealthService::STATUS_OK);

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