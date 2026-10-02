<?php

namespace App\Notifications;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContestEntryStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contest $contest,
        public User $host,
        public string $status,   // 'accepted' | 'rejected'
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
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