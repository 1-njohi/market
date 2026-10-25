<?php

namespace App\Notifications\Matchday;

use App\Models\Contest;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MatchdaySettledNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public Contest $contest,
        public int $correct,
        public int $total,
        public int $matchdayNumber,
    ) {}

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'title'           => "You scored {$this->correct}/{$this->total} in MD{$this->matchdayNumber}",
            'body'            => "{$this->contest->name} settled.",
            'type'            => 'matchday_settled',
            'contest_uuid'    => $this->contest->uuid,
            'matchday_number' => $this->matchdayNumber,
            'correct'         => $this->correct,
            'total'           => $this->total,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("MD{$this->matchdayNumber} settled")
            ->body("You scored {$this->correct}/{$this->total}.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/contests/{$this->contest->uuid}/results"]);
    }
}