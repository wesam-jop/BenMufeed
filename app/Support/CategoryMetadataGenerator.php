<?php

namespace App\Support;

use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class CategoryMetadataGenerator
{
    public static function apply(Category $category): void
    {
        if (blank($category->slug)) {
            $category->slug = self::uniqueSlug($category->name, $category->exists ? $category->id : null);
        }

        $site = SiteSetting::current();

        $category->seo_title = self::seoTitle($category->name, $site);
        $category->seo_description = self::seoDescription($category, $site);
        $category->seo_focus_keywords = self::seoKeywords($category->name, $site);
    }

    public static function uniqueSlug(string $name, ?int $exceptCategoryId = null): string
    {
        $base = Str::slug($name, language: 'ar');

        if ($base === '') {
            $base = Str::slug(Str::transliterate($name));
        }

        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $suffix = 2;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($exceptCategoryId, fn ($query) => $query->where('id', '!=', $exceptCategoryId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function seoTitle(string $name, SiteSetting $site): string
    {
        $siteName = $site->site_name ?: config('app.name');
        $sep = config('seo.title_separator', ' | ');

        return SeoText::clipTitle($name.$sep.$siteName);
    }

    public static function seoDescription(Category $category, SiteSetting $site): string
    {
        $siteName = $site->site_name ?: config('app.name');

        $base = $category->description
            ?: "تصفح أطباق {$category->name} في مطعم {$siteName} واطلب عبر واتساب.";

        $keywords = SeoText::keywordsFrom($category->name, $site->seo_keywords);

        if ($keywords) {
            $base .= ' '.Str::limit($keywords, 80, '');
        }

        return SeoText::clipDescription($base);
    }

    public static function seoKeywords(string $name, SiteSetting $site): ?string
    {
        return SeoText::keywordsFrom($name, $site->seo_keywords);
    }
}
