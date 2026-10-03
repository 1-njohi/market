<?php

namespace App\Notifications;

use App\Models\Betslip;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class BetslipVoidedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(public Betslip $betslip)
    {
    }

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
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

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $isSeller = $notifiable->id === $this->betslip->user_id;

        return (new WebPushMessage)
            ->title($isSeller ? 'Betslip voided' : 'Betslip voided — refund issued')
            ->body($isSeller
                ? "Betslip #{$this->betslip->code} was voided."
                : "Betslip #{$this->betslip->code} was voided. Refund issued.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => "/betslip/view/g/{$this->betslip->code}"]);
    }
}