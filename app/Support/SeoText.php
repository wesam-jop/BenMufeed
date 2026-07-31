<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class SeoText
{
    public static function plainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return $text;
    }

    public static function clipTitle(?string $text): string
    {
        return StorefrontSeo::clip($text, (int) config('seo.title_max', 60));
    }

    public static function clipDescription(?string $text): string
    {
        return StorefrontSeo::clip($text, (int) config('seo.description_max', 160));
    }

    public static function keywordsFrom(?string ...$parts): ?string
    {
        $merged = collect($parts)
            ->filter(fn (?string $p) => $p !== null && trim($p) !== '')
            ->flatMap(fn (string $p) => preg_split('/[,،;]+/u', $p) ?: [])
            ->map(fn (string $k) => trim($k))
            ->filter()
            ->unique()
            ->values();

        if ($merged->isEmpty()) {
            return null;
        }

        return $merged->take(12)->implode(', ');
    }

    public static function productTitle(Product $product, SiteSetting $site): string
    {
        if ($product->seo_title) {
            return self::clipTitle($product->seo_title);
        }

        $product->loadMissing('category');
        $siteName = $site->site_name ?: config('app.name');
        $sep = config('seo.title_separator', ' | ');
        $categoryName = $product->category?->name;

        $parts = array_filter([$product->name, $categoryName, $siteName]);

        return self::clipTitle(implode($sep, $parts));
    }

    public static function productDescription(Product $product, SiteSetting $site): string
    {
        if ($product->seo_description) {
            return self::clipDescription($product->seo_description);
        }

        $siteName = $site->site_name ?: config('app.name');
        $product->loadMissing('category');
        $categoryName = $product->category?->name;

        $base = $product->short_description
            ?: self::plainText($product->description)
            ?: ($categoryName
                ? "اطلب {$product->name} من قسم {$categoryName} في مطعم {$siteName}."
                : "اطلب {$product->name} من مطعم {$siteName}.");

        $keywords = self::keywordsFrom(
            $product->seo_focus_keywords,
            $site->seo_keywords,
        );

        if ($keywords) {
            $base .= ' '.Str::limit($keywords, 80, '');
        }

        return self::clipDescription($base);
    }

    public static function categoryTitle(Category $category, SiteSetting $site): string
    {
        if ($category->seo_title) {
            return self::clipTitle($category->seo_title);
        }

        $siteName = $site->site_name ?: config('app.name');
        $sep = config('seo.title_separator', ' | ');

        return self::clipTitle($category->name.$sep.$siteName);
    }

    public static function categoryDescription(Category $category, SiteSetting $site): string
    {
        if ($category->seo_description) {
            return self::clipDescription($category->seo_description);
        }

        $siteName = $site->site_name ?: config('app.name');
        $base = $category->description
            ?: "تصفح أطباق {$category->name} في مطعم {$siteName} واطلب عبر واتساب.";

        $keywords = self::keywordsFrom(
            $category->seo_focus_keywords,
            $site->seo_keywords,
        );

        if ($keywords) {
            $base .= ' '.Str::limit($keywords, 80, '');
        }

        return self::clipDescription($base);
    }
}
