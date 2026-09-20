<?php

namespace App\Services;

use App\Models\User;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Cache;
use App\Services\LeaderboardService;

class BetslipSettlementService
{
    public function __construct(
        protected WalletService $walletService,
        protected PlatformFeeService $platformFeeService,
        protected PlatformAccount $platformAccount,
    ) {
    }

    /**
     * Evaluate and settle a betslip if all its odds are resolved.
     * Idempotent – safe to call multiple times.
     */
    public function settle(Betslip $betslip): void
    {
        DB::transaction(function () use ($betslip) {
            // Capture ID before reassigning (in case lookup fails)
            $betslipId = $betslip->id;

            // Lock the betslip to prevent concurrent settlements
            $betslip = Betslip::whereKey($betslipId)->lockForUpdate()->first();

            if (!$betslip) {
                Log::warning("Betslip {$betslipId} no longer exists.");
                return;
            }

            // Idempotency guard
            // Idempotency guard
            if (in_array($betslip->status, ['settled', 'voided'], true)) {
                Log::info("Betslip {$betslip->code} already settled/voided. Skipping.");
                return;
            }

            // ─────────────────────────────────────────────────────────
            // SYNC: copy each odd's global status into its betslip_odd pivot row
            // (covers the case where MarketSettlementService only updated `odds.status`
            // but not the per-betslip pivot entries)
            // ─────────────────────────────────────────────────────────
            $syncableRows = DB::table('betslip_odd')
                ->join('odds', 'odds.id', '=', 'betslip_odd.odd_id')
                ->where('betslip_odd.betslip_id', $betslip->id)
                ->where('betslip_odd.status', 'pending')
                ->whereIn('odds.status', ['won', 'lost', 'void'])
                ->select('betslip_odd.id as pivot_id', 'odds.status as odd_status')
                ->get();

            if ($syncableRows->isNotEmpty()) {
                $byStatus = $syncableRows
                    ->groupBy('odd_status')
                    ->map(fn($group) => $group->pluck('pivot_id')->all());

                foreach ($byStatus as $status => $pivotIds) {
                    DB::table('betslip_odd')
                        ->whereIn('id', $pivotIds)
                        ->update(['status' => $status]);
                }

                Log::info("Betslip {$betslip->code}: synced {$syncableRows->count()} pivot rows from odds.status.");
            }

            // ─────────────────────────────────────────────────────────
            // Count resolved / pending legs
            // ─────────────────────────────────────────────────────────
            $counts = DB::table('betslip_odd')
                ->where('betslip_id', $betslip->id)
                ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'won'     THEN 1 ELSE 0 END) as won_count,
                SUM(CASE WHEN status = 'lost'    THEN 1 ELSE 0 END) as lost_count,
                SUM(CASE WHEN status = 'void'    THEN 1 ELSE 0 END) as void_count,
                COUNT(*) as total_count
            ")
                ->first();

            $pending = (int) ($counts->pending_count ?? 0);
            $won = (int) ($counts->won_count ?? 0);
            $lost = (int) ($counts->lost_count ?? 0);
            $void = (int) ($counts->void_count ?? 0);
            $total = (int) ($counts->total_count ?? 0);

            // ─────────────────────────────────────────────────────────
            // Still waiting for other fixtures to finish
            // ─────────────────────────────────────────────────────────
            if ($pending > 0) {
                $betslip->update(['status' => 'underway']);
                Log::info("Betslip {$betslip->code} underway: {$pending}/{$total} legs pending.");
                return;
            }

            // ─────────────────────────────────────────────────────────
            // Fully voided — no wins, no losses, at least one void
            // ─────────────────────────────────────────────────────────
            if ($won === 0 && $lost === 0 && $void > 0) {
                $betslip->update([
                    'status' => 'voided',
                    'is_winner' => false,
                    'remaining' => 0,
                ]);

                Log::info("Betslip {$betslip->code} voided: all {$void} legs void.");

                $this->settlePurchases($betslip, false, 'voided');   // <— add the third arg
                return;
            }
            // ─────────────────────────────────────────────────────────
            // Normal win/loss resolution
            //   - Winner: at least one won, no lost (void legs ignored)
            //   - Loser:  any lost leg
            // ─────────────────────────────────────────────────────────
            $isWinner = ($lost === 0) && ($won > 0);

            $betslip->update([
                'status' => 'settled',
                'is_winner' => $isWinner,
                'remaining' => 0,
            ]);

            // Distribute payouts/refunds per purchase
            $this->settlePurchases($betslip, $isWinner);
            // Bust the cached leaderboard so the homepage reflects the new results
            LeaderboardService::forget(10);
            Log::info("Betslip {$betslip->code} settled as " . ($isWinner ? 'WIN' : 'LOSS') . " ({$won}W/{$lost}L/{$void}V).");
        });
    }

    /**
     * Move money for every purchase tied to this betslip.
     */
    protected function settlePurchases(
        Betslip $betslip,
        bool $isWinner,
        string $pivotStatus = 'refunded'
    ): void {
        $purchases = BetslipUserPurchase::where('betslip_id', $betslip->id)
            ->lockForUpdate()
            ->get();

        if ($purchases->isEmpty()) {
            return;
        }

        foreach ($purchases as $purchase) {
            if ($isWinner) {
                $this->payoutSeller($purchase, $betslip);
            } else {
                $this->refundBuyer($purchase, $betslip, $pivotStatus);
            }
        }

        // Cache invalidation — unchanged from the previous slice.
        $buyerIds = $purchases->pluck('buyer_id')->unique();
        $sellerIds = $purchases->pluck('seller_id')->unique();

        foreach ($buyerIds as $id) {
            Cache::forget(DashboardController::cacheKey(User::find($id)));
        }
        foreach ($sellerIds as $id) {
            Cache::forget(DashboardController::cacheKey(User::find($id)));
        }
    }
    /**
     * Winning purchase: release seller's pending balance.
     */
    protected function payoutSeller(BetslipUserPurchase $purchase, Betslip $betslip): void
    {
        $gross = (float) $purchase->purchase_price;

        $split = $this->platformFeeService->split($purchase->seller, $gross);
        $fee = (float) $split['fee'];

        $platform = $this->platformAccount->user();

        $txs = $this->walletService->settleEscrowToSeller(
            buyer: $purchase->buyer,
            seller: $purchase->seller,
            platform: $platform,
            gross: $gross,
            fee: $fee,
            context: "Betslip #{$betslip->code}",
        );

        // Tag every money row with the originating purchase, so the ledger
        // is walkable in both directions: pivot → transactions, and any
        // single transaction → its pivot.
        foreach ($txs as $tx) {
            $tx->update([
                'transactionable_type' => BetslipUserPurchase::class,
                'transactionable_id' => $purchase->id,
            ]);
        }

        $purchase->update([
            'status' => 'won',
            'settled_at' => now(),
        ]);
    }

    /**
     * Losing/void purchase: return the buyer's escrowed funds and mark the
     * pivot with the appropriate status ('refunded' for a loss, 'voided' for
     * an all-void betslip).
     */
    protected function refundBuyer(
        BetslipUserPurchase $purchase,
        Betslip $betslip,
        string $pivotStatus = 'refunded'
    ): void {
        $txs = $this->walletService->refundEscrow(
            user: $purchase->buyer,
            amount: (float) $purchase->purchase_price,
            context: "Betslip #{$betslip->code}",
        );

        foreach ($txs as $tx) {
            $tx->update([
                'transactionable_type' => BetslipUserPurchase::class,
                'transactionable_id' => $purchase->id,
            ]);
        }

        $purchase->update([
            'status' => $pivotStatus,
            'settled_at' => now(),
        ]);
    }
    protected function notifyOutcome(bool $won, Betslip $betslip, ?BetslipUserPurchase $purchase = null): void
    {
        $notification = $won
            ? new \App\Notifications\BetslipWonNotification($betslip)
            : new \App\Notifications\BetslipLostNotification($betslip);

        \Log::info("Notification fired");

        if ($purchase) {
            // Notify buyer
            $purchase->buyer->notify($notification);

            // Notify seller (won: payout, lost: payout reversed)
            $purchase->seller->notify($notification);
        } else {
            $betslip->seller->notify($notification);
        }
    }
}