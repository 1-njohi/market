<?php

namespace App\Notifications;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use App\Notifications\Concerns\ResolvesWebPushChannel;

class ContestJoinRequestedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public Contest $contest,
        public User $requester,
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray($notifiable): array
    {
        return [
            'title'          => '🙋 New contest request',
            'body'           => "{$this->requester->name} wants to join {$this->contest->name}.",
            'type'           => 'contest_join_requested',
            'contest_uuid'   => $this->contest->uuid,
            'contest_id'     => $this->contest->id,
            'contest_name'   => $this->contest->name,
            'requester_id'   => $this->requester->id,
            'requester_name' => $this->requester->name,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🙋 New contest request')
            ->body("{$this->requester->name} wants to join {$this->contest->name}.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data([
                'url' => "/contests/{$this->contest->id}/manage",
            ]);
    }
}