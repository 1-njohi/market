<?php

namespace App\Notifications;

use App\Models\Contest;
use App\Models\User;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ContestEntryStatusNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public Contest $contest,
        public User $host,
        public string $status,   // 'accepted' | 'rejected'
    ) {}

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        [$title, $body] = $this->copy();

        return [
            'title'        => $title,
            'body'         => $body,
            'type'         => 'contest_entry_status',
            'status'       => $this->status,
            'contest_uuid' => $this->contest->uuid,
            'contest_id'   => $this->contest->id,
            'contest_name' => $this->contest->name,
            'host_name'    => $this->host->name,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        [$title, $body] = $this->copy();

        $url = $this->status === 'accepted'
            ? "/contests/{$this->contest->uuid}/picks"
            : '/contests/mine';

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => $url]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copy(): array
    {
        if ($this->status === 'accepted') {
            return [
                '✅ You\'re in — ' . $this->contest->name,
                "Make your picks before the deadline.",
            ];
        }

        return [
            'Your contest request was declined',
            "{$this->host->name} declined your request to join {$this->contest->name}.",
        ];
    }
}