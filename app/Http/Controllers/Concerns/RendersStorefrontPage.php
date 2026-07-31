<?php

namespace App\Http\Controllers\Concerns;

use App\Support\StorefrontSeo;
use Inertia\Inertia;
use Inertia\Response;

trait RendersStorefrontPage
{
    /**
     * @param  array<string, mixed>  $props
     * @param  list<array<string, mixed>>  $structuredData
     * @param  list<array{name: string, url?: string|null, href?: string|null}>  $breadcrumbs
     */
    protected function renderStorefront(
        string $component,
        array $props,
        StorefrontSeo $seo,
        array $structuredData = [],
        array $breadcrumbs = [],
    ): Response {
        $seoData = $seo->toArray();

        return Inertia::render($component, [
            ...$props,
            'seo' => $seoData,
            'structuredData' => $structuredData,
            'breadcrumbs' => $breadcrumbs,
        ])->withViewData([
            'seo' => $seoData,
            'structuredData' => $structuredData,
        ]);
    }
}
