<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\ProductType;
use App\Http\Requests\Admin\Catalog\Product\StoreProductRequest;
use App\Http\Requests\Admin\Catalog\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Catalog\ProductRecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends AdminController
{
    public function __construct(private ProductRecipeService $recipeService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->forBranchNullable(Product::query(), $request)
            ->whereIn('type', [ProductType::Finished, ProductType::Combo, ProductType::Service])
            ->with('category:id,name')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'sale_price' => (float) $product->sale_price,
                'is_active' => (bool) $product->is_active,
                'image' => $product->image,
                'image_url' => $product->image_url,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
            ]);

        return Inertia::render('admin/products/index', [
            'product' => $products,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('admin/products/create', [
            'categories' => $this->categories($request),
            'materials' => $this->materials($request),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->prepareProductData($request);
        $recipeItems = $request->validated('recipe_items') ?? [];

        DB::transaction(function () use ($data, $recipeItems) {
            $product = Product::create($data);

            if ($recipeItems !== []) {
                $cost = $this->recipeService->syncRecipe($product, $recipeItems);
                $product->update(['cost_price' => $cost]);
            }
        });

        return to_route('admin.products.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.product_created')]);
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

        return Inertia::render('admin/products/show', [
            'product' => $product,
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'sale_price' => (float) $product->sale_price,
                'cost_price' => (float) $product->cost_price,
                'image_url' => $product->image_url,
            ],
            'categories' => $this->categories($request),
            'materials' => $this->materials($request),
            'recipe_items' => $this->recipeService->recipeItemsForProduct($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $this->prepareProductData($request, $product);
        $recipeItems = $request->validated('recipe_items') ?? [];

        DB::transaction(function () use ($product, $data, $recipeItems) {
            $product->update($data);

            if ($recipeItems !== []) {
                $cost = $this->recipeService->syncRecipe($product, $recipeItems);
                $product->update(['cost_price' => $cost]);
            }
        });

        return to_route('admin.products.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.product_updated')]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return to_route('admin.products.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.deleted')]);
    }

    /** @return list<array{id: int, name: string}> */
    private function categories(Request $request): array
    {
        return $this->forBranchNullable(ProductCategory::query(), $request)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function materials(Request $request): array
    {
        return $this->forBranchNullable(Product::query(), $request)
            ->whereIn('type', [ProductType::Raw, ProductType::SemiFinished])
            ->where('is_active', true)
            ->with('unit:id,name,abbreviation')
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'unit_id'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'cost_price' => (float) $product->cost_price,
                'unit' => $product->unit ? [
                    'name' => $product->unit->name,
                    'abbreviation' => $product->unit->abbreviation,
                ] : null,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function prepareProductData(StoreProductRequest|UpdateProductRequest $request, ?Product $existing = null): array
    {
        $data = $request->safe()->except(['image', 'recipe_items']);
        $data['branch_id'] = $this->branchId($request) ?? $request->user()->branch_id;
        $data['type'] = ProductType::Finished;
        $data['track_stock'] = false;
        $data['is_active'] = true;

        if ($request->hasFile('image')) {
            if ($existing?->image) {
                Storage::disk('public')->delete($existing->image);
            }

            $data['image'] = $request->file('image')->store('products', 'public');
        }

        return $data;
    }
}
