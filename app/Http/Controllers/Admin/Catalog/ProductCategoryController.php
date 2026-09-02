<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Catalog\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Admin\Catalog\ProductCategory\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductCategory::class);

        $productCategorys = $this->forBranchNullable(ProductCategory::query(), $request)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProductCategory $category) => $this->transform($category));

        return Inertia::render('admin/product-categories/index', [
            'productCategory' => $productCategorys,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProductCategory::class);

        return Inertia::render('admin/product-categories/create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductCategory::class);

        ProductCategory::create($this->prepareCategoryData($request));

        return to_route('admin.product-categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.category_created')]);
    }

    public function show(ProductCategory $productCategory): Response
    {
        $this->authorize('view', $productCategory);

        return Inertia::render('admin/product-categories/show', [
            'productCategory' => $this->transform($productCategory),
        ]);
    }

    public function edit(ProductCategory $productCategory): Response
    {
        $this->authorize('update', $productCategory);

        return Inertia::render('admin/product-categories/edit', [
            'productCategory' => $this->transform($productCategory),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->authorize('update', $productCategory);

        $productCategory->update($this->prepareCategoryData($request, $productCategory));

        return to_route('admin.product-categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.category_updated')]);
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->authorize('delete', $productCategory);

        if ($productCategory->image) {
            Storage::disk('public')->delete($productCategory->image);
        }

        $productCategory->delete();

        return to_route('admin.product-categories.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.category_deleted')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareCategoryData(
        StoreProductCategoryRequest|UpdateProductCategoryRequest $request,
        ?ProductCategory $existing = null,
    ): array {
        $data = $request->safe()->except(['image', 'remove_image']);

        if (empty($data['slug']) && ! empty($data['name'])) {
            $base = Str::slug($data['name']) ?: 'category';
            $slug = $base;
            $counter = 1;

            while (
                ProductCategory::query()
                    ->where('slug', $slug)
                    ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                    ->exists()
            ) {
                $slug = $base.'-'.$counter++;
            }

            $data['slug'] = $slug;
        }

        if (! $existing) {
            $data['branch_id'] = $this->branchId($request) ?? $request->user()?->branch_id;
            $data['is_active'] = $request->boolean('is_active', true);
        } elseif ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->boolean('remove_image') && $existing?->image) {
            Storage::disk('public')->delete($existing->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($existing?->image) {
                Storage::disk('public')->delete($existing->image);
            }

            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ProductCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
            'is_active' => (bool) $category->is_active,
            'image' => $category->image,
            'image_url' => $category->image_url,
            'products_count' => $category->products_count ?? $category->products()->count(),
        ];
    }
}
