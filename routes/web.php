<?php

use App\Http\Controllers\BetslipController;
use App\Http\Controllers\BetslipPurchaseController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\BuyerDashboardController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/fixture/{id}', [FixtureController::class, 'index'])->name('fixture');
Route::get('/profile/{user_code}', [ProfileController::class, 'index'])->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);

    Route::prefix('betslip')->group(function () {
        Route::post('/store', [BetslipController::class, 'store'])->name('betslip.store');
        Route::get('/success/{code}', [BetslipController::class, 'success'])->name('betslip.success');
        Route::get('/view/g/{code}', [BetslipController::class, 'show'])->withoutMiddleware(['auth', 'verified'])->name('betslip.show_guest');
        Route::post('/unlock', [BetslipPurchaseController::class, 'purchase'])->name('betslip.purchase');
    });
    Route::prefix('users')->group(function () {
        Route::post('/{user_id}/follow', [FollowController::class, 'follow'])->name('users.follow');
        Route::delete('/{user_id}/unfollow', [FollowController::class, 'unfollow'])->name('users.unfollow');
        Route::post('/{user_id}/toggle-follow', [FollowController::class, 'toggleFollow'])->name('users.toggle-follow');
        Route::get('/{user_id}/followers', [FollowController::class, 'followers'])->name('users.followers');
        Route::get('/{user_id}/following', [FollowController::class, 'following'])->name('users.following');
        Route::get('/feed', [FollowController::class, 'feed'])->name('feed.index');
        Route::put('/{user_id}/follow-notifications', [FollowController::class, 'updateNotification'])->name('users.follow-notifications');

    });
    Route::prefix('seller')->group(function () {
        Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/realtime', [SellerDashboardController::class, 'getRealtimeData'])->name('seller.dashboard.realtime');
        Route::get('/dashboard/performance', [SellerDashboardController::class, 'getPerformanceData'])->name('seller.dashboard.performance');
        Route::get('/dashboard/market-performance', [SellerDashboardController::class, 'getMarketPerformance'])->name('seller.dashboard.market-performance');
        Route::get('/dashboard/activity', [SellerDashboardController::class, 'getRecentActivity'])->name('seller.dashboard.activity');
        Route::get('/dashboard/financial', [SellerDashboardController::class, 'getFinancialSummary'])->name('seller.dashboard.financial');
        Route::get('/dashboard/insights', [SellerDashboardController::class, 'getInsights'])->name('seller.dashboard.insights');
        Route::get('/dashboard/followers', [SellerDashboardController::class, 'getFollowerStats'])->name('seller.dashboard.followers');
    });

    Route::prefix('buyer')->group(function () {
        Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->name('buyer.dashboard');
        Route::get('/dashboard/performance', [BuyerDashboardController::class, 'getPerformanceData'])->name('buyer.dashboard.performance');
        Route::get('/dashboard/activity', [BuyerDashboardController::class, 'getRecentActivity'])->name('buyer.dashboard.activity');
        Route::get('/dashboard/financial', [BuyerDashboardController::class, 'getFinancialSummary'])->name('buyer.dashboard.financial');
        Route::get('/dashboard/wallet', [BuyerDashboardController::class, 'getWalletSummary'])->name('buyer.dashboard.wallet');
        Route::get('/dashboard/insights', [BuyerDashboardController::class, 'getInsights'])->name('buyer.dashboard.insights');
    });

    Route::prefix('/marketplace')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->withoutMiddleware(['auth', 'verified'])->name('marketplace.view');
    });

    // Deposit
    Route::post('/deposit/initiate', [PaystackController::class, 'initiateDeposit'])->name('deposit.initiate');
    Route::get('/deposit/callback', [PaystackController::class, 'callback'])->name('deposit.callback');

    // Withdrawal
    Route::post('/withdrawal/initiate', [PaystackController::class, 'initiateWithdrawal'])->name('withdrawal.initiate');


    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Webhooks (public)
    Route::post('/paystack/webhook', [PaystackController::class, 'webhook'])->name('paystack.webhook');
});

require __DIR__ . '/settings.php';
