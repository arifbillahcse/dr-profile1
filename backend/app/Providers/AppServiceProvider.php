<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFive();
        TrustProxies::at(config('app.trusted_proxies', []));
        RateLimiter::for('review-submissions', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));

        View::composer(['admin.layout', 'admin.login'], function (\Illuminate\View\View $view): void {
            $branding = SiteSetting::with('logo')->where('key', 'primary')->first();
            $view->with('adminSiteTitle', $branding?->site_title ?: config('app.name'));
            $view->with('adminLogo', $branding?->logo);
        });
    }
}
