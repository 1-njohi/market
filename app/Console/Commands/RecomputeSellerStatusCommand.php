<?php

namespace App\Console\Commands;

use App\Models\Betslip;
use App\Models\User;
use App\Notifications\SellerDelistedNotification;
use App\Notifications\SellerPromotedNotification;
use App\Notifications\SellerWarnedNotification;
use App\Services\SellerEligibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecomputeSellerStatusCommand extends Command
{
    protected $signature = 'users:recompute-seller-status';

    protected $description = 'Recompute rolling pick stats and apply seller status transitions.';

    public function handle(SellerEligibilityService $service): int
    {
        $userIds = $this->candidateUserIds();

        $processed   = 0;
        $transitions = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $processed++;
            if ($this->processUser($user, $service)) {
                $transitions++;
            }
        }

        $this->info("Processed {$processed} user(s); {$transitions} state transition(s).");

        return self::SUCCESS;
    }

    /**
     * Users who need attention:
     *   - anyone with a settled pick in the rolling window (numbers can change)
     *   - anyone with a non-zero rolling count on record (numbers may need
     *     zeroing when their last pick leaves the window)
     */
    private function candidateUserIds(): Collection
    {
        $windowStart = now()->subDays((int) config('marketplace.rolling_window_days'));

        $withPicks = DB::table('betslips')
            ->join('betslip_odd', 'betslip_odd.betslip_id', '=', 'betslips.id')
            ->whereIn('betslip_odd.status', ['won', 'lost', 'void'])
            ->where('betslip_odd.updated_at', '>=', $windowStart)
            ->distinct()
            ->pluck('betslips.user_id');

        $stale = User::where('picks_settled_rolling', '>', 0)->pluck('id');

        return $withPicks->merge($stale)->unique()->values();
    }

    /**
     * @return bool  true if a state transition occurred
     */
    private function processUser(User $user, SellerEligibilityService $service): bool
    {
        $count = $service->settledPicksCount($user);
        $rate  = $service->pickWinRate($user);

        return DB::transaction(function () use ($user, $count, $rate) {
            // Numbers are always rewritten, regardless of what happens to
            // status. This keeps a sellable user's dashboard numbers
            // current even when their rate stays inside the hysteresis band.
            $user->update([
                'picks_settled_rolling' => $count,
                'pick_win_rate_rolling' => $rate,
            ]);

            $minSettled = (int) config('marketplace.min_settled_picks');

            // Below the sample-size floor: leave status alone. A previously
            // active seller keeps their status on a thin sample.
            if ($count < $minSettled) {
                return false;
            }

            // $rate is non-null here — the service returns null below min,
            // and we've already bailed on that.
            $minToSell = (float) config('marketplace.min_win_rate_to_sell');
            $minToStay = (float) config('marketplace.min_win_rate_to_stay');

            if ($rate >= $minToSell) {
                if ($user->sellable_status === 'sellable') {
                    return false;
                }

                return $this->transitionToSellable($user);
            }

            if ($rate >= $minToStay) {
                // Hysteresis band — no state change.
                return false;
            }

            // Below the stay threshold.
            if ($user->sellable_status === 'sellable') {
                return $this->transitionToWarned($user);
            }

            if ($user->sellable_status === 'warned') {
                $graceDays = (int) config('marketplace.grace_period_days');
                $warnedAt  = $user->sellable_warned_at;

                if ($warnedAt && $warnedAt->lt(now()->subDays($graceDays))) {
                    return $this->transitionToDelisted($user);
                }
            }

            return false;
        });
    }

    private function transitionToSellable(User $user): bool
    {
        $previous = $user->sellable_status;

        $user->update([
            'sellable_status'            => 'sellable',
            'sellable_status_changed_at' => now(),
            'sellable_warned_at'         => null,
        ]);

        Log::info('Seller promoted', [
            'user_id' => $user->id,
            'from'    => $previous,
            'to'      => 'sellable',
        ]);

        $user->notify(new SellerPromotedNotification());

        return true;
    }

    private function transitionToWarned(User $user): bool
    {
        $user->update([
            'sellable_status'            => 'warned',
            'sellable_status_changed_at' => now(),
            'sellable_warned_at'         => now(),
        ]);

        Log::info('Seller warned', [
            'user_id' => $user->id,
            'from'    => 'sellable',
            'to'      => 'warned',
        ]);

        $user->notify(new SellerWarnedNotification());

        return true;
    }

    private function transitionToDelisted(User $user): bool
    {
        $user->update([
            'sellable_status'            => 'delisted',
            'sellable_status_changed_at' => now(),
        ]);

        // Hide every live sellable betslip this user has. Settled / voided
        // slips are untouched — they've already finished. Non-sellable
        // slips were never in the marketplace, so they're untouched too.
        Betslip::where('user_id', $user->id)
            ->where('is_sellable', true)
            ->where('is_delisted', false)
            ->whereIn('status', ['pending', 'underway'])
            ->update(['is_delisted' => true]);

        Log::info('Seller delisted', [
            'user_id' => $user->id,
            'from'    => 'warned',
            'to'      => 'delisted',
        ]);

        $user->notify(new SellerDelistedNotification());

        return true;
    }
}