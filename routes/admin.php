<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\BetslipController;
use App\Http\Controllers\Admin\MetricsController;
use App\Http\Controllers\Admin\HealthController;

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
        Route::post('/users/{id}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{id}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('users.unsuspend');
        Route::post('/users/{id}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->name('users.adjust-balance');


        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/status', [ReportController::class, 'updateStatus'])->name('reports.status');
        Route::post('/reports/{report}/force-refund', [ReportController::class, 'forceRefund'])->name('reports.force-refund');

        Route::get('/betslips', [BetslipController::class, 'index'])->name('betslips.index');
        Route::get('/betslips/{betslip}', [BetslipController::class, 'show'])->name('betslips.show');

        Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.index');
        Route::post('/metrics/refresh', [MetricsController::class, 'refresh'])->name('metrics.refresh');

        Route::get('/health', [HealthController::class, 'index'])->name('health.index');
    });