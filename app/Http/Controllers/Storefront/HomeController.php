<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontCache;
use App\Support\StorefrontJsonLd;
use App\Support\StorefrontSeo;
use Inertia\Response;

class HomeController extends Controller
{
    use RendersStorefrontPage;

    public function __invoke(SiteSettingRepositoryInterface $siteSettings): Response
    {
        $page = StorefrontCache::homeData();
        $settings = $siteSettings->current();
        $firstSlideImage = collect($page['heroSlides'] ?? [])->value('image_url');

        return $this->renderStorefront(
            'Store/Home',
            $page,
            StorefrontSeo::forSite($settings, is_string($firstSlideImage) ? $firstSlideImage : null),
            StorefrontJsonLd::forHome($settings),
        );
    }
}
