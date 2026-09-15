<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARN = 'warn';
    public const STATUS_FAIL = 'fail';

    /** Tolerance for money comparisons — floating point noise only. */
    private const MONEY_TOLERANCE = 0.01;

    /** Withdrawal queue thresholds, in hours. */
    private const WITHDRAWAL_WARN_HOURS = 4;
    private const WITHDRAWAL_FAIL_HOURS = 24;

    /** Failed job thresholds. */
    private const FAILED_JOBS_WARN = 1;
    private const FAILED_JOBS_FAIL = 10;

    /** Unsettled fixture thresholds. */
    private const UNSETTLED_WARN = 5;
    private const UNSETTLED_FAIL = 25;

    /**
     * Run every check and return the composite payload.
     */
    public function all(): array
    {
        $checks = [
            $this->walletLedgerIntegrity(),
            $this->escrowIntegrity(),
            $this->failedQueueJobs(),
            $this->unsettledFinishedFixtures(),
            $this->withdrawalQueueAge(),
            $this->orphanedData(),
        ];

        // Overall status = worst of the individual statuses.
        $overall = self::STATUS_OK;
        foreach ($checks as $check) {
            if ($check['status'] === self::STATUS_FAIL) {
                $overall = self::STATUS_FAIL;
                break;
            }
            if ($check['status'] === self::STATUS_WARN) {
                $overall = self::STATUS_WARN;
            }
        }

        return [
            'overall' => $overall,
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /**
     * Only the checks that aren't 'ok'. Useful for the command's
     * alerting path — no need to log every clean run.
     */
    public function problems(): array
    {
        return collect($this->all()['checks'])
            ->reject(fn ($c) => $c['status'] === self::STATUS_OK)
            ->values()
            ->all();
    }

    // ─────────────────────────────────────────────────────────────
    //  1. Wallet ledger integrity
    //  Invariant: SUM(transactions.amount) == balance + pending_balance
    //  per user.
    //
    //  Holds because every wallet mutation in WalletService writes a
    //  matching transaction row, including the pending<->available
    //  moves (which net to zero).
    // ─────────────────────────────────────────────────────────────

    public function walletLedgerIntegrity(): array
    {
        $drifted = DB::table('wallets as w')
            ->select('w.user_id', 'w.balance', 'w.pending_balance')
            ->whereRaw('ABS(
                (w.balance + w.pending_balance) - COALESCE((
                    SELECT SUM(t.amount)
                    FROM transactions t
                    WHERE t.user_id = w.user_id
                ), 0)
            ) > ?', [self::MONEY_TOLERANCE])
            ->get();

        $count = $drifted->count();

        return [
            'key' => 'wallet_ledger_integrity',
            'label' => 'Wallet Ledger Integrity',
            'status' => $count > 0 ? self::STATUS_FAIL : self::STATUS_OK,
            'value' => $count,
            'context' => $count === 0
                ? 'Every wallet matches its transaction history'
                : "{$count} wallet(s) drift from their ledger",
            'details' => $drifted->take(10)->map(fn ($row) => [
                'user_id' => $row->user_id,
                'wallet_total' => round($row->balance + $row->pending_balance, 2),
            ])->all(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  2. Escrow integrity
    //  Invariant: SUM(wallets.pending_balance) equals the total of
    //  all purchases currently in 'pending' status.
    //
    //  Every purchase adds to the seller's pending balance; every
    //  settlement (win or loss) removes it atomically alongside the
    //  status change.
    // ─────────────────────────────────────────────────────────────

    public function escrowIntegrity(): array
    {
        $pendingHeld = (float) DB::table('wallets')->sum('pending_balance');
        $pendingOwed = (float) DB::table('betslip_user_purchases')
            ->where('status', 'pending')
            ->sum('purchase_price');

        $drift = round($pendingHeld - $pendingOwed, 2);
        $ok = abs($drift) < self::MONEY_TOLERANCE;

        return [
            'key' => 'escrow_integrity',
            'label' => 'Escrow Integrity',
            'status' => $ok ? self::STATUS_OK : self::STATUS_FAIL,
            'value' => $drift,
            'context' => $ok
                ? 'Held funds match open purchases'
                : sprintf(
                    'Held KES %s vs. owed KES %s (drift %s)',
                    number_format($pendingHeld, 2),
                    number_format($pendingOwed, 2),
                    number_format($drift, 2),
                ),
            'details' => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  3. Failed queue jobs
    //  The failed_jobs table catches anything that fell over and
    //  wasn't retried successfully. Any nonzero is worth looking at.
    // ─────────────────────────────────────────────────────────────

    public function failedQueueJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'key' => 'failed_queue_jobs',
                'label' => 'Failed Queue Jobs',
                'status' => self::STATUS_WARN,
                'value' => null,
                'context' => 'failed_jobs table not present — skipping',
                'details' => [],
            ];
        }

        $count = DB::table('failed_jobs')->count();

        $status = match (true) {
            $count >= self::FAILED_JOBS_FAIL => self::STATUS_FAIL,
            $count >= self::FAILED_JOBS_WARN => self::STATUS_WARN,
            default => self::STATUS_OK,
        };

        return [
            'key' => 'failed_queue_jobs',
            'label' => 'Failed Queue Jobs',
            'status' => $status,
            'value' => $count,
            'context' => $count === 0
                ? 'Queue is clean'
                : "{$count} job(s) sitting in failed_jobs",
            'details' => DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(10)
                ->get(['id', 'queue', 'failed_at'])
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'queue' => $row->queue,
                    'failed_at' => $row->failed_at,
                ])->all(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  4. Unsettled finished fixtures
    //  Fixtures that reached full-time but still have odds with
    //  status='pending'. If this grows, MarketSettlementService
    //  isn't running or is failing silently.
    // ─────────────────────────────────────────────────────────────

    public function unsettledFinishedFixtures(): array
    {
        $count = DB::table('fixtures')
            ->where('status_short', 'FT')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('odds')
                    ->whereColumn('odds.fixture_id', 'fixtures.id')
                    ->where('odds.status', 'pending');
            })
            ->count();

        $status = match (true) {
            $count >= self::UNSETTLED_FAIL => self::STATUS_FAIL,
            $count >= self::UNSETTLED_WARN => self::STATUS_WARN,
            default => self::STATUS_OK,
        };

        return [
            'key' => 'unsettled_finished_fixtures',
            'label' => 'Unsettled Finished Fixtures',
            'status' => $status,
            'value' => $count,
            'context' => $count === 0
                ? 'Every finished fixture is settled'
                : "{$count} finished fixture(s) with pending odds",
            'details' => DB::table('fixtures')
                ->select('fixtures.id', 'fixtures.date', 'fixtures.status_short')
                ->where('status_short', 'FT')
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('odds')
                        ->whereColumn('odds.fixture_id', 'fixtures.id')
                        ->where('odds.status', 'pending');
                })
                ->orderBy('date', 'asc')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'date' => $row->date,
                    'status' => $row->status_short,
                ])->all(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  5. Withdrawal queue age
    //  How old is the oldest pending/processing withdrawal? A
    //  growing age usually means the M-Pesa pipeline is stuck.
    // ─────────────────────────────────────────────────────────────

    public function withdrawalQueueAge(): array
    {
        $oldest = DB::table('withdrawals')
            ->whereIn('status', ['pending', 'processing'])
            ->min('created_at');

        if (! $oldest) {
            return [
                'key' => 'withdrawal_queue_age',
                'label' => 'Withdrawal Queue Age',
                'status' => self::STATUS_OK,
                'value' => null,
                'context' => 'No withdrawals waiting',
                'details' => [],
            ];
        }

        $ageHours = round(
            Carbon::parse($oldest)->diffInMinutes(now()) / 60,
            1
        );

        $status = match (true) {
            $ageHours >= self::WITHDRAWAL_FAIL_HOURS => self::STATUS_FAIL,
            $ageHours >= self::WITHDRAWAL_WARN_HOURS => self::STATUS_WARN,
            default => self::STATUS_OK,
        };

        $count = DB::table('withdrawals')
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        return [
            'key' => 'withdrawal_queue_age',
            'label' => 'Withdrawal Queue Age',
            'status' => $status,
            'value' => $ageHours,
            'context' => sprintf(
                'Oldest of %d open request(s) is %.1fh old',
                $count,
                $ageHours,
            ),
            'details' => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  6. Orphaned data
    //  Relationships that should be intact but aren't. This is the
    //  class of bug that caused fixtures to render as 'Unknown'.
    // ─────────────────────────────────────────────────────────────

    public function orphanedData(): array
    {
        $fixturesMissingHome = DB::table('fixtures as f')
            ->whereNotNull('f.home_team_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('teams as t')
                    ->whereColumn('t.id_on_api', 'f.home_team_id');
            })
            ->count();

        $fixturesMissingAway = DB::table('fixtures as f')
            ->whereNotNull('f.away_team_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('teams as t')
                    ->whereColumn('t.id_on_api', 'f.away_team_id');
            })
            ->count();

        $oddsMissingFixture = DB::table('odds as o')
            ->whereNotNull('o.fixture_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('fixtures as f')
                    ->whereColumn('f.id', 'o.fixture_id');
            })
            ->count();

        $total = $fixturesMissingHome + $fixturesMissingAway + $oddsMissingFixture;

        return [
            'key' => 'orphaned_data',
            'label' => 'Orphaned Data',
            'status' => $total > 0 ? self::STATUS_FAIL : self::STATUS_OK,
            'value' => $total,
            'context' => $total === 0
                ? 'All relationships resolve'
                : "{$total} orphaned row(s) across fixtures and odds",
            'details' => [
                ['label' => 'Fixtures missing home team', 'count' => $fixturesMissingHome],
                ['label' => 'Fixtures missing away team', 'count' => $fixturesMissingAway],
                ['label' => 'Odds missing fixture', 'count' => $oddsMissingFixture],
            ],
        ];
    }
}