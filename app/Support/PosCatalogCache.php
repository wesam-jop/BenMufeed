<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PosCatalogCache
{
    /**
     * @return Collection<int, Category>
     */
    public static function categories(): Collection
    {
        $rows = Cache::remember('pos.categories.rows', 600, function (): array {
            return Category::query()
                ->whereHas('products', fn ($q) => $q->published())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category): array => $category->getAttributes())
                ->all();
        });

        return new Collection(Category::query()->hydrate($rows));
    }

    /**
     * Default POS product grid (no search / no category filter).
     *
     * @return Collection<int, Product>
     */
    public static function defaultProducts(): Collection
    {
        $ids = Cache::remember('pos.products.default_ids', 300, function (): array {
            return Product::query()
                ->published()
                ->orderBy('name')
                ->limit(120)
                ->pluck('id')
                ->all();
        });

        if ($ids === []) {
            return new Collection;
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->with(['category:id,name'])
            ->withCount(['activeVariants', 'activeAddons'])
            ->orderBy('name')
            ->get();
    }

    public static function forget(): void
    {
        Cache::forget('pos.categories');
        Cache::forget('pos.categories.rows');
        Cache::forget('pos.products.default');
        Cache::forget('pos.products.default_ids');
        StorefrontCache::forgetHome();
    }
}
