<?php

namespace App\Notifications;

use App\Models\Betslip;
use App\Models\User;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ReferralRewardNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public User $referee,
        public Betslip $betslip,
        public float $amount,
    ) {
    }

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        $amount = number_format($this->amount, 2);
        $code = $this->betslip->code;

        return [
            'title' => '💰 Referral reward earned',
            'body' => "You earned KES {$amount} from {$this->referee->name}'s winning betslip #{$code}.",
            'amount' => $this->amount,
            'referee_id' => $this->referee->id,
            'referee_name' => $this->referee->name,
            'betslip_id' => $this->betslip->id,
            'betslip_code' => $code,
            'type' => 'referral_reward',
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $amount = number_format($this->amount, 2);
        $code = $this->betslip->code;

        return (new WebPushMessage)
            ->title('💰 Referral reward earned')
            ->body("You earned KES {$amount} from {$this->referee->name}'s winning betslip #{$code}.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => '/refer']);
    }
}