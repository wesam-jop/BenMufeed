<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CmsPage;
use App\Services\Cms\CmsPageBuilderService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Support\Facades\Cache;

trait RendersCmsPages
{
    abstract protected function storefrontService(): StorefrontService;

    abstract protected function cmsBuilderService(): CmsPageBuilderService;

    /** @return array<string, mixed> */
    protected function storefrontSharedProps(?int $branchId = null): array
    {
        $storefront = $this->storefrontService();
        $branch = $storefront->defaultBranch();
        $branchId ??= $branch?->id;

        return [
            'business' => $storefront->businessInfo($branch),
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ] : null,
            'navCategories' => Cache::remember(
                'storefront.nav_categories.'.($branchId ?? 0),
                120,
                fn () => $storefront->categoriesQuery($branchId)
                    ->limit(8)
                    ->get(['id', 'name', 'slug', 'image']),
            ),
        ];
    }

    /** @return array<string, mixed> */
    protected function cmsPageProps(CmsPage $page, ?int $branchId = null): array
    {
        $storefront = $this->storefrontService();
        $builder = $this->cmsBuilderService();
        $branch = $storefront->defaultBranch();
        $branchId ??= $branch?->id;
        $settings = $storefront->settings();
        $limit = (int) ($settings->featured_products_limit ?? 8);

        $sections = array_values(array_map(
            fn ($section) => $builder->normalizeSection(is_array($section) ? $section : []),
            $page->sections ?? [],
        ));

        return [
            ...$this->storefrontSharedProps($branchId),
            'cmsPage' => [
                'id' => $page->id,
                'title' => $page->title,
                'path' => $page->path,
                'locale' => $page->locale,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'theme' => $page->resolvedTheme(),
                'sections' => $sections,
            ],
            'categories' => $storefront->categoriesQuery($branchId)->get(),
            'featuredProducts' => ($settings->show_featured_products ?? true)
                ? $storefront->featuredProducts($branchId, $limit)
                : collect(),
        ];
    }

    protected function resolveSystemCmsPage(string $path): CmsPage
    {
        $this->cmsBuilderService()->ensureSystemPages();

        $locale = app()->getLocale();
        $page = CmsPage::systemPage($path, $locale)
            ?? CmsPage::findPublishedByPath($path, $locale);

        abort_if($page === null || ! $page->is_published, 404);

        return $page;
    }
}
