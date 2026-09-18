<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('password-reset', fn ($request) => Limit::perMinute(3)->by($request->string('email')->lower()->toString().'|'.$request->ip()));
        RateLimiter::for('manager-password-reset', function ($request): Limit {
            $target = $request->route('user');
            $targetId = $target instanceof User ? $target->id : $target;

            return Limit::perMinute(5)->by($request->user()?->id.'|'.$targetId);
        });

        View::composer('components.layouts.app', function ($view): void {
            $user = auth()->user();

            $view->with('navbarNotifications', $user?->notifications()->latest()->limit(5)->get() ?? collect())
                ->with('navbarUnreadCount', $user?->unreadNotifications()->count() ?? 0);
        });
    }
}
