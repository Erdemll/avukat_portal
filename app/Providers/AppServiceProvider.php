<?php

namespace App\Providers;

use App\Auth\SicilNoUserProvider;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('sicil_no', function ($app, array $config) {
            return new SicilNoUserProvider($app['hash'], $config['model']);
        });

        RateLimiter::for('password-reset', fn ($request) => Limit::perMinute(3)->by($request->string('email')->lower()->toString().'|'.$request->ip()));
        RateLimiter::for('manager-password-reset', function ($request): Limit {
            $target = $request->route('user');
            $targetId = $target instanceof User ? $target->id : $target;

            return Limit::perMinute(5)->by($request->user()?->id.'|'.$targetId);
        });
        RateLimiter::for('lawyer-login', fn ($request) => Limit::perMinute(5)->by($request->input('sicil_no')));
        RateLimiter::for('two-factor', fn ($request) => Limit::perMinute(5)->by($request->session()->get('two_factor_user_id', $request->ip())));

        View::composer('components.layouts.app', function ($view): void {
            $user = auth()->user();

            $view->with('navbarNotifications', $user?->notifications()->latest()->limit(5)->get() ?? collect())
                ->with('navbarUnreadCount', $user?->unreadNotifications()->count() ?? 0);
        });
    }
}
