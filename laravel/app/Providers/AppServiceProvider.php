<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
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
        $trustedProxies = config('services.namdel.trusted_proxies');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            TrustProxies::at($trustedProxies);
        }
    }
}
