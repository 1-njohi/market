<?php

namespace App\Notifications\Matchday;

use App\Models\Contest;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MatchdayCreatedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public Contest $contest,
    ) {}

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        $md = $this->contest->matchday_number;
        $code = $this->contest->league_code;

        return [
            'title'           => "MD{$md} {$code}: picks open",
            'body'            => "Picks are open for {$this->contest->name}.",
            'type'            => 'matchday_created',
            'contest_uuid'    => $this->contest->uuid,
            'matchday_number' => $md,
            'league_code'     => $code,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $md = $this->contest->matchday_number;

        return (new WebPushMessage)
            ->title("MD{$md}: picks open")
            ->body('Your sub-league is in. Make your picks.')
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/contests/{$this->contest->uuid}/picks"]);
    }
}