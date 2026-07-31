<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class ProductMetadataGenerator
{
    public static function apply(Product $product): void
    {
        if (blank($product->slug)) {
            $product->slug = self::uniqueSlug($product->name, $product->exists ? $product->id : null);
        }

        $site = SiteSetting::current();
        $categoryName = self::categoryName($product);

        $product->seo_title = self::seoTitle($product->name, $categoryName, $site);
        $product->seo_description = self::seoDescription($product, $categoryName, $site);
        $product->seo_focus_keywords = self::seoKeywords($product->name, $categoryName, $site);
    }

    public static function uniqueSlug(string $name, ?int $exceptProductId = null): string
    {
        $base = Str::slug($name, language: 'ar');

        if ($base === '') {
            $base = Str::slug(Str::transliterate($name));
        }

        if ($base === '') {
            $base = 'product';
        }

        $slug = $base;
        $suffix = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($exceptProductId, fn ($query) => $query->where('id', '!=', $exceptProductId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function seoTitle(string $name, ?string $categoryName, SiteSetting $site): string
    {
        $siteName = $site->site_name ?: config('app.name');
        $sep = config('seo.title_separator', ' | ');
        $parts = array_filter([$name, $categoryName, $siteName]);

        return SeoText::clipTitle(implode($sep, $parts));
    }

    public static function seoDescription(Product $product, ?string $categoryName, SiteSetting $site): string
    {
        $siteName = $site->site_name ?: config('app.name');

        $base = $product->short_description
            ?: SeoText::plainText($product->description)
            ?: ($categoryName
                ? "اطلب {$product->name} من قسم {$categoryName} في مطعم {$siteName}."
                : "اطلب {$product->name} من مطعم {$siteName}.");

        $keywords = SeoText::keywordsFrom(
            $product->name,
            $categoryName,
            $site->seo_keywords,
        );

        if ($keywords) {
            $base .= ' '.Str::limit($keywords, 80, '');
        }

        return SeoText::clipDescription($base);
    }

    public static function seoKeywords(string $name, ?string $categoryName, SiteSetting $site): ?string
    {
        return SeoText::keywordsFrom($name, $categoryName, $site->seo_keywords);
    }

    private static function categoryName(Product $product): ?string
    {
        if ($product->relationLoaded('category') && $product->category) {
            return $product->category->name;
        }

        if ($product->category_id) {
            return Category::query()->whereKey($product->category_id)->value('name');
        }

        return null;
    }
}
