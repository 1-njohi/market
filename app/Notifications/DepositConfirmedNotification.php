<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class DepositConfirmedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(public Deposit $deposit)
    {
    }

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        $amount = number_format((float) $this->deposit->amount, 2);

        return [
            'title' => '💰 Deposit confirmed',
            'body' => "KES {$amount} added to your wallet.",
            'amount' => (float) $this->deposit->amount,
            'reference' => $this->deposit->reference,
            'type' => 'deposit',
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $amount = number_format((float) $this->deposit->amount, 2);

        return (new WebPushMessage)
            ->title('💰 Deposit confirmed')
            ->body("KES {$amount} added to your wallet.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => '/dashboard']);
    }
}