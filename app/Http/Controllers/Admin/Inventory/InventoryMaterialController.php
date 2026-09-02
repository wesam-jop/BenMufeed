<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\ProductType;
use App\Http\Requests\Admin\Inventory\StoreMaterialRequest;
use App\Http\Requests\Admin\Inventory\UpdateMaterialRequest;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryMaterialController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $baseQuery = $this->materialsQuery($request);

        $allMaterials = (clone $baseQuery)
            ->with('unit:id,name,abbreviation')
            ->withSum('stockBalances as stock_quantity', 'quantity')
            ->get();

        $summary = $this->buildSummary($allMaterials);

        $materials = (clone $baseQuery)
            ->with('unit:id,name,abbreviation')
            ->withSum('stockBalances as stock_quantity', 'quantity')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $materials->getCollection()->transform(fn (Product $product) => $this->transformMaterial($product));

        return Inertia::render('admin/inventory/materials/index', [
            'materials' => $materials,
            'summary' => $summary,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('admin/inventory/materials/create', [
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        Product::create([
            ...$request->validated(),
            'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
            'type' => ProductType::Raw,
            'track_stock' => true,
            'is_active' => true,
            'sale_price' => 0,
        ]);

        return to_route('admin.inventory.materials.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.material_created')]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        abort_unless(in_array($product->type, [ProductType::Raw, ProductType::SemiFinished], true), 404);

        return Inertia::render('admin/inventory/materials/edit', [
            'material' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'cost_price' => (float) $product->cost_price,
                'min_stock' => $product->min_stock !== null ? (float) $product->min_stock : null,
                'unit_id' => $product->unit_id,
                'description' => $product->description,
            ],
            'units' => Unit::query()->orderBy('name')->get(['id', 'name', 'abbreviation']),
        ]);
    }

    public function update(UpdateMaterialRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_unless(in_array($product->type, [ProductType::Raw, ProductType::SemiFinished], true), 404);

        $product->update($request->validated());

        return to_route('admin.inventory.materials.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.material_updated')]);
    }

    /** @return Builder<Product> */
    private function materialsQuery(Request $request): Builder
    {
        return $this->forBranchNullable(Product::query(), $request)
            ->whereIn('type', [ProductType::Raw, ProductType::SemiFinished]);
    }

    /** @return array<string, mixed> */
    private function transformMaterial(Product $product): array
    {
        $stockQuantity = (float) ($product->stock_quantity ?? 0);
        $costPrice = (float) $product->cost_price;
        $minStock = $product->min_stock !== null ? (float) $product->min_stock : null;
        $stockValue = round($stockQuantity * $costPrice, 4);

        $stockStatus = 'ok';
        if ($stockQuantity <= 0) {
            $stockStatus = 'out';
        } elseif ($minStock !== null && $stockQuantity <= $minStock) {
            $stockStatus = 'low';
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'type' => $product->type->value,
            'cost_price' => $costPrice,
            'min_stock' => $minStock,
            'unit' => $product->unit ? [
                'name' => $product->unit->name,
                'abbreviation' => $product->unit->abbreviation,
            ] : null,
            'stock_quantity' => $stockQuantity,
            'stock_value' => $stockValue,
            'stock_status' => $stockStatus,
            'is_active' => (bool) $product->is_active,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $materials
     * @return array{materials_count: int, total_value: float, low_stock_count: int, out_of_stock_count: int, in_stock_count: int}
     */
    private function buildSummary($materials): array
    {
        $totalValue = 0.0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $inStockCount = 0;

        foreach ($materials as $product) {
            $qty = (float) ($product->stock_quantity ?? 0);
            $cost = (float) $product->cost_price;
            $min = $product->min_stock !== null ? (float) $product->min_stock : null;

            $totalValue += $qty * $cost;

            if ($qty <= 0) {
                $outOfStockCount++;
            } elseif ($min !== null && $qty <= $min) {
                $lowStockCount++;
                $inStockCount++;
            } else {
                $inStockCount++;
            }
        }

        return [
            'materials_count' => $materials->count(),
            'total_value' => round($totalValue, 2),
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'in_stock_count' => $inStockCount,
        ];
    }
}
