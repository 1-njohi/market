<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\ResolvesWebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class ReferralAttributedNotification extends Notification
{
    use Queueable, ResolvesWebPushChannel;

    public function __construct(
        public User $referrer,
        public float $discountPct,
        public float $discountCap,
    ) {
    }

    public function via($notifiable): array
    {
        return $this->withWebPush(['database'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        $pct = (int) round($this->discountPct * 100);
        $cap = number_format($this->discountCap, 0);

        return [
            'title' => '🎁 You were referred',
            'body' => "Welcome! {$this->referrer->name} referred you. Your first purchase is {$pct}% off (capped at KES {$cap}).",
            'referrer_id' => $this->referrer->id,
            'referrer_name' => $this->referrer->name,
            'type' => 'referral_attributed',
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $pct = (int) round($this->discountPct * 100);
        $cap = number_format($this->discountCap, 0);

        return (new WebPushMessage)
            ->title('🎁 You were referred')
            ->body("Welcome! Your first purchase is {$pct}% off (capped at KES {$cap}).")
            ->icon('/img/logo-192.png')
            ->badge('/img/badge-72.png')
            ->data(['url' => '/dashboard']);
    }
}