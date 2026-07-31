<?php

namespace App\Providers;

use App\Services\WebsiteOrderWorkflowService;
use App\Support\StorefrontCache;
use App\Support\StorefrontSeo;
use App\Listeners\InvalidateRestaurantMetrics;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            \App\Http\Auth\AppFilamentLogoutResponse::class,
        );

        $this->app->bind(
            \App\Repositories\Contracts\CategoryRepositoryInterface::class,
            \App\Repositories\CategoryRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\OrderRepositoryInterface::class,
            \App\Repositories\OrderRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\SiteSettingRepositoryInterface::class,
            \App\Repositories\SiteSettingRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\HomeSliderRepositoryInterface::class,
            \App\Repositories\HomeSliderRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(InvalidateRestaurantMetrics::class);

        Vite::prefetch(concurrency: 3);

        View::composer('app', function ($view): void {
            $logoUrl = StorefrontCache::sharedSite()['logo_url'] ?? null;

            $view->with(
                'siteLogoUrl',
                is_string($logoUrl) && $logoUrl !== '' ? StorefrontSeo::absoluteUrl($logoUrl) : null,
            );
        });

        View::composer('components.pos.header', function ($view): void {
            $user = auth()->user();
            $view->with(
                'pendingWebsiteCount',
                ($user !== null && $user->canAccessPos())
                    ? app(WebsiteOrderWorkflowService::class)->pendingWebsiteOrdersCount()
                    : 0,
            );
        });
    }
}
