<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SellerWarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Your seller status is at risk',
            'body'  => 'Your recent pick win rate has dropped below the threshold. Improve your picks within the grace period to keep selling.',
            'url'   => '/seller/dashboard',
        ];
    }
}