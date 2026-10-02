<?php

namespace App\Notifications;

use App\Models\Contest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContestSettledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contest $contest,
        public int $rank,
        public int $correct,
        public float $units,
        public int $totalParticipants,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        [$title, $body] = $this->copy();

        return [
            'title'            => $title,
            'body'             => $body,
            'type'             => 'contest_settled',
            'contest_uuid'     => $this->contest->uuid,
            'contest_name'     => $this->contest->name,
            'rank'             => $this->rank,
            'correct'          => $this->correct,
            'units'            => $this->units,
            'total_participants' => $this->totalParticipants,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copy(): array
    {
        $name    = $this->contest->name;
        $correct = $this->correct;
        $rank    = $this->rank;
        $total   = $this->totalParticipants;
        $units   = number_format($this->units, 2);

        // Determine legs count from the contest — cached relation is fine.
        $legs = $this->contest->legs()->count();
        $denominator = $legs > 0 ? $legs : $correct;

        if ($rank === 1) {
            return [
                '🏆 You won ' . $name,
                "Top of {$total}! You got {$correct}/{$denominator} correct, +{$units}u.",
            ];
        }

        if ($rank === 2) {
            return [
                '🥈 Runner-up in ' . $name,
                "2nd of {$total}. {$correct}/{$denominator} correct, +{$units}u.",
            ];
        }

        if ($rank === 3) {
            return [
                '🥉 Third place in ' . $name,
                "3rd of {$total}. {$correct}/{$denominator} correct, +{$units}u.",
            ];
        }

        return [
            $name . ' settled',
            "You got {$correct}/{$denominator}, ranked {$rank} of {$total}. " . number_format($this->units, 2) . 'u.',
        ];
    }
}