<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $this->loadMigrationsFrom(base_path('../database/migrations'));

        RateLimiter::for('health', function (Request $request) {
            $path = $request->path();
            $maxAttempts = str_contains($path, 'live') ? 120 : 30;

            return Limit::perMinute($maxAttempts)->by('health:' . $path . ':' . $request->ip());
        });

        RateLimiter::for('typing', function (Request $request) {
            return Limit::perMinute(40)->by('typing:' . ($request->user()?->id ?: $request->ip()));
        });
    }
}
