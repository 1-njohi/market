<?php

namespace App\Notifications;

use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DepositConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public Deposit $deposit)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
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
}