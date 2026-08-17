<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Sla\CalendarSlaCalculator;
use App\Services\Sla\SlaCalculator;
use Illuminate\Cache\RateLimiting\Limit;
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
        $this->app->bind(SlaCalculator::class, CalendarSlaCalculator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(UserRole::Administrator->value) ? true : null;
        });

        // Laravel's classic default "api" limiter (60 requests/minute, keyed by
        // authenticated user ID or IP) — the Laravel 11+ slim skeleton no longer
        // registers this automatically, so it must be defined explicitly for
        // `$middleware->throttleApi()` (bootstrap/app.php) to have a limiter to use.
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
