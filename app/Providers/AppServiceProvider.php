<?php

namespace App\Providers;

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
        if ($this->app->environment('local') && \Illuminate\Support\Facades\File::exists(public_path('hot'))) {
            try {
                $url = rtrim(trim(\Illuminate\Support\Facades\File::get(public_path('hot'))), '/');
                $context = stream_context_create([
                    'http' => ['timeout' => 0.15],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                // Strict check: if the actual Vite client heartbeat is dead, it's stale.
                if (!@file_get_contents($url . '/@vite/client', false, $context)) {
                    \Illuminate\Support\Facades\File::delete(public_path('hot'));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\File::delete(public_path('hot'));
            }
        }

        \Illuminate\Support\Facades\RateLimiter::for('transfers', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('withdrawals', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('kyc', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(2)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\Blade::component('layouts.customer', 'customer-layout');
        \Illuminate\Support\Facades\Blade::component('layouts.admin', 'admin-layout');
        \Illuminate\Support\Facades\Blade::component('layouts.base', 'base-layout');
    }
}
