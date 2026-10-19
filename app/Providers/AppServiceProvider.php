<?php

namespace App\Providers;

use App\Models\Tag;
use App\Observers\TagObserver;
use App\Services\RateLimitService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Named limiter for auth endpoints (API login/register),
        // with a very high-but-existing limit for trusted IPs.
        RateLimiter::for('auth', function (Request $request) {
            $service = app(RateLimitService::class);
            $isTrusted = $service->isTrustedIp($request);
            $max = $isTrusted
                ? RateLimitService::TRUSTED_LIMIT_PER_MINUTE
                : RateLimitService::DEFAULT_LIMIT_PER_MINUTE;
            $key = $isTrusted ? 'trusted' : $service->clientIp($request);

            return Limit::perMinute($max)->by($key);
        });

        $theme = config('app.theme', 'default');
        $variant = config('app.theme_variant');

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
