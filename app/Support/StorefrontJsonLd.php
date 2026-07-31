<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StorefrontJsonLd
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forHome(SiteSetting $site): array
    {
        return [
            self::organization($site),
            self::website($site),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forProduct(Product $product, SiteSetting $site): array
    {
        $product->loadMissing('category');

        return [
            self::organization($site),
            self::product($product, $site),
            self::breadcrumb([
                ['name' => 'الرئيسية', 'url' => url('/')],
                ['name' => 'القائمة', 'url' => url('/products')],
                ...($product->category
                    ? [['name' => $product->category->name, 'url' => url('/categories/'.$product->category->slug)]]
                    : []),
                ['name' => $product->name, 'url' => url('/products/'.$product->slug)],
            ]),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forCategory(Category $category, SiteSetting $site, Collection $products): array
    {
        $blocks = [
            self::organization($site),
            self::breadcrumb([
                ['name' => 'الرئيسية', 'url' => url('/')],
                ['name' => $category->name, 'url' => url('/categories/'.$category->slug)],
            ]),
        ];

        if ($products->isNotEmpty()) {
            $blocks[] = self::itemList(
                $products,
                $category->name,
                url('/categories/'.$category->slug),
            );
        }

        return $blocks;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forProductsIndex(SiteSetting $site, Collection $products): array
    {
        $blocks = [
            self::organization($site),
            self::website($site),
            self::breadcrumb([
                ['name' => 'الرئيسية', 'url' => url('/')],
                ['name' => 'القائمة', 'url' => url('/products')],
            ]),
        ];

        if ($products->isNotEmpty()) {
            $blocks[] = self::itemList($products, 'قائمة الطعام', url('/products'));
        }

        return $blocks;
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $items): array
    {
        $list = collect($items)->values()->map(function (array $item, int $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        })->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(SiteSetting $site): array
    {
        $name = $site->site_name ?: config('app.name');
        $logo = StorefrontSeo::absoluteUrl($site->logoUrl());

        $data = [
            '@context' => 'https://schema.org',
            '@type' => config('seo.organization_type', 'Store'),
            'name' => $name,
            'url' => url('/'),
            'description' => SeoText::clipDescription($site->site_description ?: $site->seo_description),
        ];

        if ($logo) {
            $data['logo'] = $logo;
            $data['image'] = $logo;
        }

        if ($site->whatsapp_phone) {
            $data['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'telephone' => preg_replace('/\s+/', '', $site->whatsapp_phone),
                'availableLanguage' => ['Arabic'],
            ];
        }

        $sameAs = array_values(array_filter([
            $site->facebook_url,
            $site->instagram_url,
            $site->tiktok_url,
        ]));

        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(SiteSetting $site): array
    {
        $name = $site->site_name ?: config('app.name');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $name,
            'url' => url('/'),
            'inLanguage' => 'ar-SA',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/products').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function product(Product $product, SiteSetting $site): array
    {
        $images = $product->getMedia('images')
            ->map(fn ($media) => StorefrontSeo::absoluteUrl($media->getUrl('large') ?: $media->getUrl()))
            ->filter()
            ->values()
            ->all();

        $primary = StorefrontSeo::absoluteUrl($product->primaryImageUrl());
        if ($primary && ! in_array($primary, $images, true)) {
            array_unshift($images, $primary);
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => SeoText::productDescription($product, $site),
            'url' => url('/products/'.$product->slug),
            'sku' => (string) $product->id,
            'offers' => [
                '@type' => 'Offer',
                'url' => url('/products/'.$product->slug),
                'priceCurrency' => config('seo.currency', 'SAR'),
                'price' => (float) $product->price,
                'availability' => 'https://schema.org/InStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        if ($images !== []) {
            $data['image'] = count($images) === 1 ? $images[0] : $images;
        }

        if ($product->category) {
            $data['category'] = $product->category->name;
        }

        return $data;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    public static function itemList(Collection $products, string $name, string $pageUrl): array
    {
        $elements = $products->values()->map(function (Product $product, int $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => url('/products/'.$product->slug),
                'name' => $product->name,
            ];
        })->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'url' => $pageUrl,
            'itemListElement' => $elements,
        ];
    }

    public static function paginationLinks(?LengthAwarePaginator $paginator): array
    {
        if ($paginator === null || $paginator->lastPage() <= 1) {
            return ['prev' => null, 'next' => null];
        }

        return [
            'prev' => $paginator->previousPageUrl() ? StorefrontSeo::absoluteUrl($paginator->previousPageUrl()) : null,
            'next' => $paginator->hasMorePages() ? StorefrontSeo::absoluteUrl($paginator->nextPageUrl()) : null,
        ];
    }
}
