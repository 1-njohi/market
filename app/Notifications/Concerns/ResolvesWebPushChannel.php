<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushChannel;

trait ResolvesWebPushChannel
{
    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function withWebPush(array $channels, $notifiable): array
    {
        if ($notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }
}