<?php

namespace App\Notifications;

use App\Models\Betslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WatcherSettlementNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $outcome  'won' | 'refunded' | 'voided'
     */
    public function __construct(
        public Betslip $betslip,
        public string $outcome,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        [$title, $body] = $this->copy();

        return [
            'title' => $title,
            'body' => $body,
            'outcome' => $this->outcome,
            'betslip_id' => $this->betslip->id,
            'betslip_code' => $this->betslip->code,
            'type' => 'watch_settled',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copy(): array
    {
        $code = $this->betslip->code;

        return match ($this->outcome) {
            'won' => [
                '🏆 Watched slip won',
                "Betslip #{$code} settled as a win. The seller's picks landed. Open the slip to see what they picked.",
            ],
            'refunded' => [
                '💸 Watched slip lost',
                "Betslip #{$code} settled as a loss. The seller's picks missed. Open the slip to see what they picked.",
            ],
            'voided' => [
                '↩️ Watched slip voided',
                "Betslip #{$code} was voided. No result was recorded. Open the slip to see the picks.",
            ],
            default => [
                'Watched slip settled',
                "Betslip #{$code} has settled.",
            ],
        };
    }
}