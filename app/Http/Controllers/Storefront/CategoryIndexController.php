<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontJsonLd;
use App\Support\StorefrontPresenter;
use App\Support\StorefrontSeo;
use Illuminate\Http\Request;
use Inertia\Response;

class CategoryIndexController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function __invoke(Request $request, CategoryRepositoryInterface $categories): Response
    {
        $perPage = (int) config('store.categories_per_page', 15);
        $page = max(1, (int) $request->query('page', 1));

        $paginator = $categories->paginateForStorefront($perPage, $page);
        $pagination = $paginator->toArray();
        unset($pagination['data']);

        $settings = $this->siteSettings->current();

        return $this->renderStorefront(
            'Store/CategoryIndex',
            [
                'categories' => $paginator->getCollection()
                    ->map(fn ($c) => StorefrontPresenter::categoryCard($c))
                    ->values()
                    ->all(),
                'pagination' => $pagination,
                'listMeta' => [
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'per_page' => $perPage,
                ],
            ],
            StorefrontSeo::forCategoriesIndex($settings, $paginator),
            [
                StorefrontJsonLd::organization($settings),
                StorefrontJsonLd::breadcrumb([
                    ['name' => 'الرئيسية', 'url' => url('/')],
                    ['name' => 'أقسام القائمة', 'url' => url('/categories')],
                ]),
            ],
            [
                ['name' => 'الرئيسية', 'href' => '/'],
                ['name' => 'أقسام القائمة'],
            ],
        );
    }
}
