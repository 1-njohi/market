<?php

namespace App\Console\Commands;

use App\Models\Contest;
use Illuminate\Console\Command;

class LockDueContestsCommand extends Command
{
    protected $signature = 'contests:lock-due';

    protected $description = 'Transition open contests past their entry deadline to locked.';

    public function handle(): int
    {
        $locked = Contest::where('status', 'open')
            ->whereNotNull('entry_deadline_at')
            ->where('entry_deadline_at', '<=', now())
            ->update(['status' => 'locked']);

        $this->info("Locked {$locked} contest(s).");

        return self::SUCCESS;
    }
}