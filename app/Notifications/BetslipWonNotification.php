<?php

namespace App\Notifications;

use App\Models\Betslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BetslipWonNotification extends Notification
{
    use Queueable;

    public function __construct(public Betslip $betslip) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => '🎉 Your betslip won!',
            'body' => "Betslip #{$this->betslip->code} settled as a win.",
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