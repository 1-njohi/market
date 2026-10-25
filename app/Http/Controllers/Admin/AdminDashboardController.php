<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Report;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Core User Stats
        $usersTotal = User::count();
        $usersNewToday = User::whereDate('created_at', $today)->count();
        $activeLast24h = User::where('updated_at', '>=', now()->subDay())->count();

        // 2. Wallet & Escrow Balances
        $wallets = Wallet::selectRaw('SUM(balance) as total_available, SUM(escrow_balance) as total_escrow')->first();

        $totalAvailable = (float) ($wallets->total_available ?? 0);
        $totalEscrow = (float) ($wallets->total_escrow ?? 0);

        // 3. Platform Treasury (Lifetime)
        // Deposits (money coming in from outside)
        $totalDeposits = (float) DB::table('transactions')->where('type', 'deposit')->sum('amount');

        // Withdrawals (money going out to outside)
        $totalWithdrawals = (float) Withdrawal::where('status', 'approved')->sum('amount');

        // Platform Fees (money kept by the house)
        $totalFees = (float) DB::table('transactions')->where('type', 'fee')->where('user_id', 1)->sum('amount');

        // 4. Today's Movements
        $depositsToday = (float) DB::table('transactions')->where('type', 'deposit')->whereDate('created_at', $today)->sum('amount');
        $depositsTodayCount = DB::table('transactions')->where('type', 'deposit')->whereDate('created_at', $today)->count();

        $withdrawalsToday = (float) Withdrawal::whereDate('created_at', $today)->sum('amount');
        $withdrawalsTodayCount = Withdrawal::whereDate('created_at', $today)->count();

        $feesToday = (float) DB::table('transactions')->where('type', 'fee')->where('user_id', 1)->whereDate('created_at', $today)->sum('amount');

        // 5. Betslip Economics
        $betslipsCreatedToday = Betslip::whereDate('created_at', $today)->count();
        $betslipsSoldToday = BetslipUserPurchase::whereDate('created_at', $today)->count();

        // Total Value of all purchases ever
        $totalSalesValue = (float) BetslipUserPurchase::sum('purchase_price');

        // Volume today (value of purchases)
        $volumeToday = (float) BetslipUserPurchase::whereDate('created_at', $today)->sum('purchase_price');

        // Expected payout liability (if every active slip wins)
        $openBetslips = Betslip::where('status', 'pending')->where('remaining', '>', 0)->get();
        $totalPotentialLiability = $openBetslips->sum(function ($slip) {
            return ($slip->price * $slip->total_odds) * ($slip->purchases_count ?? 1); // Rough estimate
        });

        // 6. Queues / Alerts
        $pendingWithdrawals = Withdrawal::whereIn('status', ['pending', 'processing'])->count();
        $pendingWithdrawalsValue = (float) Withdrawal::whereIn('status', ['pending', 'processing'])->sum('amount');
        $openReports = Report::whereIn('status', ['open', 'investigating'])->count();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => [
                    'total' => $usersTotal,
                    'new_today' => $usersNewToday,
                    'active_24h' => $activeLast24h,
                ],
                'wallets' => [
                    'available' => $totalAvailable,
                    'escrow' => $totalEscrow,
                    'total' => $totalAvailable + $totalEscrow,
                ],
                'treasury' => [
                    'deposits_lifetime' => $totalDeposits,
                    'withdrawals_lifetime' => $totalWithdrawals,
                    'fees_lifetime' => $totalFees,
                ],
                'today' => [
                    'deposits_amount' => $depositsToday,
                    'deposits_count' => $depositsTodayCount,
                    'withdrawals_amount' => $withdrawalsToday,
                    'withdrawals_count' => $withdrawalsTodayCount,
                    'fees_amount' => $feesToday,
                    'volume_amount' => $volumeToday,
                    'betslips_created' => $betslipsCreatedToday,
                    'betslips_sold' => $betslipsSoldToday,
                ],
                'betslips' => [
                    'total_sales_value' => $totalSalesValue,
                    'potential_liability' => $totalPotentialLiability,
                ],
                'queues' => [
                    'pending_withdrawals_count' => $pendingWithdrawals,
                    'pending_withdrawals_value' => $pendingWithdrawalsValue,
                    'open_reports_count' => $openReports,
                ]
            ],
        ]);
    }
}