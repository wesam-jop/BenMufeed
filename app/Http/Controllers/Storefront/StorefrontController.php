<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\RendersCmsPages;
use App\Models\CmsPage;
use App\Services\Cms\CmsPageBuilderService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    use RendersCmsPages;

    public function __construct(
        protected StorefrontService $storefront,
        protected CmsPageBuilderService $cmsBuilder,
    ) {}

    protected function storefrontService(): StorefrontService
    {
        return $this->storefront;
    }

    protected function cmsBuilderService(): CmsPageBuilderService
    {
        return $this->cmsBuilder;
    }

    public function index(): Response
    {
        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage('home')),
        );
    }

    public function categories(): Response
    {
        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage('categories')),
        );
    }

    public function categoryShow(string $slug): Response
    {
        $branchId = $this->storefront->defaultBranch()?->id;
        $category = $this->storefront->findCategoryBySlug($slug, $branchId);

        abort_if($category === null, 404);

        return Inertia::render('storefront/categories/show', [
            ...$this->storefrontSharedProps($branchId),
            'category' => $category,
        ]);
    }

    public function products(Request $request): Response
    {
        $branchId = $this->storefront->defaultBranch()?->id;
        $cms = $this->cmsPageProps($this->resolveSystemCmsPage('products'), $branchId);

        return Inertia::render('storefront/products/index', [
            ...$cms,
            'products' => $this->storefront->searchProducts($request, $branchId),
            'categories' => $this->storefront->categoriesQuery($branchId)->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'sort' => $request->input('sort'),
            ],
        ]);
    }

    public function about(): Response
    {
        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage('about')),
        );
    }

    public function contact(): Response
    {
        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage('contact')),
        );
    }

    public function errorPage(int $code): Response
    {
        abort_unless(in_array($code, [403, 404, 500], true), 404);

        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage("errors/{$code}")),
        );
    }

    public function checkoutSuccessPreview(): Response
    {
        return Inertia::render(
            'storefront/cms-page',
            $this->cmsPageProps($this->resolveSystemCmsPage('checkout-success')),
        );
    }

    public function cmsPage(string $cmsPath): Response
    {
        $normalized = CmsPage::normalizePath($cmsPath);
        $firstSegment = explode('/', $normalized)[0] ?? $normalized;

        abort_if(in_array($firstSegment, CmsPage::reservedPaths(), true), 404);
        abort_if(in_array($normalized, CmsPage::systemPaths(), true), 404);

        $page = CmsPage::findPublishedByPath($normalized, app()->getLocale());

        abort_if($page === null || $page->is_system, 404);

        return Inertia::render('storefront/cms-page', $this->cmsPageProps($page));
    }
}
