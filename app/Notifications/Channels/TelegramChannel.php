<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!config('alerts.telegram.enabled')) {
            return;
        }

        $token = config('alerts.telegram.bot_token');
        $chatId = $notifiable->routeNotificationFor('telegram')
            ?? config('alerts.telegram.chat_id');

        if (!$token || !$chatId) {
            Log::warning('TelegramChannel: token or chat_id missing');
            return;
        }

        $payload = $notification->toTelegram($notifiable);

        try {
            Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $payload['text'],
                    'parse_mode' => $payload['parse_mode'] ?? 'HTML',
                    'disable_web_page_preview' => true,
                ]);
        } catch (Throwable $e) {
            // A failing notifier must never take down the command that
            // invoked it. Log and move on.
            Log::error('TelegramChannel delivery failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}