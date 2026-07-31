<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Http\Request;

class ProductCatalogQuery
{
    private ?int $resolvedCategoryId = null;

    private bool $categoryResolved = false;

    public function __construct(
        public readonly ?string $q,
        public readonly string $sort,
        public readonly ?string $categorySlug,
        public readonly int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->query('sort', 'latest');
        if (! in_array($sort, ['latest', 'oldest', 'price_asc', 'price_desc'], true)) {
            $sort = 'latest';
        }

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $category = $request->query('category');
        $category = is_string($category) && $category !== '' ? $category : null;

        return new self(
            q: $q !== '' ? $q : null,
            sort: $sort,
            categorySlug: $category,
            page: max(1, (int) $request->query('page', 1)),
        );
    }

    public function categoryId(): ?int
    {
        if ($this->categoryResolved) {
            return $this->resolvedCategoryId;
        }

        $this->categoryResolved = true;

        if ($this->categorySlug === null) {
            return null;
        }

        $id = Category::query()->where('slug', $this->categorySlug)->value('id');

        $this->resolvedCategoryId = $id ? (int) $id : null;

        return $this->resolvedCategoryId;
    }

    public function hasValidCategory(): bool
    {
        if ($this->categorySlug === null) {
            return true;
        }

        return $this->categoryId() !== null;
    }

    /**
     * @return array{q: string, sort: string, category: string}
     */
    public function toFilterArray(): array
    {
        return [
            'q' => $this->q ?? '',
            'sort' => $this->sort,
            'category' => $this->categorySlug ?? '',
        ];
    }

    /**
     * @return array<string, string|int>
     */
    public function toQueryParams(): array
    {
        return array_filter([
            'q' => $this->q,
            'sort' => $this->sort,
            'category' => $this->categorySlug,
            'page' => $this->page > 1 ? $this->page : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
