<?php

namespace App\Support;

use App\Models\PosSetting;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Services\StorefrontService;
use Illuminate\Support\Facades\Cache;

final class StorefrontCache
{
    public static function sharedSite(): array
    {
        return Cache::remember('storefront.shared.site', 3600, function (): array {
            $settings = app(SiteSettingRepositoryInterface::class)->current();

            return StorefrontPresenter::site($settings);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function phoneCountries(): array
    {
        return Cache::remember('storefront.phone_countries', 86400, fn (): array => PhoneCountries::forFrontend());
    }

    public static function deliveryFee(): float
    {
        return Cache::remember('storefront.delivery_fee', 3600, function (): float {
            return (float) PosSetting::current()->delivery_fee;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function navigationCategories(): array
    {
        return Cache::remember('storefront.navigation_categories', 900, function (): array {
            return app(CategoryRepositoryInterface::class)
                ->allForNavigation()
                ->map(fn ($category) => StorefrontPresenter::categoryNav($category))
                ->values()
                ->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function homeData(): array
    {
        return Cache::remember('storefront.home_data', 300, function (): array {
            $data = app(StorefrontService::class)->homeData();

            return [
                'settings' => StorefrontPresenter::site($data['settings']),
                'heroSlides' => StorefrontPresenter::homeSliders($data['homeSliders']),
                'categories' => collect($data['categories'])->map(fn ($c) => StorefrontPresenter::categoryNav($c))->values()->all(),
                'spotlightProducts' => StorefrontPresenter::productCards($data['spotlightProducts']),
            ];
        });
    }

    public static function forgetSite(): void
    {
        Cache::forget('storefront.shared.site');
        Cache::forget('storefront.home_data');
        \App\Repositories\SiteSettingRepository::forgetCache();
    }

    public static function forgetHome(): void
    {
        Cache::forget('storefront.home_data');
    }

    public static function forgetNavigation(): void
    {
        Cache::forget('storefront.navigation_categories');
        Cache::forget('storefront.home_data');
    }

    public static function forgetDeliveryFee(): void
    {
        Cache::forget('storefront.delivery_fee');
    }
}
