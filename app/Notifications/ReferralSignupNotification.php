<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralSignupNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $referee,
        public float $rewardPct,
    ) {
    }
    public function via($notifiable): array
    {
        return ['database'];
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
}