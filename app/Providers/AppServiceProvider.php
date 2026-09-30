<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;
use App\Listeners\AttributeReferralOnRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use App\Listeners\HandleMpesaDepositCallback;
use FelixMuhoro\Mpesa\Events\PaymentFailed;
use FelixMuhoro\Mpesa\Events\PaymentSuccessful;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\PlatformAccount::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        if ($this->app->environment('staging')) {
            URL::forceScheme('https');
        }
        Event::listen(Registered::class, AttributeReferralOnRegistration::class);
        Event::listen(PaymentSuccessful::class, [HandleMpesaDepositCallback::class, 'onSuccess']);
        Event::listen(PaymentFailed::class, [HandleMpesaDepositCallback::class, 'onFailure']);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
