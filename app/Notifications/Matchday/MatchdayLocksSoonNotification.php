<?php

namespace App\Notifications\Matchday;

use App\Models\Contest;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MatchdayLocksSoonNotification extends Notification
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

        return [
            'title'           => "MD{$md} locks in 24h",
            'body'            => "You still have picks to submit for {$this->contest->name}.",
            'type'            => 'matchday_locks_soon',
            'contest_uuid'    => $this->contest->uuid,
            'matchday_number' => $md,
            'locks_at'        => $this->contest->locks_at?->toIso8601String(),
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $md = $this->contest->matchday_number;

        return (new WebPushMessage)
            ->title("MD{$md} locks in 24h")
            ->body('Finish your picks before kickoff.')
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/contests/{$this->contest->uuid}/picks"]);
    }
}