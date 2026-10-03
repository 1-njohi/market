<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ReferralSignupNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public User $referee,
        public float $rewardPct,
    ) {
    }

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        $pct = (int) round($this->rewardPct * 100);

        return [
            'title' => '🎉 Someone joined with your code',
            'body' => "{$this->referee->name} just signed up using your referral code. You'll earn {$pct}% of the listed price every time they make a winning purchase.",
            'referee_id' => $this->referee->id,
            'referee_name' => $this->referee->name,
            'type' => 'referral_signup',
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $pct = (int) round($this->rewardPct * 100);

        return (new WebPushMessage)
            ->title('🎉 Someone joined with your code')
            ->body("{$this->referee->name} signed up with your code. Earn {$pct}% on their wins.")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => '/refer']);
    }
}