<?php

namespace App\Providers;

use App\Models\Tag;
use App\Observers\TagObserver;
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
    }
}
