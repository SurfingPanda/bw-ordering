<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Checkout submissions (CheckoutController::store) — a first line of
        // defense against order-spam / fraud attempts (see memory
        // checkout-fraud-discussion): a bad actor can rotate IP but reuse a
        // phone number, or vice versa, so both are limited independently and
        // a request is throttled if it trips either one.
        RateLimiter::for('checkout', function (Request $request) {
            $phone = trim((string) $request->input('phone', ''));

            return [
                Limit::perMinutes(5, 6)->by('checkout-ip:'.$request->ip()),
                $phone !== ''
                    ? Limit::perMinutes(15, 8)->by('checkout-phone:'.$phone)
                    : Limit::none(),
            ];
        });
    }
}
