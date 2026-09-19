<?php

namespace App\Services;

use App\Notifications\SystemAlertNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AlertDispatcher
{
    /**
     * Cache key that holds the last-known health state. Used to detect
     * transitions (ok→fail, fail→ok) and to throttle re-alerts.
     */
    public const STATE_KEY = 'system_health:last_state';

    /**
     * How long to keep the last state before forgetting it entirely.
     * A day is long enough to cover an overnight incident, short enough
     * that a stale state doesn't suppress a genuine new alert days later.
     */
    public const STATE_TTL_HOURS = 24;

    /**
     * Called by the health command every run. Decides whether the result
     * warrants a notification, sends it if so, and updates the stored
     * state for the next run.
     */
    public function dispatchHealthResult(array $health): void
    {
        if (!config('alerts.enabled')) {
            return;
        }

        $previous = Cache::get(self::STATE_KEY);
        $previousOverall = $previous['overall'] ?? 'ok';
        $previousAlertedAt = $previous['alerted_at'] ?? null;

        $currentOverall = $health['overall'];

        $decision = $this->decide($previousOverall, $currentOverall, $previousAlertedAt);

        if ($decision['alert']) {
            $this->send($health, $decision['severity']);
        }

        Cache::put(self::STATE_KEY, [
            'overall' => $currentOverall,
            'alerted_at' => $decision['alert']
                ? now()->toIso8601String()
                : $previousAlertedAt,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(self::STATE_TTL_HOURS));
    }

    /**
     * Called by scheduled jobs when they fail. Uses a per-job cache key
     * so two different jobs failing in the same minute each produce one
     * alert, rather than the second overwriting the first.
     */
    public function dispatchJobFailure(string $jobName, string $error): void
    {
        if (!config('alerts.enabled')) {
            return;
        }

        $key = 'job_failure:' . $jobName;
        $lastAlertedAt = Cache::get($key);

        if ($lastAlertedAt && Carbon::parse($lastAlertedAt)->diffInMinutes(now()) < config('alerts.re_alert_minutes')) {
            Log::warning("Job {$jobName} failed again within cooldown — suppressing alert", [
                'error' => $error,
            ]);
            return;
        }

        Cache::put($key, now()->toIso8601String(), now()->addHours(self::STATE_TTL_HOURS));

        $this->send([
            'overall' => 'fail',
            'checked_at' => now()->toIso8601String(),
            'checks' => [
                [
                    'label' => $jobName,
                    'status' => 'fail',
                    'context' => $error,
                ]
            ],
        ], SystemAlertNotification::SEVERITY_FAIL);
    }

    /**
     * Decide whether to alert, and at what severity. Pure logic — the
     * caller handles the side effects.
     */
    protected function decide(string $previous, string $current, ?string $alertedAt): array
    {
        // State changed → always alert.
        if ($previous !== $current) {
            return [
                'alert' => true,
                'severity' => $this->severityForTransition($current),
            ];
        }

        // Still in fail → re-alert on the cooldown window.
        if ($current === 'fail' && $alertedAt) {
            $minutesSince = Carbon::parse($alertedAt)->diffInMinutes(now());

            if ($minutesSince >= config('alerts.re_alert_minutes')) {
                return ['alert' => true, 'severity' => SystemAlertNotification::SEVERITY_FAIL];
            }
        }

        // Still in warn, or still ok → silence.
        return ['alert' => false, 'severity' => null];
    }

    protected function severityForTransition(string $currentOverall): string
    {
        return match ($currentOverall) {
            'fail' => SystemAlertNotification::SEVERITY_FAIL,
            'warn' => SystemAlertNotification::SEVERITY_WARN,
            'ok' => SystemAlertNotification::SEVERITY_RECOVERED,
            default => SystemAlertNotification::SEVERITY_WARN,
        };
    }

    protected function send(array $health, string $severity): void
    {
        if (!config('alerts.enabled')) {
            return;
        }

        $lines = collect($health['checks'])
            ->reject(fn($c) => $c['status'] === 'ok')
            ->map(fn($c) => sprintf(
                '[%s] %s — %s',
                strtoupper($c['status']),
                $c['label'],
                $c['context'] ?? '',
            ))
            ->values()
            ->all();

        if ($severity === SystemAlertNotification::SEVERITY_RECOVERED) {
            $lines = ['All checks passing again.'];
        }

        if (empty($lines)) {
            Log::warning('AlertDispatcher: send() called with empty lines, skipping');
            return;
        }

        $notification = new SystemAlertNotification($severity, $lines);

        $anyDestinationConfigured = false;

        // ── Chat: Slack + Telegram, one notification to each ──
        // Both fire on every severity. Routing them through a fresh
        // anonymous notifiable keeps the shape uniform across channels.
        if (config('alerts.slack.enabled') && config('alerts.slack.webhook')) {
            Notification::route('slack', config('alerts.slack.webhook'))
                ->notify($notification);
            $anyDestinationConfigured = true;
        }

        if (config('alerts.telegram.enabled') && config('alerts.telegram.chat_id')) {
            Notification::route('telegram', config('alerts.telegram.chat_id'))
                ->notify($notification);
            $anyDestinationConfigured = true;
        }

        // ── Mail: only on fail, and only if we have recipients ──
        if (
            $severity === SystemAlertNotification::SEVERITY_FAIL
            && config('alerts.mail.enabled')
            && !empty(config('alerts.mail.recipients'))
        ) {
            Notification::route('mail', config('alerts.mail.recipients'))
                ->notify($notification);
            $anyDestinationConfigured = true;
        }

        // ── SMS: gated by feature flag, credential presence, and severity ──
        if ($this->shouldSendSms($severity)) {
            foreach (config('alerts.sms.recipients') as $phone) {
                Notification::route('sms', $phone)->notify($notification);
            }
            $anyDestinationConfigured = true;
        }

        if (!$anyDestinationConfigured) {
            Log::warning('AlertDispatcher: no destinations configured — alert dropped', [
                'severity' => $severity,
                'lines' => $lines,
            ]);
        }
    }

    protected function shouldSendSms(string $severity): bool
    {
        if (!config('alerts.sms.enabled')) {
            return false;
        }

        if ($severity !== config('alerts.sms.only_on_severity')) {
            return false;
        }

        if (empty(config('alerts.sms.recipients'))) {
            return false;
        }

        if (!config('alerts.sms.username') || !config('alerts.sms.api_key')) {
            return false;
        }

        return true;
    }
}