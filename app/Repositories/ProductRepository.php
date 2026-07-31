<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ProductRepository implements ProductRepositoryInterface
{
    public function randomPublishedForHome(int $limit = 4): Collection
    {
        $query = $this->storefrontCardQuery(
            Product::query()->published(),
        );

        $featured = (clone $query)
            ->where('is_featured', true)
            ->latest()
            ->limit($limit)
            ->get();

        if ($featured->count() >= $limit) {
            return $featured;
        }

        $remaining = $limit - $featured->count();

        if ($remaining <= 0) {
            return $featured;
        }

        $excludeIds = $featured->modelKeys();

        $more = $this->storefrontCardQuery(
            Product::query()->published(),
        )
            ->when($excludeIds !== [], fn (Builder $builder) => $builder->whereKeyNot($excludeIds))
            ->latest()
            ->limit($remaining)
            ->get();

        return $featured->concat($more);
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return $this->storefrontCardQuery(
            Product::query()->published()->where('slug', $slug),
        )
            ->first();
    }

    public function similarPublished(Product $product, int $limit = 4): Collection
    {
        return $this->storefrontCardQuery(
            Product::query()
                ->published()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey()),
        )
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginatePublishedInCategory(
        Category $category,
        ?string $search,
        string $sort,
        int $perPage = 12
    ): LengthAwarePaginator {
        return $this->paginateAllPublished($search, $sort, $category->getKey(), $perPage);
    }

    public function paginateAllPublished(
        ?string $search,
        string $sort,
        ?int $categoryId = null,
        int $perPage = 24,
        int $page = 1,
    ): LengthAwarePaginator {
        $search = $search !== null ? trim($search) : '';

        if ($search !== '' && $this->scoutSearchAvailable()) {
            $scoutResult = $this->tryScoutPaginate($search, $sort, $categoryId, $perPage, $page);
            if ($scoutResult !== null) {
                return $scoutResult;
            }
        }

        $query = $this->storefrontCardQuery(Product::query()->published());

        $this->applyCategoryFilter($query, $categoryId);
        $this->applyDatabaseSearch($query, $search);
        $this->applySort($query, $sort);

        return $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();
    }

    protected function storefrontCardQuery(Builder $query): Builder
    {
        return $query
            ->withCount(['activeVariants', 'activeAddons'])
            ->with([
                'category',
                'media',
                'activeVariants' => function ($variants): void {
                    $variants->orderBy('sort_order')->orderBy('id');
                },
            ]);
    }

    protected function tryScoutPaginate(
        string $search,
        string $sort,
        ?int $categoryId,
        int $perPage,
        int $page,
    ): ?LengthAwarePaginator {
        try {
            $builder = Product::search($search);

            if ($categoryId !== null) {
                $builder->where('category_id', $categoryId);
            }

            return $builder
                ->query(function (Builder $query) use ($sort, $categoryId) {
                    $this->storefrontCardQuery($query->published());
                    $this->applyCategoryFilter($query, $categoryId);
                    $this->applySort($query, $sort);
                })
                ->paginate($perPage, 'page', $page)
                ->withQueryString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function applyCategoryFilter(Builder $query, ?int $categoryId): void
    {
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
    }

    protected function applyDatabaseSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $terms = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($terms === []) {
            return;
        }

        $query->where(function (Builder $outer) use ($terms) {
            foreach ($terms as $term) {
                $like = '%'.$term.'%';
                $outer->where(function (Builder $inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('short_description', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', $like));
                });
            }
        });
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };
    }

    protected function scoutSearchAvailable(): bool
    {
        $driver = config('scout.driver');

        return in_array($driver, ['meilisearch', 'algolia', 'typesense', 'database'], true);
    }
}
