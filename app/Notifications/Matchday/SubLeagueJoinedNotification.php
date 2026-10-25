<?php

namespace App\Notifications\Matchday;

use App\Models\SubLeague;
use App\Models\User;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class SubLeagueJoinedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public SubLeague $subLeague,
        public User $joiner,
    ) {}

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'title'         => "{$this->joiner->name} joined {$this->subLeague->name}",
            'body'          => "Your sub-league now has {$this->subLeague->members()->count()} members.",
            'type'          => 'sub_league_joined',
            'sub_league_id' => $this->subLeague->id,
            'joiner_id'     => $this->joiner->id,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("{$this->joiner->name} joined {$this->subLeague->name}")
            ->body('Your sub-league grew.')
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/sub-leagues/{$this->subLeague->id}"]);
    }
}