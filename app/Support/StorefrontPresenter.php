<?php

namespace App\Support;

use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StorefrontPresenter
{
    private static function mediaUrl(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media, ?string $conversion = null): ?string
    {
        if ($media === null) {
            return null;
        }

        if ($conversion !== null && $media->hasGeneratedConversion($conversion)) {
            $url = $media->getUrl($conversion);

            if ($url !== '') {
                return $url;
            }
        }

        $url = $media->getUrl();

        return $url !== '' ? $url : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function site(SiteSetting $s): array
    {
        return [
            'site_name' => $s->site_name,
            'site_description' => $s->site_description,
            'whatsapp_phone' => $s->whatsapp_phone,
            'facebook_url' => $s->facebook_url,
            'instagram_url' => $s->instagram_url,
            'tiktok_url' => $s->tiktok_url,
            'hero_title' => $s->hero_title,
            'hero_subtitle' => $s->hero_subtitle,
            'seo_title' => $s->seo_title,
            'seo_description' => $s->seo_description,
            'logo_url' => $s->logoUrl(),
            'hero_banner_url' => self::mediaUrl($s->getFirstMedia('hero_banner'), 'banner_webp'),
            'currency_code' => config('store.currency_code', 'USD'),
            'currency_symbol' => config('store.currency_symbol', '$'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function homeSlider(HomeSlider $slide): array
    {
        return [
            'id' => $slide->id,
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'button_text' => $slide->button_text,
            'button_url' => $slide->button_url,
            'image_url' => $slide->slideImageUrl(),
        ];
    }

    /**
     * @param  Collection<int, HomeSlider>  $slides
     * @return array<int, array<string, mixed>>
     */
    public static function homeSliders(Collection $slides): array
    {
        return $slides->map(fn (HomeSlider $s) => self::homeSlider($s))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function categoryNav(Category $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'published_products_count' => (int) ($c->published_products_count ?? 0),
            'image_url' => $c->coverImageUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function categoryCard(Category $c): array
    {
        return [
            ...self::categoryNav($c),
            'description' => $c->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function productCard(Product $p): array
    {
        $hasOptions = ($p->active_variants_count ?? 0) > 0 || ($p->active_addons_count ?? 0) > 0;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'short_description' => $p->short_description,
            'price' => self::resolveCardPrice($p),
            'base_price' => (float) $p->price,
            'has_options' => $hasOptions,
            'compare_at_price' => $p->compare_at_price !== null ? (float) $p->compare_at_price : null,
            'is_featured' => (bool) $p->is_featured,
            'is_delivery_available' => (bool) $p->is_delivery_available,
            'image_url' => $p->primaryImageUrl(),
            'category' => $p->relationLoaded('category') && $p->category ? [
                'name' => $p->category->name,
                'slug' => $p->category->slug,
            ] : null,
        ];
    }

    protected static function resolveCardPrice(Product $p): float
    {
        if ($p->relationLoaded('activeVariants') && $p->activeVariants->isNotEmpty()) {
            return (float) $p->activeVariants->min('price');
        }

        return (float) $p->price;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    public static function productCards(Collection $products): array
    {
        return $products->map(fn (Product $p) => self::productCard($p))->values()->all();
    }

    /**
     * بطاقة منتج لقائمة الطاولة — تشمل الخيارات (variants/addons) للطلب داخل الصفحة.
     *
     * @return array<string, mixed>
     */
    public static function tableMenuProduct(Product $p): array
    {
        return [
            ...self::productCard($p),
            'variants' => $p->relationLoaded('activeVariants')
                ? $p->activeVariants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => (float) $variant->price,
                    'is_default' => (bool) $variant->is_default,
                ])->values()->all()
                : [],
            'addons' => $p->relationLoaded('activeAddons')
                ? $p->activeAddons->map(fn ($addon) => [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => (float) $addon->price,
                    'max_quantity' => (int) $addon->max_quantity,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    public static function tableMenuProducts(Collection $products): array
    {
        return $products->map(fn (Product $p) => self::tableMenuProduct($p))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function productDetail(Product $p): array
    {
        if (! $p->relationLoaded('activeVariants')) {
            $p->loadMissing(['category', 'activeVariants', 'activeAddons']);
        }

        if (! isset($p->active_variants_count)) {
            $p->loadCount(['activeVariants', 'activeAddons']);
        }

        $images = $p->getMedia('images')
            ->map(fn ($media) => $media->getUrl('large') ?: $media->getUrl())
            ->filter()
            ->values()
            ->all();

        if ($images === []) {
            $fallback = $p->primaryImageUrl();
            $images = $fallback ? [$fallback] : [];
        }

        return [
            ...self::productCard($p),
            'description' => filled($p->description)
                ? Str::sanitizeHtml($p->description)
                : null,
            'seo_title' => $p->seo_title,
            'seo_description' => $p->seo_description,
            'images' => $images,
            'variants' => $p->activeVariants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price' => (float) $variant->price,
                'cost' => $variant->cost !== null ? (float) $variant->cost : null,
                'is_default' => (bool) $variant->is_default,
            ])->values()->all(),
            'addons' => $p->activeAddons->map(fn ($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->price,
                'cost' => $addon->cost !== null ? (float) $addon->cost : null,
                'max_quantity' => (int) $addon->max_quantity,
            ])->values()->all(),
        ];
    }
}
