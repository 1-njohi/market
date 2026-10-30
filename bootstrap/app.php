<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Cache;
use App\Jobs\UpdateSellerMetrics;
use App\Models\User;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Route-model binding for the analyst-pick public URLs. Scoped so
            // unpublished picks and non-analyst slips 404 at binding time — the
            // controllers never see them and cannot leak their existence.
            //
            // Does NOT affect the existing /betslip/view/g/{code} route, which
            // binds on {code}, not {betslip}.
            Route::bind('betslip', function (string $value) {
                return \App\Models\Betslip::query()
                    ->where('id', $value)
                    ->where('is_analyst_slip', true)
                    ->whereNotNull('analyst_published_at')
                    ->firstOrFail();
            });

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'not.suspended' => \App\Http\Middleware\EnsureUserIsNotSuspended::class,
            'seller.access' => \App\Http\Middleware\EnsureSellerAccess::class,
            'has.purchased' => \App\Http\Middleware\EnsureHasPurchased::class,
            'is.analyst' => \App\Http\Middleware\EnsureUserIsAnalyst::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (InsufficientBalanceException $e, $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (\App\Exceptions\UnauthorizedHostingException $e, $request, ) {
            return response($e->getMessage(), 403);
        });
    })
    ->withSchedule(function ($schedule) {
        // Update seller metrics daily
        $schedule->call(function () {
            $users = User::whereHas('betslips')->get();
            foreach ($users as $user) {
                dispatch(new UpdateSellerMetrics($user));
            }
        })->everyMinute();

        // Update real-time stats every 5 minutes
        $schedule->call(function () {
            $users = User::whereHas('betslips')->get();
            foreach ($users as $user) {
                Cache::forget("seller_dashboard_{$user->id}");
            }
        })->everyFiveMinutes();
    })->create();

