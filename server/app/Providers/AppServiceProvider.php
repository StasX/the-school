<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        RateLimiter::for('login', function (Request $request) {
            $rateLimit = config('auth.login_rate_limit', 5);

            if (! is_numeric($rateLimit)) {
                $rateLimit = 5;
            }

            return Limit::perMinute((int) $rateLimit)
                ->by($request->ip());
        });
    }
}
