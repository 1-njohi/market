<?php

namespace App\Notifications;

use App\Models\Betslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BetslipWonNotification extends Notification
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
                ? '🎉 Your betslip won!'
                : '🎉 Betslip you bought won!',
            'body' => $isSeller
                ? "Betslip #{$this->betslip->code} won. Your payout has been released to your wallet."
                : "Betslip #{$this->betslip->code} won. You kept the tip — no refund was issued.",
            'betslip_id' => $this->betslip->id,
            'betslip_code' => $this->betslip->code,
            'type' => 'betslip_won',
        ];
    }
    public function toMail($notifiable): MailMessage
    {
        $role = $notifiable->id === $this->betslip->user_id ? 'seller' : 'buyer';

        return (new MailMessage)
            ->subject("🎉 Betslip #{$this->betslip->code} won!")
            ->greeting("Hi {$notifiable->name},")
            ->line($role === 'seller'
                ? "Your betslip #{$this->betslip->code} won. Your payout has been released to your wallet."
                : "The betslip #{$this->betslip->code} you purchased won.")
            ->action('View Wallet', url('/wallet'))
            ->line('Thanks for using Betslip Pirates!');
    }
}