<?php

namespace App\Services\Storefront;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorefrontService
{
    public function defaultBranch(): ?Branch
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function settings(): SiteSetting
    {
        return SiteSetting::current();
    }

    /**
     * @param Branch|array<string, mixed>|null $branch
     *
     * @return array<string, mixed>
     */
    public function businessInfo(Branch|array|null $branch = null): array
    {
        $branch ??= $this->defaultBranch();
        $site = $this->settings();

        $branchName = is_array($branch) ? ($branch['name'] ?? null) : $branch?->name;
        $branchPhone = is_array($branch) ? ($branch['phone'] ?? null) : $branch?->phone;
        $branchEmail = is_array($branch) ? ($branch['email'] ?? null) : $branch?->email;
        $branchAddress = is_array($branch) ? ($branch['address'] ?? null) : $branch?->address;

        return [
            'name' => $site->business_name ?? $branchName ?? config('app.name'),
            'tagline' => $site->tagline,
            'phone' => $site->phone ?? $branchPhone,
            'email' => $site->email ?? $branchEmail,
            'address' => $site->address ?? $branchAddress,
            'tax_number' => $site->tax_number,
            'logo' => $site->logo ? Storage::url($site->logo) : null,
            'hero_image' => $site->hero_image ? Storage::url($site->hero_image) : null,
            'about_image' => $site->about_image ? Storage::url($site->about_image) : null,
            'categories_page_image' => $site->categories_page_image ? Storage::url($site->categories_page_image) : null,
            'products_page_image' => $site->products_page_image ? Storage::url($site->products_page_image) : null,
            'currency' => app(\App\Services\Shared\CurrencyService::class)->primary()->code,
            'description' => $site->hero_subtitle ?? $site->tagline,
            'opening_hours' => $site->opening_hours,
            'store_opens_at' => $this->formatStoredTime($site->store_opens_at) ?? '10:00',
            'store_closes_at' => $this->formatStoredTime($site->store_closes_at) ?? '21:00',
            'delivery_hours' => app(OpeningHoursService::class)->deliveryStatus(),
            'whatsapp' => $site->whatsapp ?? $branchPhone,
            'instagram' => $site->instagram,
            'facebook' => $site->facebook,
            'tiktok' => $site->tiktok,
            'hero_title' => $site->hero_title,
            'hero_subtitle' => $site->hero_subtitle,
            'homepage_sections' => $site->homepage_sections ?? ['hero', 'categories', 'featured', 'about_preview'],
            'show_featured_products' => $site->show_featured_products,
            'featured_products_limit' => $site->featured_products_limit,
            'trust_items' => $site->trust_items ?: SiteSetting::defaults()['trust_items'],
            'categories_section_title' => $site->categories_section_title ?: SiteSetting::defaults()['categories_section_title'],
            'categories_section_subtitle' => $site->categories_section_subtitle ?: SiteSetting::defaults()['categories_section_subtitle'],
            'featured_section_title' => $site->featured_section_title ?: SiteSetting::defaults()['featured_section_title'],
            'featured_section_subtitle' => $site->featured_section_subtitle ?: SiteSetting::defaults()['featured_section_subtitle'],
            'contact_preview_title' => $site->contact_preview_title ?: SiteSetting::defaults()['contact_preview_title'],
            'contact_preview_subtitle' => $site->contact_preview_subtitle ?: SiteSetting::defaults()['contact_preview_subtitle'],
            'categories_page_title' => $site->categories_page_title ?: SiteSetting::defaults()['categories_page_title'],
            'categories_page_subtitle' => $site->categories_page_subtitle ?: SiteSetting::defaults()['categories_page_subtitle'],
            'products_page_title' => $site->products_page_title ?: SiteSetting::defaults()['products_page_title'],
            'products_page_subtitle' => $site->products_page_subtitle ?: SiteSetting::defaults()['products_page_subtitle'],
            'about_title' => $site->about_title,
            'about_content' => $site->about_content,
            'about_features' => $site->about_features ?? [],
            'about_eyebrow' => $site->about_eyebrow ?: SiteSetting::defaults()['about_eyebrow'],
            'about_why_title' => $site->about_why_title ?: SiteSetting::defaults()['about_why_title'],
            'about_why_subtitle' => $site->about_why_subtitle ?: SiteSetting::defaults()['about_why_subtitle'],
            'about_cta_title' => $site->about_cta_title ?: SiteSetting::defaults()['about_cta_title'],
            'about_cta_subtitle' => $site->about_cta_subtitle ?: SiteSetting::defaults()['about_cta_subtitle'],
            'contact_title' => $site->contact_title,
            'contact_content' => $site->contact_content,
            'contact_map_embed' => $site->contact_map_embed,
            'contact_form_enabled' => $site->contact_form_enabled,
            'contact_eyebrow' => $site->contact_eyebrow ?: SiteSetting::defaults()['contact_eyebrow'],
            'map_lat' => $site->map_lat,
            'map_lng' => $site->map_lng,
            'map_title' => $site->map_title ?: SiteSetting::defaults()['map_title'],
            'map_subtitle' => $site->map_subtitle ?: SiteSetting::defaults()['map_subtitle'],
            'map_placements' => $site->map_placements ?: SiteSetting::defaults()['map_placements'],
            'map_url' => ($site->map_lat !== null && $site->map_lng !== null)
                ? "https://www.google.com/maps?q={$site->map_lat},{$site->map_lng}"
                : null,
            'meta_title' => $site->meta_title,
            'meta_description' => $site->meta_description,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function featuredProducts(?int $branchId = null, int $limit = 8): Collection
    {
        return $this->productsQuery($branchId)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{categories: Collection<int, ProductCategory>, products: Collection<int, Product>}
     */
    public function catalog(?int $branchId = null): array
    {
        return [
            'categories' => $this->categoriesQuery($branchId)->get(),
            'products' => $this->productsQuery($branchId)->get(),
        ];
    }

    /**
     * @return Builder<ProductCategory>
     */
    public function categoriesQuery(?int $branchId = null): Builder
    {
        $query = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $this->applySellableProductScope($q)])
            ->orderBy('sort_order')
            ->orderBy('name');

        return $this->applyBranchScope($query, $branchId);
    }

    /**
     * @return Builder<Product>
     */
    public function productsQuery(?int $branchId = null): Builder
    {
        $query = Product::query()
            ->with(['category', 'variants', 'taxRate'])
            ->whereHas('category', fn ($q) => $q->where('is_active', true));

        $this->applySellableProductScope($query);

        return $this->applyBranchScope($query, $branchId)->orderBy('name');
    }

    public function searchProducts(Request $request, ?int $branchId = null): LengthAwarePaginator
    {
        $query = $this->productsQuery($branchId);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($minPrice = $request->input('min_price')) {
            $query->where('sale_price', '>=', $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('sale_price', '<=', $maxPrice);
        }

        $sort = $request->input('sort', 'name');
        match ($sort) {
            'price_asc' => $query->orderBy('sale_price'),
            'price_desc' => $query->orderByDesc('sale_price'),
            'newest' => $query->latest(),
            default => $query->orderBy('name'),
        };

        return $query->paginate(12)->withQueryString();
    }

    public function findCategoryBySlug(string $slug, ?int $branchId = null): ?ProductCategory
    {
        $query = $this->categoriesQuery($branchId)
            ->where('slug', $slug)
            ->with(['products' => fn ($q) => $this->applySellableProductScope($q->with(['variants', 'taxRate'])->orderBy('name'))]);

        return $query->first();
    }

    /**
     * @param  Builder<Product>|Builder<ProductCategory>  $query
     * @return Builder<Product>|Builder<ProductCategory>
     */
    protected function applyBranchScope(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->where(fn (Builder $inner) => $inner
            ->where('branch_id', $branchId)
            ->orWhereNull('branch_id'));
    }

    /**
     * @param  Builder<Product>|Relation<Product, ProductCategory, Collection<int, Product>>  $query
     * @return Builder<Product>|Relation<Product, ProductCategory, Collection<int, Product>>
     */
    protected function applySellableProductScope(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('is_active', true)
            ->whereNotIn('type', ['raw', 'raw_material'])
            ->whereNotNull('category_id');
    }

    private function formatStoredTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('H:i');
        }

        $string = (string) $value;

        return preg_match('/^\d{2}:\d{2}/', $string) ? substr($string, 0, 5) : null;
    }
}
