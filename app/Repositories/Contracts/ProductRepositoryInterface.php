<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * منتجات عشوائية للصفحة الرئيسية (تتغير عند كل طلب / ريفرش).
     *
     * @return Collection<int, Product>
     */
    public function randomPublishedForHome(int $limit = 4): Collection;

    public function findPublishedBySlug(string $slug): ?Product;

    /**
     * @return Collection<int, Product>
     */
    public function similarPublished(Product $product, int $limit = 4): Collection;

    public function paginatePublishedInCategory(
        Category $category,
        ?string $search,
        string $sort,
        int $perPage = 12
    ): LengthAwarePaginator;

    public function paginateAllPublished(
        ?string $search,
        string $sort,
        ?int $categoryId = null,
        int $perPage = 24,
        int $page = 1,
    ): LengthAwarePaginator;
}
