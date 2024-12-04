<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API Routes should be grouped under the 'api' middleware
            Route::prefix('api')
                ->middleware('api')  // This is important for API routes
                ->group(base_path('routes/api.php'));

            // Web Routes (usually for pages)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
