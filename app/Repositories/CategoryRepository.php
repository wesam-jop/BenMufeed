<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function allForNavigation(): Collection
    {
        return Category::query()
            ->with(['media'])
            ->withCount(['products as published_products_count' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::query()->where('slug', $slug)->with(['media'])->first();
    }

    public function paginateForStorefront(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return Category::query()
            ->with(['media'])
            ->withCount(['products as published_products_count' => function ($q) {
                $q->published();
            }])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
