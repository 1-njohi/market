<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SellerDelistedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Your seller account has been delisted',
            'body'  => 'Your win rate stayed below the threshold for the full grace period. Existing listings are hidden. Rebuild your record to qualify again.',
            'url'   => '/seller/dashboard',
        ];
    }
}