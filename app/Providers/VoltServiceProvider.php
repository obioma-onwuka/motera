<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class VoltServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // NOTE: this mount call is REQUIRED infrastructure — it registers the
        // 'volt-livewire' view namespace used by Volt single-file components.
        // The app's single-file components live under resources/views/components,
        // which MUST be in the mounted paths for Volt's render() to resolve them.
        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/components'),
            resource_path('views/pages'),
        ]);
    }
}
