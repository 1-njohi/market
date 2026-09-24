<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralAttributedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $referrer,
        public float $discountPct,
        public float $discountCap,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
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
}