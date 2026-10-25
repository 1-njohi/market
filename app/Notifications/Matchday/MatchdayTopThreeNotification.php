<?php

namespace App\Notifications\Matchday;

use App\Models\Contest;
use App\Models\SubLeague;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MatchdayTopThreeNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public Contest $contest,
        public SubLeague $subLeague,
        public int $rank,
        public int $points,
    ) {}

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'title'           => "Top {$this->rank} in {$this->subLeague->name}",
            'body'            => "{$this->points} pts in MD{$this->contest->matchday_number}. Keep it up.",
            'type'            => 'matchday_top_three',
            'contest_uuid'    => $this->contest->uuid,
            'sub_league_id'   => $this->subLeague->id,
            'matchday_number' => $this->contest->matchday_number,
            'rank'            => $this->rank,
            'points'          => $this->points,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title("Top {$this->rank} in {$this->subLeague->name}")
            ->body("{$this->points} pts in MD{$this->contest->matchday_number}.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/sub-leagues/{$this->subLeague->id}/leaderboard"]);
    }
}