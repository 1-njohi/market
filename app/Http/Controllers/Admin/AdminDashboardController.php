<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Betslip;
use App\Models\BetslipUserPurchase;
use App\Models\Report;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users_total' => User::count(),
                'users_new_today' => User::whereDate('created_at', $today)->count(),
                'active_last_24h' => User::where('updated_at', '>=', now()->subDay())->count(),
                'betslips_today' => Betslip::whereDate('created_at', $today)->count(),
                'betslips_sold_today' => BetslipUserPurchase::whereDate('created_at', $today)->count(),
                'deposits_today_count' => DB::table('transactions')
                    ->where('type', 'deposit')
                    ->whereDate('created_at', $today)
                    ->count(),
                'deposits_today_amount' => (float) DB::table('transactions')
                    ->where('type', 'deposit')
                    ->whereDate('created_at', $today)
                    ->sum('amount'),
                'withdrawals_today_count' => Withdrawal::whereDate('created_at', $today)->count(),
                'withdrawals_today_amount' => (float) Withdrawal::whereDate('created_at', $today)->sum('amount'),
                'fees_today_amount' => (float) DB::table('transactions')
                    ->where('type', 'fee')
                    ->whereDate('created_at', $today)
                    ->sum('amount'),
                'pending_withdrawals' => Withdrawal::whereIn('status', ['pending', 'processing'])->count(),
                'open_reports' => Report::whereIn('status', ['open', 'investigating'])->count(),
            ],
        ]);
    }
}