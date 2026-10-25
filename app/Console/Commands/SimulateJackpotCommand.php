<?php

namespace App\Console\Commands;

use App\Models\Contest;
use App\Models\ContestEntry;
use App\Models\ContestLeg;
use App\Models\ContestPick;
use App\Models\Odd;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateJackpotCommand extends Command
{
    protected $signature = 'jackpot:simulate 
                            {--users=100 : Number of users to enter}
                            {--uuid= : Specific jackpot UUID (default: latest open jackpot)}
                            {--clear-picks : Delete existing entries and picks before seeding}';

    protected $description = 'Enters the first N non-admin users into a jackpot and generates random picks for all legs.';

    public function handle(): int
    {
        $limit = (int) $this->option('users');
        $uuid = $this->option('uuid');

        // 1. Find the jackpot
        $contest = $uuid
            ? Contest::where('type', 'jackpot')->where('uuid', $uuid)->first()
            : Contest::where('type', 'jackpot')
                ->whereIn('status', ['open', 'locked'])
                ->orderByDesc('id')
                ->first();

        if (!$contest) {
            $this->error('No jackpot found. Create one first or pass --uuid.');
            return self::FAILURE;
        }

        $this->info("Jackpot: {$contest->name} ({$contest->uuid})");
        $this->info("Status: {$contest->status} · Locks: {$contest->locks_at}");

        // 2. Load legs and verify they have odds
        $legs = ContestLeg::where('contest_id', $contest->id)->get();

        if ($legs->isEmpty()) {
            $this->error('Jackpot has no legs. Nothing to pick.');
            return self::FAILURE;
        }

        $legsWithoutOdds = $legs->filter(function ($leg) {
            return Odd::where('fixture_id', $leg->fixture_id)
                ->where('market_id', $leg->market_id)
                ->doesntExist();
        });

        if ($legsWithoutOdds->isNotEmpty()) {
            $this->error("{$legsWithoutOdds->count()} leg(s) have no odds. Cannot generate picks for those legs.");
            $this->line("Leg IDs without odds: " . $legsWithoutOdds->pluck('id')->implode(', '));
            return self::FAILURE;
        }

        // 3. Optional: clear existing data
        if ($this->option('clear-picks')) {
            $existingEntries = ContestEntry::where('contest_id', $contest->id)->pluck('id');
            ContestPick::whereIn('contest_entry_id', $existingEntries)->delete();
            ContestEntry::where('contest_id', $contest->id)->delete();
            $this->warn("Cleared existing entries and picks for this jackpot.");
        }

        // 4. Find the first N non-admin users
        $users = User::where('is_admin', false)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($users->isEmpty()) {
            $this->error('No non-admin users found in the database.');
            return self::FAILURE;
        }

        $this->info("Processing {$users->count()} non-admin users...");

        $entriesCreated = 0;
        $picksCreated = 0;

        // 5. Enter users and generate picks
        // The withoutMatchdayLock bypass handles the case where the jackpot
        // has already locked — needed because jackpot picks auto-set
        // revealed_at, and we want to seed data regardless of the lock state.
        ContestPick::withoutMatchdayLock(function () use ($users, $contest, $legs, &$entriesCreated, &$picksCreated) {
            foreach ($users as $user) {
                $entry = ContestEntry::firstOrCreate(
                    [
                        'contest_id' => $contest->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'status' => 'accepted',
                        'joined_at' => now(),
                    ],
                );

                if ($entry->wasRecentlyCreated) {
                    $entriesCreated++;
                }

                foreach ($legs as $leg) {
                    // Skip if this user already picked this leg (idempotent).
                    $exists = ContestPick::where('contest_entry_id', $entry->id)
                        ->where('contest_leg_id', $leg->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $odds = Odd::where('fixture_id', $leg->fixture_id)
                        ->where('market_id', $leg->market_id)
                        ->get();

                    if ($odds->isEmpty()) {
                        continue;
                    }

                    $selectedOdd = $odds->random();

                    ContestPick::create([
                        'contest_entry_id' => $entry->id,
                        'contest_leg_id' => $leg->id,
                        'selection' => $selectedOdd->value,
                        'odds_at_pick' => $selectedOdd->odd,
                        'status' => 'pending',
                    ]);
                    $picksCreated++;
                }
            }
        });

        $this->newLine();
        $this->info("Simulation complete.");
        $this->line("Entries created: {$entriesCreated}");
        $this->line("Picks created:   {$picksCreated}");
        $this->line("Total entrants:  " . ContestEntry::where('contest_id', $contest->id)->count());
        $this->line("Total picks:     " . ContestPick::whereIn(
            'contest_entry_id',
            ContestEntry::where('contest_id', $contest->id)->pluck('id')
        )->count());

        return self::SUCCESS;
    }
}