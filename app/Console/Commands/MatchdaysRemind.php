<?php

namespace App\Console\Commands;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Notifications\Matchday\MatchdayLocksSoonNotification;
use Illuminate\Console\Command;

class MatchdaysRemind extends Command
{
    protected $signature = 'matchdays:remind';

    protected $description = 'Notify entrants of matchday contests locking within 24h who have incomplete picks.';

    public function handle(): int
    {
        $contests = Contest::where('is_matchday', true)
            ->where('status', 'open')
            ->whereBetween('locks_at', [now(), now()->addHours(24)])
            ->get();

        $sent = 0;

        foreach ($contests as $contest) {
            $legCount = $contest->legs()->count();

            if ($legCount === 0) {
                continue;
            }

            $entries = ContestEntry::where('contest_id', $contest->id)
                ->where('status', 'accepted')
                ->withCount('picks')
                ->get()
                ->filter(fn (ContestEntry $e) => $e->picks_count < $legCount);

            foreach ($entries as $entry) {
                $entry->user?->notify(new MatchdayLocksSoonNotification($contest));
                $sent++;
            }
        }

        $this->info("Dispatched {$sent} matchday lock reminders.");

        return self::SUCCESS;
    }
}