<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\ProductCatalogQuery;
use App\Support\StorefrontCache;
use App\Support\StorefrontJsonLd;
use App\Support\StorefrontPresenter;
use App\Support\StorefrontSeo;
use Illuminate\Http\Request;
use Inertia\Response;

class ProductIndexController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function __invoke(
        Request $request,
        ProductRepositoryInterface $products,
    ): Response {
        $catalog = ProductCatalogQuery::fromRequest($request);

        $perPage = (int) config('store.products_per_page', 15);

        $paginator = $catalog->hasValidCategory()
            ? $products->paginateAllPublished(
                $catalog->q,
                $catalog->sort,
                $catalog->categoryId(),
                $perPage,
                $catalog->page,
            )
            : $products->paginateAllPublished(null, 'latest', null, $perPage, 1);

        $pagination = $paginator->toArray();
        unset($pagination['data']);

        $settings = $this->siteSettings->current();
        $collection = $paginator->getCollection();

        return $this->renderStorefront(
            'Store/ProductIndex',
            [
                'filters' => $catalog->toFilterArray(),
                'products' => StorefrontPresenter::productCards($collection),
                'pagination' => $pagination,
                'catalogMeta' => [
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'categories' => StorefrontCache::navigationCategories(),
            ],
            StorefrontSeo::forProductsIndex(
                $settings,
                $paginator,
                $catalog->q,
            ),
            StorefrontJsonLd::forProductsIndex($settings, $collection),
            [
                ['name' => 'الرئيسية', 'href' => '/'],
                ['name' => 'القائمة'],
            ],
        );
    }
}
