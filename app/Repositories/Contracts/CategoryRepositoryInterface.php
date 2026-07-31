<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function allForNavigation(): Collection;

    public function findBySlug(string $slug): ?Category;

    public function paginateForStorefront(int $perPage = 15, int $page = 1): LengthAwarePaginator;
}
