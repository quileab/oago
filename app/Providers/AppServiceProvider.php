<?php

namespace App\Providers;

use App\Models\Tag;
use App\Observers\TagObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // para NGROK
        // if (config('app.env') === 'local') {
        //     $this->app['request']->server->set('HTTPS', true);
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Tag::observe(TagObserver::class);

        $theme = config('app.theme', env('APP_THEME', 'default'));
        $variant = config('app.theme_variant', env('APP_THEME_VARIANT'));

        $pathsToRegister = [];

        if ($theme !== 'default') {
            $baseThemePath = resource_path("views/themes/{$theme}");
            $pathsToRegister[] = $baseThemePath;

            if ($variant) {
                $pathsToRegister[] = "{$baseThemePath}/{$variant}";
            }
        }

        foreach ($pathsToRegister as $path) {
            if (file_exists($path)) {
                View::prependLocation($path);

                if (file_exists("{$path}/livewire")) {
                    View::prependNamespace('livewire', "{$path}/livewire");
                }
            }
        }
    }
}
