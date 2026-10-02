<?php

namespace App\Notifications;

use App\Models\Contest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContestJoinRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contest $contest,
        public User $requester,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
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
}