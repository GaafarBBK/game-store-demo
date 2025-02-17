<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Game;
use App\Models\Review;
use App\Models\Favorite;
use App\Models\Purchase;
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
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->input('email'));
        });

        // This gate is used to check if the user has permission to manage any of the resources for permission-based functionality.
        Gate::define('manage-resource', function (User $user, $model) {
            if ($model instanceof Game) {
                return $user->id === $model->manager || $user->role === 'admin';
            }

            return $user->id === $model->user_id || $user->role === 'admin';
        });
    }
}
