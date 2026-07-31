<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontJsonLd;
use App\Support\StorefrontPresenter;
use App\Support\StorefrontSeo;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function show(
        string $slug,
        Request $request,
        CategoryRepositoryInterface $categories,
        ProductRepositoryInterface $products,
    ): Response {
        $category = $categories->findBySlug($slug);

        if ($category === null) {
            throw new NotFoundHttpException;
        }

        $sort = in_array($request->query('sort'), ['latest', 'oldest', 'price_asc', 'price_desc'], true)
            ? $request->query('sort')
            : 'latest';

        $searchQuery = $request->query('q');

        $paginator = $products->paginatePublishedInCategory(
            $category,
            $searchQuery,
            $sort,
            (int) config('store.products_per_page', 15),
        );

        $pagination = $paginator->toArray();
        unset($pagination['data']);

        $settings = $this->siteSettings->current();
        $collection = $paginator->getCollection();

        return $this->renderStorefront(
            'Store/CategoryShow',
            [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image_url' => $category->coverImageUrl(),
                ],
                'filters' => [
                    'q' => $searchQuery ?? '',
                    'sort' => $sort,
                ],
                'products' => StorefrontPresenter::productCards($collection),
                'pagination' => $pagination,
            ],
            StorefrontSeo::forCategory($category, $settings, $paginator, is_string($searchQuery) ? $searchQuery : null),
            StorefrontJsonLd::forCategory($category, $settings, $collection),
            [
                ['name' => 'الرئيسية', 'href' => '/'],
                ['name' => $category->name],
            ],
        );
    }
}
