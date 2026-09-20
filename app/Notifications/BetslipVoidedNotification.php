<?php

namespace App\Notifications;

use App\Models\Betslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BetslipVoidedNotification extends Notification
{
    use Queueable;

    public function __construct(public Betslip $betslip)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $isSeller = $notifiable->id === $this->betslip->user_id;

        return [
            'title' => $isSeller
                ? 'Betslip voided'
                : 'Betslip voided — refund issued',
            'body' => $isSeller
                ? "Betslip #{$this->betslip->code} was voided. No sales were paid out."
                : "Betslip #{$this->betslip->code} was voided. Your purchase has been refunded.",
            'betslip_id' => $this->betslip->id,
            'betslip_code' => $this->betslip->code,
            'type' => 'betslip_voided',
        ];
    }
}