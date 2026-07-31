<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StorefrontSeo
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $image = null,
        public ?string $url = null,
        public string $type = 'website',
        public ?string $siteName = null,
        public string $robots = 'index,follow',
        public ?string $keywords = null,
        public ?string $prevUrl = null,
        public ?string $nextUrl = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'url' => $this->url,
            'type' => $this->type,
            'site_name' => $this->siteName,
            'robots' => $this->robots,
            'keywords' => $this->keywords,
            'prev_url' => $this->prevUrl,
            'next_url' => $this->nextUrl,
        ];
    }

    public static function absoluteUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    public static function clip(?string $text, int $max = 300): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        return Str::limit($plain, $max, '…');
    }

    public static function forSite(SiteSetting $site, ?string $imageUrl = null): self
    {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::clipTitle($site->seo_title ?: $name);
        $description = SeoText::clipDescription($site->seo_description ?: $site->site_description ?: $name);
        $image = self::siteImage($site, $imageUrl);

        return new self(
            title: $title,
            description: $description,
            image: $image,
            url: url('/'),
            type: 'website',
            siteName: $name,
            keywords: SeoText::keywordsFrom($site->seo_keywords),
        );
    }

    public static function forHome(SiteSetting $site, Collection $heroSlides): self
    {
        $firstSlideImage = $heroSlides->first()?->slideImageUrl();

        return self::forSite($site, $firstSlideImage);
    }

    public static function forProduct(Product $product, SiteSetting $site): self
    {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::productTitle($product, $site);
        $description = SeoText::productDescription($product, $site);

        $image = self::absoluteUrl(
            $product->socialImageUrl() ?: $site->logoUrl(),
        );

        return new self(
            title: $title,
            description: $description,
            image: $image,
            url: url('/products/'.$product->slug),
            type: 'product',
            siteName: $name,
            keywords: SeoText::keywordsFrom($product->seo_focus_keywords, $site->seo_keywords),
        );
    }

    public static function forCategory(
        Category $category,
        SiteSetting $site,
        ?LengthAwarePaginator $paginator = null,
        ?string $searchQuery = null,
    ): self {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::categoryTitle($category, $site);
        $description = SeoText::categoryDescription($category, $site);

        $image = self::absoluteUrl(
            $category->coverImageUrl() ?: $site->logoUrl(),
        );

        $canonical = url('/categories/'.$category->slug);
        $robots = self::listingRobots($searchQuery);
        $pagination = StorefrontJsonLd::paginationLinks($paginator);

        return new self(
            title: $title,
            description: $description,
            image: $image,
            url: $canonical,
            type: 'website',
            siteName: $name,
            robots: $robots,
            keywords: SeoText::keywordsFrom($category->seo_focus_keywords, $site->seo_keywords),
            prevUrl: $pagination['prev'],
            nextUrl: $pagination['next'],
        );
    }

    public static function forCategoriesIndex(
        SiteSetting $site,
        ?LengthAwarePaginator $paginator = null,
    ): self {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::clipTitle("أقسام القائمة — {$name}");
        $description = SeoText::clipDescription(
            $site->site_description ?: "تصفح أقسام قائمة {$name} — مقبلات، أطباق رئيسية، حلويات ومشروبات.",
        );

        $image = self::siteImage($site);
        $pagination = StorefrontJsonLd::paginationLinks($paginator);

        return new self(
            title: $title,
            description: $description,
            image: $image,
            url: url('/categories'),
            type: 'website',
            siteName: $name,
            keywords: SeoText::keywordsFrom($site->seo_keywords),
            prevUrl: $pagination['prev'],
            nextUrl: $pagination['next'],
        );
    }

    public static function forProductsIndex(
        SiteSetting $site,
        ?LengthAwarePaginator $paginator = null,
        ?string $searchQuery = null,
    ): self {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::clipTitle("قائمة الطعام — {$name}");
        $description = SeoText::clipDescription(
            $site->site_description ?: "تصفح قائمة {$name} كاملة واطلب أطباقك المفضلة عبر واتساب.",
        );

        $image = self::siteImage($site);
        $robots = self::listingRobots($searchQuery);
        $pagination = StorefrontJsonLd::paginationLinks($paginator);

        return new self(
            title: $title,
            description: $description,
            image: $image,
            url: url('/products'),
            type: 'website',
            siteName: $name,
            robots: $robots,
            keywords: SeoText::keywordsFrom($site->seo_keywords),
            prevUrl: $pagination['prev'],
            nextUrl: $pagination['next'],
        );
    }

    public static function forCart(SiteSetting $site): self
    {
        $name = $site->site_name ?: config('app.name');
        $title = SeoText::clipTitle("طلبك — {$name}");
        $description = SeoText::clipDescription("راجع أطباق طلبك وأكّده عبر واتساب من {$name}.");

        return new self(
            title: $title,
            description: $description,
            image: self::siteImage($site),
            url: url('/cart'),
            type: 'website',
            siteName: $name,
            robots: 'noindex,follow',
        );
    }

    protected static function siteImage(SiteSetting $site, ?string $override = null): ?string
    {
        if ($override !== null && $override !== '') {
            return self::absoluteUrl($override);
        }

        if ($logo = $site->logoUrl()) {
            return self::absoluteUrl($logo);
        }

        $banner = $site->getFirstMediaUrl('hero_banner', 'banner_webp') ?: $site->getFirstMediaUrl('hero_banner');

        return $banner !== '' ? self::absoluteUrl($banner) : null;
    }

    protected static function listingRobots(?string $searchQuery): string
    {
        if ($searchQuery !== null && trim($searchQuery) !== '') {
            return 'noindex,follow';
        }

        return 'index,follow';
    }
}
