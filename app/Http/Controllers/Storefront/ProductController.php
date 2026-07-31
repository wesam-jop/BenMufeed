<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontJsonLd;
use App\Support\StorefrontPresenter;
use App\Support\StorefrontSeo;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function show(string $slug, ProductRepositoryInterface $products): Response
    {
        $product = $products->findPublishedBySlug($slug);

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        $similar = StorefrontPresenter::productCards($products->similarPublished($product, 4));
        $settings = $this->siteSettings->current();
        $seo = StorefrontSeo::forProduct($product, $settings);

        $breadcrumbs = [
            ['name' => 'الرئيسية', 'href' => '/'],
            ['name' => 'القائمة', 'href' => '/products'],
        ];

        if ($product->category) {
            $breadcrumbs[] = [
                'name' => $product->category->name,
                'href' => '/categories/'.$product->category->slug,
            ];
        }

        $breadcrumbs[] = ['name' => $product->name];

        return $this->renderStorefront(
            'Store/ProductShow',
            [
                'product' => StorefrontPresenter::productDetail($product),
                'similarProducts' => $similar,
            ],
            $seo,
            StorefrontJsonLd::forProduct($product, $settings),
            $breadcrumbs,
        );
    }
}
