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

// ── Dev / maintenance ──
Route::prefix('dev')->group(function () {
    Route::get('/otheer', function () {
        $jackpot = \App\Models\Contest::where('type', 'jackpot')->where('status', 'settled')->latest()->first();

        if (!$jackpot) {
            return "No settled jackpots found.";
        }

        // 1. Get the crowd majority for each leg
        $crowdMajorities = [];
        $legs = $jackpot->legs;

        foreach ($legs as $leg) {
            $majority = \App\Models\ContestPick::where('contest_leg_id', $leg->id)
                ->select('selection', \DB::raw('COUNT(*) as c'))
                ->groupBy('selection')
                ->orderByDesc('c')
                ->first();

            $crowdMajorities[$leg->id] = [
                'selection' => $majority ? $majority->selection : null,
                'winning' => is_array($leg->result_selections) ? $leg->result_selections : [$leg->result_selections]
            ];
        }

        // 2. Analyze every entry
        $results = [];

        $entries = \App\Models\ContestEntry::where('contest_id', $jackpot->id)
            ->where('status', 'accepted')
            ->with(['user:id,name,email', 'picks'])
            ->get();

        foreach ($entries as $entry) {
            $contrarianAttempts = 0;
            $contrarianWins = 0;
            $score = 0;

            foreach ($entry->picks as $pick) {
                $majority = $crowdMajorities[$pick->contest_leg_id] ?? null;
                if (!$majority || !$majority['selection'])
                    continue;

                $isWin = in_array($pick->selection, $majority['winning'], true);
                if ($isWin)
                    $score++;

                $isContrarian = $pick->selection !== $majority['selection'];
                if ($isContrarian) {
                    $contrarianAttempts++;
                    if ($isWin) {
                        $contrarianWins++;
                    }
                }
            }

            // We want users who actually tried to be contrarian, but either crushed it or failed miserably
            if ($contrarianAttempts > 0) {
                $accuracy = round(($contrarianWins / $contrarianAttempts) * 100);

                $results[] = [
                    'id' => $entry->user_id,
                    'name' => $entry->user->name,
                    'email' => $entry->user->email,
                    'score' => $score,
                    'contrarian_attempts' => $contrarianAttempts,
                    'contrarian_wins' => $contrarianWins,
                    'accuracy' => $accuracy,
                ];
            }
        }

        // 3. Sort by highest accuracy, then by most attempts
        usort($results, function ($a, $b) {
            if ($a['accuracy'] === $b['accuracy']) {
                return $b['contrarian_attempts'] <=> $a['contrarian_attempts'];
            }
            return $b['accuracy'] <=> $a['accuracy'];
        });

        // Show the top 5 "genius" contrarians
        dump("Top Contrarians (Geniuses):");
        dump(array_slice($results, 0, 5));

        // Show the top 5 "foolish" contrarians (lowest accuracy, but tried the most)
        usort($results, function ($a, $b) {
            if ($a['accuracy'] === $b['accuracy']) {
                return $b['contrarian_attempts'] <=> $a['contrarian_attempts'];
            }
            return $a['accuracy'] <=> $b['accuracy'];
        });

        dump("Bottom Contrarians (Fools):");
        dump(array_slice($results, 0, 5));

        // Show someone who beat the crowd
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        dump("Highest Scores Overall:");
        dump(array_slice($results, 0, 3));
    });
    Route::get('/push-test/{token}', function (Request $request, string $token) {
        $expected = env('DEV_PUSH_TEST_SECRET');
        abort_unless($expected && hash_equals($expected, $token), 404);

        $email = 'denis.mwangi24@students.dkut.ac.ke';
        $user = \App\Models\User::where('email', $email)->first();
        abort_unless($user, 404, "No user with email {$email}.");

        $subCount = $user->pushSubscriptions()->count();
        if ($subCount === 0) {
            return response()->json([
                'error' => 'User has no push subscriptions. Enable notifications in the browser first.',
                'email' => $email,
            ], 422);
        }

        // ── Build supporting models ──────────────────────────────
        $seller = $user;
        $other = \App\Models\User::factory()->create([
            'name' => 'Push Test Other',
            'code' => 'PUSHTEST01',
        ]);

        $betslip = \App\Models\Betslip::create([
            'user_id' => $seller->id,
            'code' => 'PUSH-TEST-CDE',
            'price' => 100,
            'total_odds' => 2.50,
            'status' => 'pending',
            'remaining' => 3,
            'is_winner' => false,
        ]);

        $contest = \App\Models\Contest::create([
            'host_id' => $user->id,
            'name' => 'Push Test Contest',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay()->addHour(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $deposit = \App\Models\Deposit::create([
            'user_id' => $seller->id,
            'amount' => 500,
            'reference' => 'PUSH-TEST-DEP',
            'status' => 'confirmed',
        ]);

        // ── Fire every notification ──────────────────────────────
        $sent = [];

        $fire = function (string $label, $notification) use ($user, &$sent) {
            try {
                $user->notify($notification);
                $sent[] = ['ok' => true, 'label' => $label];
            } catch (\Throwable $e) {
                $sent[] = [
                    'ok' => false,
                    'label' => $label,
                    'error' => get_class($e) . ': ' . $e->getMessage(),
                ];
            }
        };

        $fire('contest_join_requested', new \App\Notifications\ContestJoinRequestedNotification($contest, $other));
        $fire('betslip_won', new \App\Notifications\BetslipWonNotification($betslip));
        $fire('betslip_lost', new \App\Notifications\BetslipLostNotification($betslip));
        $fire('betslip_voided', new \App\Notifications\BetslipVoidedNotification($betslip));
        $fire('watcher_settlement_won', new \App\Notifications\WatcherSettlementNotification($betslip, 'won'));
        $fire('watcher_settlement_refunded', new \App\Notifications\WatcherSettlementNotification($betslip, 'refunded'));
        $fire('contest_entry_accepted', new \App\Notifications\ContestEntryStatusNotification($contest, $other, 'accepted'));
        $fire('contest_entry_rejected', new \App\Notifications\ContestEntryStatusNotification($contest, $other, 'rejected'));
        $fire('contest_settled', new \App\Notifications\ContestSettledNotification($contest, 1, 5, 10.0, 20));
        $fire('referral_signup', new \App\Notifications\ReferralSignupNotification($other, 0.10));
        $fire('referral_attributed', new \App\Notifications\ReferralAttributedNotification($other, 0.05, 500.0));
        $fire('referral_reward', new \App\Notifications\ReferralRewardNotification($other, $betslip, 25.0));
        $fire('deposit_confirmed', new \App\Notifications\DepositConfirmedNotification($deposit));

        return response()->json([
            'recipient' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
            'subscriptions' => $subCount,
            'sent_count' => count(array_filter($sent, fn($s) => $s['ok'])),
            'failed_count' => count(array_filter($sent, fn($s) => !$s['ok'])),
            'notifications' => $sent,
            'cleanup_ids' => [
                'betslip_id' => $betslip->id,
                'contest_id' => $contest->id,
                'deposit_id' => $deposit->id,
                'other_user_id' => $other->id,
            ],
        ]);
    })->name('dev.push-test');

    // ── TEMP: webpush end-to-end test. Remove after verifying. ──
    Route::get('/push-test', function (Request $request) {
        $email = 'denis.mwangi24@students.dkut.ac.ke';
        $user = \App\Models\User::where('email', $email)->first();
        abort_unless($user, 404, "No user with email {$email}.");

        $subCount = $user->pushSubscriptions()->count();
        if ($subCount === 0) {
            return response()->json([
                'error' => 'User has no push subscriptions. Enable notifications in the browser first.',
                'email' => $email,
            ], 422);
        }

        // Create a throwaway second user to act as the "sender" in the
        // notification payloads. No factory — production builds don't
        // include fakerphp/faker.
        $other = \App\Models\User::create([
            'name' => 'Push Test Other',
            'email' => 'push-test-' . time() . '@betslip-pirates.test',
            'phone' => '07' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => \Illuminate\Support\Facades\Hash::make(
                \Illuminate\Support\Str::random(32),
            ),
            'country_code' => '+254',
            'code' => 'PUSH' . strtoupper(\Illuminate\Support\Str::random(6)),
            'email_verified_at' => now(),
        ]);

        // ── Build supporting models ──────────────────────────────
        $betslip = \App\Models\Betslip::create([
            'user_id' => $user->id,
            'code' => 'PT-' . time(),
            'price' => 100,
            'total_odds' => 2.50,
            'status' => 'pending',
            'remaining' => 3,
            'is_winner' => false,
        ]);

        $contest = \App\Models\Contest::create([
            'host_id' => $user->id,
            'name' => 'Push Test Contest',
            'visibility' => 'private',
            'status' => 'open',
            'entry_deadline_at' => now()->addDay(),
            'starts_at' => now()->addDay()->addHour(),
            'ends_at' => now()->addDay()->addHours(2),
        ]);

        $deposit = \App\Models\Deposit::create([
            'user_id' => $user->id,
            'amount' => 500,
            'reference' => 'DEP-' . time(),
            'status' => 'confirmed',
        ]);

        // ── Fire every notification ──────────────────────────────
        $sent = [];

        $fire = function (string $label, $notification) use ($user, &$sent) {
            try {
                $user->notify($notification);
                $sent[] = ['ok' => true, 'label' => $label];
            } catch (\Throwable $e) {
                $sent[] = [
                    'ok' => false,
                    'label' => $label,
                    'error' => get_class($e) . ': ' . $e->getMessage(),
                ];
            }
        };

        $fire('contest_join_requested', new \App\Notifications\ContestJoinRequestedNotification($contest, $other));
        $fire('betslip_won', new \App\Notifications\BetslipWonNotification($betslip));
        $fire('betslip_lost', new \App\Notifications\BetslipLostNotification($betslip));
        $fire('betslip_voided', new \App\Notifications\BetslipVoidedNotification($betslip));
        $fire('watcher_settlement_won', new \App\Notifications\WatcherSettlementNotification($betslip, 'won'));
        $fire('watcher_settlement_refunded', new \App\Notifications\WatcherSettlementNotification($betslip, 'refunded'));
        $fire('contest_entry_accepted', new \App\Notifications\ContestEntryStatusNotification($contest, $other, 'accepted'));
        $fire('contest_entry_rejected', new \App\Notifications\ContestEntryStatusNotification($contest, $other, 'rejected'));
        $fire('contest_settled', new \App\Notifications\ContestSettledNotification($contest, 1, 5, 10.0, 20));
        $fire('referral_signup', new \App\Notifications\ReferralSignupNotification($other, 0.10));
        $fire('referral_attributed', new \App\Notifications\ReferralAttributedNotification($other, 0.05, 500.0));
        $fire('referral_reward', new \App\Notifications\ReferralRewardNotification($other, $betslip, 25.0));
        $fire('deposit_confirmed', new \App\Notifications\DepositConfirmedNotification($deposit));

        $user = \App\Models\User::where('email', 'denis.mwangi24@students.dkut.ac.ke')->first();
        $sub_count = $user->pushSubscriptions()->count();

        return response()->json([
            'recipient' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
            'other_user' => ['id' => $other->id, 'name' => $other->name, 'email' => $other->email],
            'subscriptions' => $subCount,
            'sent_count' => count(array_filter($sent, fn($s) => $s['ok'])),
            'failed_count' => count(array_filter($sent, fn($s) => !$s['ok'])),
            'notifications' => $sent,
            'subscriptions' => $sub_count,
            'cleanup_ids' => [
                'betslip_id' => $betslip->id,
                'contest_id' => $contest->id,
                'deposit_id' => $deposit->id,
                'other_user_id' => $other->id,
            ],
        ]);
    })->name('dev.push-test');

    Route::get('/shift-fixture-dates', function () {
        $earliest = \App\Models\Fixture::min('date');

        if (!$earliest) {
            return response()->json(['status' => 'error', 'message' => 'No fixtures found.'], 404);
        }

        $delta = (int) \Carbon\Carbon::parse($earliest)
            ->diffInDays(now()->addDay(), false);

        $driver = \DB::connection()->getDriverName();

        $dateExpr = $driver === 'sqlite'
            ? "datetime(date, '{$delta} days')"
            : "DATE_ADD(date, INTERVAL {$delta} DAY)";

        $timestampExpr = $driver === 'sqlite'
            ? "strftime('%s', datetime(date, '{$delta} days'))"
            : "UNIX_TIMESTAMP(DATE_ADD(date, INTERVAL {$delta} DAY))";

        $affected = \App\Models\Fixture::query()
            // ->whereBetween('date', ['2022-06-06 00:00:00', '2022-08-06 23:59:59'])
            ->where('id_on_api', 867946)
            ->update([
                'date' => \DB::raw($dateExpr),
                'timestamp' => \DB::raw($timestampExpr),
            ]);

        return response()->json([
            'status' => 'ok',
            'driver' => $driver,
            'shifted' => $affected,
            'delta' => $delta,
            'earliest_after' => \App\Models\Fixture::min('date'),
            'latest_after' => \App\Models\Fixture::max('date'),
            'upcoming_count' => \App\Models\Fixture::whereBetween('date', [
                now(),
                now()->addDays(14),
            ])->count(),
        ]);
    })->name('dev.shift-fixture-dates');

    Route::get('/shift-fixture-dates/revert', function () {
        $earliest = \App\Models\Fixture::min('date');

        if (!$earliest) {
            return response()->json([
                'status' => 'error',
                'message' => 'No fixtures found.',
            ], 404);
        }

        // Reverse by shifting the shifted set back by the same delta.
        // We compute delta by comparing "should be tomorrow" against the
        // current earliest.
        $delta = (int) \Carbon\Carbon::parse($earliest)
            ->diffInDays(now()->addDay(), false);

        $affected = \App\Models\Fixture::query()
            ->whereBetween('date', [now(), now()->addDays(365)])
            ->update([
                'date' => \DB::raw("datetime(date, '{$delta} days')"),
                'timestamp' => \DB::raw("strftime('%s', datetime(date, '{$delta} days'))"),
            ]);

        return response()->json([
            'status' => 'ok',
            'shifted' => $affected,
            'delta' => $delta,
        ]);
    })->name('dev.shift-fixture-dates.revert');

    Route::get('/clear-push-dismiss', function () {
        return response(<<<'HTML'
            <!doctype html>
            <body>
                <p>Clearing push prompt dismissal...</p>
                <script>
                    localStorage.removeItem('push_prompt_dismissed_until');
                    document.body.insertAdjacentHTML('beforeend', '<p>Done. <a href="/dashboard">Go to dashboard</a></p>');
                </script>
            </body>
            HTML, 200, ['Content-Type' => 'text/html']);
    });

    // ── TEMP: push client debug. Remove after diagnosing. ──
    Route::get('/push-client-debug', function () {
        return response(<<<'HTML'
            <!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <title>Push client debug</title>
                <style>
                    body { font-family: ui-monospace, monospace; padding: 16px; background: #0d1527; color: #e2e8f0; }
                    h2 { font-size: 14px; letter-spacing: 2px; text-transform: uppercase; color: #ff8c00; }
                    pre { background: #161c2a; padding: 12px; border-radius: 8px; border: 1px solid #232d42; white-space: pre-wrap; word-break: break-word; font-size: 12px; line-height: 1.6; }
                    .ok { color: #10b981; }
                    .bad { color: #ef4444; }
                    .warn { color: #f59e0b; }
                </style>
            </head>
            <body>
                <h2>Push client debug</h2>
                <pre id="out">Running…</pre>
                <script>
                    (async () => {
                        const out = document.getElementById('out');
                        const lines = [];
                        const add = (label, val, cls = '') => lines.push((cls ? '[' + cls + '] ' : '') + label + ': ' + val);

                        add('User agent', navigator.userAgent.substring(0, 120));
                        add('Is secure context (HTTPS)', window.isSecureContext, window.isSecureContext ? 'ok' : 'bad');
                        add('Notification API present', 'Notification' in window, 'Notification' in window ? 'ok' : 'bad');
                        add('Notification.permission', 'Notification' in window ? Notification.permission : 'N/A',
                            !('Notification' in window) ? 'bad' : (Notification.permission === 'granted' ? 'ok' : (Notification.permission === 'denied' ? 'bad' : 'warn')));
                        add('serviceWorker in navigator', 'serviceWorker' in navigator, 'serviceWorker' in navigator ? 'ok' : 'bad');
                        add('PushManager in window', 'PushManager' in window, 'PushManager' in window ? 'ok' : 'bad');
                        add('Standalone (PWA installed)', window.matchMedia('(display-mode: standalone)').matches);

                        if ('serviceWorker' in navigator) {
                            try {
                                const reg = await navigator.serviceWorker.ready;
                                add('SW scope', reg.scope, 'ok');
                                add('SW state', reg.active ? reg.active.state : 'no active worker', reg.active ? 'ok' : 'bad');
                                try {
                                    const sub = await reg.pushManager.getSubscription();
                                    add('Local push subscription', sub ? 'yes' : 'no', sub ? 'ok' : 'warn');
                                    if (sub) {
                                        add('  endpoint prefix', sub.endpoint.substring(0, 80));
                                        add('  endpoint host', new URL(sub.endpoint).hostname);
                                    }
                                } catch (e) {
                                    add('getSubscription threw', e.name + ': ' + e.message, 'bad');
                                }
                            } catch (e) {
                                add('SW ready failed', e.name + ': ' + e.message, 'bad');
                            }
                        }

                        try {
                            const r = await fetch('/push/status', {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });
                            add('/push/status HTTP', String(r.status), r.ok ? 'ok' : 'bad');
                            if (r.ok) {
                                const data = await r.json();
                                add('/push/status body', JSON.stringify(data));
                            } else {
                                const text = await r.text();
                                add('/push/status body', text.substring(0, 300));
                            }
                        } catch (e) {
                            add('/push/status network error', e.message, 'bad');
                        }

                        out.textContent = lines.join('\n');
                    })();
                </script>
            </body>
            </html>
            HTML, 200, ['Content-Type' => 'text/html']);
    })->name('dev.push-client-debug');
});

Route::get('/fixtures/{fixture}/results', [\App\Http\Controllers\FixtureResultsController::class, 'show'])
    ->name('fixtures.results');

// ── Landing ──
Route::get('/', [HomeController::class, 'index'])->name('home');

// ── Marketing / Company ──
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/careers', fn() => Inertia::render('Careers'))->name('careers');
Route::get('/press', fn() => Inertia::render('Press'))->name('press');
Route::get('/community', fn() => Inertia::render('Community'))->name('community');

// ── Help / Support ──
Route::get('/help', fn() => Inertia::render('HelpCenter'))->name('help');
Route::get('/faq', fn() => Inertia::render('Faq'))->name('faq');
Route::get('/how-it-works', fn() => Inertia::render('HowItWorks'))->name('how-it-works');

// ── Contact ──
Route::get('/contact', fn() => Inertia::render('Contact'))->name('contact');
Route::post('/contact', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|min:10',
    ]);

    // TODO: Mail::to('hello@betslip-pirates.com')->send(new ContactFormMail($request->validated()));

    return back()->with('success', 'Thanks — we\'ll be in touch within 24 hours.');
})->name('contact.submit');

// ── Legal ──
Route::get('/terms', fn() => Inertia::render('Legal/Terms'))->name('terms');
Route::get('/privacy', fn() => Inertia::render('Legal/Privacy'))->name('privacy');
Route::get('/cookies', fn() => Inertia::render('Legal/Cookies'))->name('cookies');
Route::get('/responsible-gaming', fn() => Inertia::render('Legal/ResponsibleGaming'))->name('responsible-gaming');

// ── Public Data / Profile Pages ──
Route::get('/fixture/{id}', [FixtureController::class, 'index'])->name('fixture');
Route::get('/fixtures', [FixturesController::class, 'index'])->name('fixtures.index');
Route::get('/profile/{user_code}', [ProfileController::class, 'index'])->name('profile');
Route::get('/sellers/lookup', [SellerLookupController::class, 'show']);
// ── Analyst Picks (public) ──
Route::get('/analyst-picks', [\App\Http\Controllers\AnalystPickController::class, 'index'])
    ->name('analyst-picks.index');

Route::get('/analyst-picks/{betslip}', [\App\Http\Controllers\AnalystPickController::class, 'show'])
    ->name('analyst-picks.show');

Route::get('/analysts/{slug}', [\App\Http\Controllers\AnalystProfileController::class, 'show'])
    ->name('analysts.show');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

// ── Reporting ──
Route::get('/report', fn() => Inertia::render('Report'))->name('report');
Route::post('/report', [ReportController::class, 'store'])->name('report.submit');

// ── Referral Links ──
Route::get('/r/{code}', [ReferralLinkController::class, 'show'])->name('referral.link');

// ── Bet slip Code Lookup ──
Route::get('/betslips/lookup', [BetslipController::class, 'lookup'])->name('betslip.lookup');

Route::get('/jackpots', [\App\Http\Controllers\JackpotController::class, 'index'])
    ->name('jackpots.index');

Route::get('/jackpots/{uuid}', [\App\Http\Controllers\JackpotController::class, 'show'])
    ->name('jackpots.show');


Route::get('/jackpots/{uuid}/picks', [\App\Http\Controllers\JackpotController::class, 'show'])
    ->name('jackpots.show');

// ── Contest Join /     (Public View) ──
Route::prefix('contests')->group(function () {
    Route::get('/join/{uuid}', [ContestJoinController::class, 'show'])
        ->name('contests.join');

    Route::get('/{uuid}/results', [ContestResultsController::class, 'show'])
        ->name('contests.results');

    // Public JSON lookups backing the results page. Same visibility as
    // the results page itself — no auth required to find a row.
    Route::get('/{uuid}/entries/search', [ContestResultsController::class, 'search'])
        ->name('contests.entries.search');

    Route::get('/{uuid}/legs/{legId}/picks', [ContestResultsController::class, 'legPicks'])
        ->name('contests.legs.picks');
});
/*
|--------------------------------------------------------------------------
| WEBHOOKS (Must stay outside auth middleware)
|--------------------------------------------------------------------------
| Third-party providers (Paystack, etc.) cannot authenticate via session.
*/

Route::post('/paystack/webhook', [PaystackController::class, 'webhook'])->name('paystack.webhook');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES (auth + verified)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // ── Dashboard ──
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


    Route::get('/play-preview', [\App\Http\Controllers\Play\PlayDashboardController::class, 'index'])
        ->name('play.preview');

    // ── Dev / Maintenance ──
    Route::get('/deletebetslips', function () {
        try {
            Betslip::truncate();

            return response('Success: All betslips and relationships deleted.', 200);
        } catch (\Throwable $e) {
            return response('Fail: ' . $e->getMessage(), 500);
        }
    });

    Route::post('/jackpots/{uuid}/enter', [\App\Http\Controllers\JackpotController::class, 'enter'])
        ->name('jackpots.enter');

    // ── Marketplace (guest browsing allowed) ──
    Route::prefix('marketplace')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])
            ->withoutMiddleware(['auth', 'verified'])
            ->name('marketplace.view');
    });

    // ── Buyer ──

    Route::middleware('has.purchased')->prefix('buyer')->group(function () {
        Route::get('/following', [BuyerDashboardController::class, 'followingIndex'])
            ->name('buyer.following.index');

        Route::get('/purchases', [BuyerDashboardController::class, 'purchasesIndex'])
            ->name('buyer.purchases.index');

        Route::prefix('dashboard')->group(function () {
            Route::get('/', [BuyerDashboardController::class, 'index'])->name('buyer.dashboard');
            Route::get('/performance', [BuyerDashboardController::class, 'getPerformanceData'])->name('buyer.dashboard.performance');
            Route::get('/activity', [BuyerDashboardController::class, 'getRecentActivity'])->name('buyer.dashboard.activity');
            Route::get('/financial', [BuyerDashboardController::class, 'getFinancialSummary'])->name('buyer.dashboard.financial');
            Route::get('/wallet', [BuyerDashboardController::class, 'getWalletSummary'])->name('buyer.dashboard.wallet');
            Route::get('/insights', [BuyerDashboardController::class, 'getInsights'])->name('buyer.dashboard.insights');
        });
    });



    // ── Play — gated dashboard switcher targets ──
    Route::middleware('has.purchased')->prefix('dashboard')->group(function () {
        Route::get('/buy', [\App\Http\Controllers\Play\PlayDashboardController::class, 'buy'])
            ->name('play.buy');
    });

    Route::middleware('seller.access')->prefix('dashboard')->group(function () {
        Route::get('/sell', [\App\Http\Controllers\Play\PlayDashboardController::class, 'sell'])
            ->name('play.sell');
    });

    Route::get('/portfolio', [\App\Http\Controllers\PredictionPortfolioController::class, 'index'])
        ->name('portfolio.index');

    // ── Betslips ──
    Route::prefix('betslip')->group(function () {
        // CRUD / flow
        Route::post('/', [BetslipController::class, 'store'])->name('betslip.store');
        Route::post('/draft', [BetslipController::class, 'draft'])->name('betslip.draft');
        Route::get('/confirm', [BetslipController::class, 'confirm'])->name('betslip.confirm');

        // Legacy alias — kept so existing forms don't break.
        Route::post('/betslip', [BetslipController::class, 'store']);

        // Success / public view
        Route::get('/success/{code}', [BetslipController::class, 'success'])->name('betslip.success');
        Route::get('/view/g/{code}', [BetslipController::class, 'show'])
            ->withoutMiddleware(['auth', 'verified'])
            ->name('betslip.show_guest');

        // Purchase / unlock
        Route::post('/unlock', [BetslipPurchaseController::class, 'purchase'])->name('betslip.purchase');

        // Watch / unwatch
        Route::post('/{code}/watch', [BetslipWatchController::class, 'store'])
            ->name('betslip.watch');
        Route::delete('/{code}/watch', [BetslipWatchController::class, 'destroy'])
            ->name('betslip.unwatch');
    });

    // ── Push subscriptions ──
    Route::prefix('push')->group(function () {
        Route::post('/subscribe', [PushSubscriptionController::class, 'store'])
            ->name('push.subscribe');
        Route::delete('/subscribe', [PushSubscriptionController::class, 'destroy'])
            ->name('push.unsubscribe');
        Route::get('/status', [PushSubscriptionController::class, 'status'])
            ->name('push.status');
    });

    // ── Wallet: Deposits ──
    Route::prefix('deposit')->group(function () {
        Route::post('/initiate', [PaystackController::class, 'initiateDeposit'])->name('deposit.initiate');
        Route::get('/callback', [PaystackController::class, 'callback'])->name('deposit.callback');
        Route::post('/mpesa/initiate', [MpesaDepositController::class, 'store'])
            ->name('deposit.mpesa.initiate');
    });

    // ── Wallet: Withdrawals ──
    Route::post('/withdrawal/initiate', [PaystackController::class, 'initiateWithdrawal'])->name('withdrawal.initiate');
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);

    // ── Wallet: Balance / Transactions ──
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', function () {
            $wallet = app(WalletService::class)->getWallet(auth()->user());

            return response()->json([
                'balance' => (float) $wallet->balance,
                'currency' => $wallet->currency ?: 'KES',
                'updated_at' => $wallet->updated_at?->toIso8601String()
                    ?? now()->toIso8601String(),
            ]);
        })->name('wallet.balance');

        Route::get('/transactions', [WalletController::class, 'transactions'])
            ->name('wallet.transactions.index');
    });

    // ── Seller ──
    Route::middleware('seller.access')->prefix('seller')->group(function () {
        Route::get('/settlements', [SellerDashboardController::class, 'settlementsIndex'])
            ->name('seller.settlements.index');

        Route::get('/betslips', [SellerDashboardController::class, 'betslipsIndex'])
            ->name('seller.betslips.index');

        Route::get('/followers', [SellerDashboardController::class, 'followersIndex'])
            ->name('seller.followers.index');

        Route::prefix('dashboard')->group(function () {
            Route::get('/', [SellerDashboardController::class, 'index'])->name('seller.dashboard');
            Route::get('/realtime', [SellerDashboardController::class, 'getRealtimeData'])->name('seller.dashboard.realtime');
            Route::get('/performance', [SellerDashboardController::class, 'getPerformanceData'])->name('seller.dashboard.performance');
            Route::get('/market-performance', [SellerDashboardController::class, 'getMarketPerformance'])->name('seller.dashboard.market-performance');
            Route::get('/activity', [SellerDashboardController::class, 'getRecentActivity'])->name('seller.dashboard.activity');
            Route::get('/financial', [SellerDashboardController::class, 'getFinancialSummary'])->name('seller.dashboard.financial');
            Route::get('/insights', [SellerDashboardController::class, 'getInsights'])->name('seller.dashboard.insights');
            Route::get('/followers', [SellerDashboardController::class, 'getFollowerStats'])->name('seller.dashboard.followers');
        });
    });

    Route::prefix('sub-leagues')->group(function () {
        Route::post('/', [\App\Http\Controllers\SubLeagueController::class, 'store'])
            ->name('sub-leagues.store');
        Route::post('/join', [\App\Http\Controllers\SubLeagueController::class, 'join'])
            ->name('sub-leagues.join');
    });

    Route::get('/sub-leagues/{subLeague}/leaderboard', [\App\Http\Controllers\SubLeagueLeaderboardController::class, 'show'])
        ->name('sub-leagues.leaderboard');

    Route::get('/leaderboards/matchday', [\App\Http\Controllers\MatchdayLeaderboardController::class, 'global'])
        ->name('leaderboards.matchday');

    // ── Matchday leagues ──
    Route::get('/matchday', [\App\Http\Controllers\MatchdayDashboardController::class, 'index'])
        ->name('matchday.dashboard');

    Route::get('/matchday/sub-leagues/{subLeague}/leaderboard', [
        \App\Http\Controllers\SubLeagueLeaderboardController::class,
        'page',
    ])->name('matchday.sub-leagues.leaderboard');

    Route::get('/matchday/live', [\App\Http\Controllers\MatchdayLiveController::class, 'index'])
        ->name('matchday.live');

    Route::post('/matchday/settlements/{entry}/dismiss', [
        \App\Http\Controllers\MatchdayDashboardController::class,
        'dismissSettlement',
    ])->name('matchday.settlements.dismiss');


    Route::get('/matchday/sub-leagues/{subLeague}', [
        \App\Http\Controllers\SubLeagueLeaderboardController::class,
        'detail',
    ])->name('matchday.sub-leagues.show');

    // ── Contests (Authenticated actions) ──
    Route::prefix('contests')->group(function () {
        Route::get('/mine', [HostContestController::class, 'index'])
            ->name('contests.mine');

        Route::get('/create', [ContestCreateController::class, 'create'])
            ->name('contests.create');

        Route::post('/', [ContestCreateController::class, 'store'])
            ->name('contests.store');

        Route::post('/draft', [ContestCreateController::class, 'draft'])
            ->name('contests.draft');

        Route::get('/confirm', [ContestCreateController::class, 'confirm'])
            ->name('contests.confirm');

        Route::post('/join/{uuid}', [ContestJoinController::class, 'store'])
            ->name('contests.join.store');

        Route::get('/{uuid}/picks', [ContestPickController::class, 'show'])
            ->name('contests.picks');

        Route::post('/{uuid}/picks', [ContestPickController::class, 'store'])
            ->name('contests.picks.store');

        Route::get('/{uuid}/manage', [HostContestController::class, 'manage'])
            ->name('contests.manage');

        Route::get('/{contest}/created', [ContestCreateController::class, 'created'])
            ->name('contests.created');

        Route::post('/{contest}/entries/{entry}/accept', [ContestEntryController::class, 'accept'])
            ->name('contests.entries.accept');

        Route::post('/{contest}/entries/{entry}/reject', [ContestEntryController::class, 'reject'])
            ->name('contests.entries.reject');
    });

    // ── Social Graph (Follow / Feed) ──
    Route::prefix('users')->group(function () {
        Route::get('/feed', [FollowController::class, 'feed'])->name('feed.index');

        Route::post('/{user_id}/follow', [FollowController::class, 'follow'])->name('users.follow');
        Route::delete('/{user_id}/unfollow', [FollowController::class, 'unfollow'])->name('users.unfollow');
        Route::post('/{user_id}/toggle-follow', [FollowController::class, 'toggleFollow'])->name('users.toggle-follow');
        Route::get('/{user_id}/followers', [FollowController::class, 'followers'])->name('users.followers');
        Route::get('/{user_id}/following', [FollowController::class, 'following'])->name('users.following');
        Route::put('/{user_id}/follow-notifications', [FollowController::class, 'updateNotification'])->name('users.follow-notifications');
    });

    // ── Notifications ──
    Route::prefix('notifications')->group(function () {
        // Bell feed (JSON) — must come before the {id}-bearing routes below.
        Route::get('/feed', [NotificationController::class, 'feed']);

        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // ── Watchlist ──
    Route::get('/watchlist', [WatchlistController::class, 'index'])
        ->name('watchlist.index');

    Route::get('/watchlist/record', [WatchlistRecordController::class, 'index'])
        ->name('watchlist.record');

    // ── Referral Dashboard ──
    Route::get('/refer', [ReferralDashboardController::class, 'index'])
        ->name('refer');
});

/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

require __DIR__ . '/settings.php';