<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\MidtransPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MidtransPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configureRateLimiting();
    }

    /**
     * The storefront checkout reserves stock (decrements it) before payment
     * is confirmed, so an unthrottled endpoint would let someone spam orders
     * against a single business to lock up its entire stock without ever
     * paying — a per-IP limit alone doesn't stop that if the target is one
     * specific store, hence the second, per-business limit.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('checkout', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip())->response($this->tooManyAttemptsResponse()),
                Limit::perMinute(3)->by('business:'.$request->route('slug'))->response($this->tooManyAttemptsResponse()),
            ];
        });

        // Looser than checkout since normal typing can legitimately fire this
        // a few times, but still capped — it's a phone-number lookup, and
        // without a limit it'd let someone enumerate which numbers belong to
        // registered customers.
        RateLimiter::for('customer-lookup', function (Request $request) {
            return [
                Limit::perMinute(20)->by($request->ip())->response($this->tooManyAttemptsResponse()),
            ];
        });
    }

    private function tooManyAttemptsResponse(): \Closure
    {
        return fn (Request $request, array $headers) => response()->json([
            'message' => 'Terlalu banyak percobaan. Coba lagi sebentar lagi.',
        ], 429, $headers);
    }
}
