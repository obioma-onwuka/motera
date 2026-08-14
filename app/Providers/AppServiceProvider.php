<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
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
        // Safe-guard against stale public/hot file breaking the UI locally
        if ($this->app->environment('local') && File::exists(public_path('hot'))) {
            try {
                $url = rtrim(trim(File::get(public_path('hot'))), '/');
                $context = stream_context_create([
                    'http' => ['timeout' => 0.15],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);
                // Strict check: if the actual Vite client heartbeat is dead, it's stale.
                if (! @file_get_contents($url.'/@vite/client', false, $context)) {
                    File::delete(public_path('hot'));
                }
            } catch (\Exception $e) {
                File::delete(public_path('hot'));
            }
        }

        RateLimiter::for('transfers', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('withdrawals', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('kyc', function (Request $request) {
            return Limit::perMinute(2)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('account-lookup', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Super Admin bypasses all permission gates
        Gate::before(fn ($user) => $user && $user->hasRole('Super Admin') ? true : null);

        Blade::component('layouts.customer', 'customer-layout');
        Blade::component('layouts.admin', 'admin-layout');
        Blade::component('layouts.base', 'base-layout');
    }
}
