<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SellerPromotedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'You can now sell betslips',
            'body'  => 'Your recent picks hit the qualifying threshold. Set a price on a slip to list it on the marketplace.',
            'url'   => '/seller/dashboard',
        ];
    }
}