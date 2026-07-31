<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontSeo;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class StoreNotFoundController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function __invoke(): Response
    {
        $settings = $this->siteSettings->current();

        return Inertia::render('Store/NotFound', [
            'seo' => (new StorefrontSeo(
                title: 'الصفحة غير موجودة | '.($settings->site_name ?: config('app.name')),
                description: 'لم نعثر على الصفحة المطلوبة. ارجع إلى القائمة أو الصفحة الرئيسية.',
                image: StorefrontSeo::absoluteUrl($settings->logoUrl()),
                url: url()->current(),
                type: 'website',
                siteName: $settings->site_name ?: config('app.name'),
                robots: 'noindex,follow',
            ))->toArray(),
        ])->toResponse(request())->setStatusCode(404);
    }
}
