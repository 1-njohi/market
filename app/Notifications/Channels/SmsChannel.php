<?php

namespace App\Notifications\Channels;

use App\Services\Sms\AfricasTalkingClient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsChannel
{
    public function __construct(
        protected AfricasTalkingClient $client,
    ) {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (!config('alerts.sms.enabled')) {
            return;
        }

        $recipient = $notifiable->routeNotificationFor('sms');
        $message = $notification->toSms($notifiable);

        if (!$recipient || !$message) {
            return;
        }

        try {
            $this->client->send([$recipient], $message);
        } catch (Throwable $e) {
            Log::error('SmsChannel delivery failed', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }
}