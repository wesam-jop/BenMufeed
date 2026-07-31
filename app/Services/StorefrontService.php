<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\HomeSliderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;

class StorefrontService
{
    public function __construct(
        protected CategoryRepositoryInterface $categories,
        protected ProductRepositoryInterface $products,
        protected SiteSettingRepositoryInterface $settings,
        protected HomeSliderRepositoryInterface $homeSliders,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function homeData(): array
    {
        return [
            'settings' => $this->settings->current(),
            'homeSliders' => $this->homeSliders->activeForHome(),
            'categories' => $this->categories->allForNavigation(),
            'spotlightProducts' => $this->products->randomPublishedForHome(4),
        ];
    }
}
