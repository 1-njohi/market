<?php

namespace App\Notifications;

use App\Models\Betslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BetslipLostNotification extends Notification
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
                ? 'Betslip settled — no payout'
                : 'Betslip settled — refund issued',
            'body' => $isSeller
                ? "Betslip #{$this->betslip->code} lost. No payout was issued."
                : "Betslip #{$this->betslip->code} lost. Your purchase has been refunded to your wallet.",
            'betslip_id' => $this->betslip->id,
            'betslip_code' => $this->betslip->code,
            'type' => 'betslip_lost',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Betslip #{$this->betslip->code} – refund issued")
            ->greeting("Hi {$notifiable->name},")
            ->line("Betslip #{$this->betslip->code} lost. Your purchase has been refunded to your wallet.")
            ->action('View Wallet', url('/wallet'))
            ->line('Thanks for using Betslip Pirates!');
    }
}