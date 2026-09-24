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
        $theme = config('app.theme', env('APP_THEME', 'default'));
        $paths = [
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ];

        if ($theme !== 'default') {
            $themePath = resource_path("views/themes/{$theme}/livewire");
            if (file_exists($themePath)) {
                array_unshift($paths, $themePath);
            }
        }

        Volt::mount($paths);
    }
}
