<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\WarehouseType;
use App\Http\Requests\Admin\Inventory\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Admin\Inventory\Warehouse\UpdateWarehouseRequest;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Warehouse::class);

        $baseQuery = $this->forBranch(Warehouse::query(), $request);

        $allWarehouses = (clone $baseQuery)->get();
        $summaryValueMap = $this->warehouseValueMap($allWarehouses->pluck('id')->all());

        $warehouses = (clone $baseQuery)
            ->withCount([
                'stockBalances as materials_count' => fn ($query) => $query->where('quantity', '>', 0),
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $valueMap = $this->warehouseValueMap($warehouses->getCollection()->pluck('id')->all());

        $warehouses->through(function (Warehouse $warehouse) use ($valueMap) {
            return $this->transformSummary($warehouse, $valueMap);
        });

        return Inertia::render('admin/warehouses/index', [
            'warehouses' => $warehouses,
            'summary' => $this->buildSummary($allWarehouses, $summaryValueMap),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Warehouse::class);

        return Inertia::render('admin/warehouses/create', [
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = Warehouse::create([
            ...$request->validated(),
            'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
        ]);

        return to_route('admin.warehouses.show', $warehouse)
            ->with('toast', ['type' => 'success', 'message' => __('toast.warehouse_created')]);
    }

    public function show(Warehouse $warehouse): Response
    {
        $this->authorize('view', $warehouse);

        $balances = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->with(['product.unit:id,name,abbreviation'])
            ->get()
            ->sortBy(fn (StockBalance $balance) => $balance->product?->name)
            ->values();

        $materials = $balances->map(function (StockBalance $balance) {
            $quantity = (float) $balance->quantity;
            $costPrice = (float) ($balance->product?->cost_price ?? $balance->avg_cost ?? 0);

            return [
                'id' => $balance->product_id,
                'name' => $balance->product?->name ?? '—',
                'sku' => $balance->product?->sku,
                'quantity' => $quantity,
                'unit_cost' => $costPrice,
                'line_value' => round($quantity * $costPrice, 2),
                'unit' => $balance->product?->unit ? [
                    'name' => $balance->product->unit->name,
                    'abbreviation' => $balance->product->unit->abbreviation,
                ] : null,
            ];
        })->values()->all();

        $totalValue = round(collect($materials)->sum('line_value'), 2);

        return Inertia::render('admin/warehouses/show', [
            'warehouse' => [
                ...$this->transformSummary($warehouse, [
                    $warehouse->id => [
                        'materials_count' => count($materials),
                        'total_value' => $totalValue,
                    ],
                ]),
                'materials' => $materials,
                'summary' => [
                    'materials_count' => count($materials),
                    'total_quantity' => round(collect($materials)->sum('quantity'), 4),
                    'total_value' => $totalValue,
                ],
            ],
        ]);
    }

    public function edit(Warehouse $warehouse): Response
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('admin/warehouses/edit', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'type' => $warehouse->type->value,
                'is_active' => $warehouse->is_active,
            ],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return to_route('admin.warehouses.show', $warehouse)
            ->with('toast', ['type' => 'success', 'message' => __('toast.warehouse_updated')]);
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $hasStock = $warehouse->stockBalances()->where('quantity', '>', 0)->exists();

        if ($hasStock) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.warehouse_has_stock'),
            ]);
        }

        $warehouse->delete();

        return to_route('admin.warehouses.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.warehouse_deleted')]);
    }

    /** @return list<array{value: string, label: string}> */
    private function typeOptions(): array
    {
        return collect(WarehouseType::cases())
            ->map(fn (WarehouseType $type) => [
                'value' => $type->value,
                'label' => $this->typeLabel($type),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $warehouseIds
     * @return array<int, array{materials_count: int, total_value: float}>
     */
    private function warehouseValueMap(array $warehouseIds): array
    {
        if ($warehouseIds === []) {
            return [];
        }

        return StockBalance::query()
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity', '>', 0)
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->groupBy('warehouse_id')
            ->selectRaw('warehouse_id')
            ->selectRaw('COUNT(*) as materials_count')
            ->selectRaw('SUM(stock_balances.quantity * products.cost_price) as total_value')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->warehouse_id => [
                    'materials_count' => (int) $row->materials_count,
                    'total_value' => round((float) $row->total_value, 2),
                ],
            ])
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Warehouse>  $warehouses
     * @param  array<int, array{materials_count: int, total_value: float}>  $valueMap
     * @return array<string, int|float>
     */
    private function buildSummary($warehouses, array $valueMap): array
    {
        return [
            'warehouses_count' => $warehouses->count(),
            'active_count' => $warehouses->where('is_active', true)->count(),
            'materials_count' => (int) collect($valueMap)->sum('materials_count'),
            'total_value' => round(collect($valueMap)->sum('total_value'), 2),
        ];
    }

    /**
     * @param  array<int, array{materials_count: int, total_value: float}>  $valueMap
     * @return array<string, mixed>
     */
    private function transformSummary(Warehouse $warehouse, array $valueMap): array
    {
        $stats = $valueMap[$warehouse->id] ?? [
            'materials_count' => (int) ($warehouse->materials_count ?? 0),
            'total_value' => 0.0,
        ];

        return [
            'id' => $warehouse->id,
            'name' => $warehouse->name,
            'type' => $warehouse->type->value,
            'type_label' => $this->typeLabel($warehouse->type),
            'is_active' => $warehouse->is_active,
            'materials_count' => $stats['materials_count'],
            'total_value' => $stats['total_value'],
            'created_at' => $warehouse->created_at?->toIso8601String(),
        ];
    }

    private function typeLabel(WarehouseType $type): string
    {
        return match ($type) {
            WarehouseType::Main => 'رئيسي',
            WarehouseType::Kitchen => 'مطبخ',
            WarehouseType::ColdStorage => 'تبريد',
            WarehouseType::Retail => 'بيع',
            WarehouseType::Production => 'إنتاج',
        };
    }
}
