<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification
{
    use Queueable;

    public const SEVERITY_WARN = 'warn';
    public const SEVERITY_FAIL = 'fail';
    public const SEVERITY_RECOVERED = 'recovered';

    public function __construct(
        public string $severity,
        public array $lines,
        public ?string $footer = null,
    ) {
    }

    /**
     * Channels are resolved per-severity. Slack and Telegram fire on
     * everything; SMS only on the severity configured in alerts.sms;
     * mail only on fail.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (config('alerts.slack.enabled')) {
            $channels[] = 'slack';
        }
        if (config('alerts.telegram.enabled')) {
            $channels[] = TelegramChannel::class;
        }
        if (config('alerts.sms.enabled')
            && $this->severity === config('alerts.sms.only_on_severity')
            && $notifiable->routeNotificationFor('sms')) {
            $channels[] = SmsChannel::class;
        }
        if ($this->severity === self::SEVERITY_FAIL
            && config('alerts.mail.enabled')
            && $notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toTelegram(object $notifiable): array
    {
        // Telegram's HTML mode accepts a small subset of tags and
        // requires escaping of &, <, >. Keep it simple.
        $emoji = match ($this->severity) {
            self::SEVERITY_FAIL => '🚨',
            self::SEVERITY_WARN => '⚠️',
            self::SEVERITY_RECOVERED => '✅',
            default => 'ℹ️',
        };

        $heading = htmlspecialchars($this->headline(), ENT_QUOTES, 'UTF-8');

        $body = collect($this->lines)
            ->map(fn ($line) => '• ' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8'))
            ->implode("\n");

        $footer = $this->footer
            ?? 'Checked ' . now()->toDateTimeString();

        $text = "{$emoji} <b>{$heading}</b>\n\n{$body}\n\n<i>{$footer}</i>";

        return [
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
    }

    public function toSms(object $notifiable): string
    {
        // SMS is a hard 160-char budget in one segment. Keep the fail
        // line the responder needs and drop the rest.
        $firstLine = $this->lines[0] ?? 'System health issue';
        $prefix = match ($this->severity) {
            self::SEVERITY_FAIL => 'ALERT',
            self::SEVERITY_WARN => 'WARN',
            self::SEVERITY_RECOVERED => 'RECOVERED',
            default => 'UPDATE',
        };

        $message = "{$prefix}: {$firstLine}";

        return mb_substr($message, 0, 160);
    }

    // ── Unchanged from the previous version ──────────────────────

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject())
            ->greeting($this->headline())
            ->line('Betslip Pirates system health detected the following:');

        foreach ($this->lines as $line) {
            $message->line($line);
        }

        return $message
            ->action('Open dashboard', config('alerts.dashboard_url'))
            ->line($this->footer ?? 'This alert was sent by the scheduled health check.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->attachment(function ($attachment) {
                $attachment
                    ->title($this->headline())
                    ->content(implode("\n", $this->lines))
                    ->color($this->slackColor())
                    ->footer('Checked at ' . now()->toDateTimeString());

                if ($this->footer) {
                    $attachment->footer($this->footer . ' · ' . now()->toDateTimeString());
                }
            });
    }

    public function toArray(object $notifiable): array
    {
        return [
            'severity' => $this->severity,
            'lines' => $this->lines,
        ];
    }

    protected function headline(): string
    {
        return match ($this->severity) {
            self::SEVERITY_FAIL => 'System health: FAILING',
            self::SEVERITY_WARN => 'System health: degraded',
            self::SEVERITY_RECOVERED => 'System health: recovered',
            default => 'System health update',
        };
    }

    protected function subject(): string
    {
        return match ($this->severity) {
            self::SEVERITY_FAIL => '[Betslip Pirates] CRITICAL — system health failing',
            self::SEVERITY_WARN => '[Betslip Pirates] warning — system health degraded',
            self::SEVERITY_RECOVERED => '[Betslip Pirates] resolved — system health recovered',
            default => '[Betslip Pirates] system health update',
        };
    }

    protected function slackColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_FAIL => '#ef4444',
            self::SEVERITY_WARN => '#f59e0b',
            self::SEVERITY_RECOVERED => '#10b981',
            default => '#64748b',
        };
    }
}