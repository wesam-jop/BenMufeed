<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Actions\ApproveStockCountAction;
use App\Enums\ProductType;
use App\Http\Requests\Admin\Inventory\StockCount\StoreStockCountRequest;
use App\Http\Requests\Admin\Inventory\StockCount\UpdateStockCountRequest;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockCountController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockCount::class);

        $stockCounts = $this->forBranchThrough(StockCount::query(), $request, 'warehouse')
            ->with([
                'warehouse:id,name',
                'approver:id,name',
            ])
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stockCounts->through(fn (StockCount $stockCount) => $this->transformSummary($stockCount));

        return Inertia::render('admin/stock-counts/index', [
            'stockCounts' => $stockCounts,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', StockCount::class);

        return Inertia::render('admin/stock-counts/create', [
            'warehouses' => $this->forBranch(Warehouse::query(), $request)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreStockCountRequest $request): RedirectResponse
    {
        $this->authorize('create', StockCount::class);

        $stockCount = DB::transaction(function () use ($request) {
            $stockCount = StockCount::create([
                'warehouse_id' => $request->validated('warehouse_id'),
                'status' => 'draft',
            ]);

            $this->seedCountItems($stockCount);

            return $stockCount;
        });

        return to_route('admin.stock-counts.show', $stockCount)
            ->with('toast', ['type' => 'success', 'message' => __('toast.stock_count_started')]);
    }

    public function show(StockCount $stockCount): Response
    {
        $this->authorize('view', $stockCount);

        $stockCount->load([
            'warehouse:id,name',
            'approver:id,name',
            'items.product.unit:id,name,abbreviation',
        ]);

        return Inertia::render('admin/stock-counts/show', [
            'stockCount' => $this->transformDetail($stockCount),
        ]);
    }

    public function edit(StockCount $stockCount): RedirectResponse
    {
        $this->authorize('update', $stockCount);

        return to_route('admin.stock-counts.show', $stockCount);
    }

    public function update(UpdateStockCountRequest $request, StockCount $stockCount): RedirectResponse
    {
        $this->authorize('update', $stockCount);

        abort_unless($stockCount->status === 'draft', 422, 'لا يمكن تعديل جرد معتمد.');

        DB::transaction(function () use ($request, $stockCount) {
            foreach ($request->validated('items') as $itemData) {
                $item = StockCountItem::query()
                    ->where('stock_count_id', $stockCount->id)
                    ->findOrFail($itemData['id']);

                $actualQty = (float) $itemData['actual_qty'];
                $difference = $actualQty - (float) $item->system_qty;

                $item->update([
                    'actual_qty' => $actualQty,
                    'difference' => $difference,
                ]);
            }
        });

        return to_route('admin.stock-counts.show', $stockCount)
            ->with('toast', ['type' => 'success', 'message' => __('toast.stock_count_saved')]);
    }

    public function approve(Request $request, StockCount $stockCount, ApproveStockCountAction $approveStockCount): RedirectResponse
    {
        $this->authorize('update', $stockCount);

        $approveStockCount->execute($stockCount, $request->user());

        return to_route('admin.stock-counts.show', $stockCount)
            ->with('toast', ['type' => 'success', 'message' => __('toast.stock_count_approved')]);
    }

    public function destroy(StockCount $stockCount): RedirectResponse
    {
        $this->authorize('delete', $stockCount);

        abort_unless($stockCount->status === 'draft', 422, 'لا يمكن حذف جرد معتمد.');

        $stockCount->delete();

        return to_route('admin.stock-counts.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.stock_count_deleted')]);
    }

    private function seedCountItems(StockCount $stockCount): void
    {
        $warehouse = Warehouse::query()->findOrFail($stockCount->warehouse_id);

        $materials = Product::query()
            ->where('branch_id', $warehouse->branch_id)
            ->whereIn('type', [ProductType::Raw, ProductType::SemiFinished])
            ->where('track_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id']);

        $balances = StockBalance::query()
            ->where('warehouse_id', $stockCount->warehouse_id)
            ->whereIn('product_id', $materials->pluck('id'))
            ->pluck('quantity', 'product_id');

        foreach ($materials as $material) {
            $systemQty = (float) ($balances[$material->id] ?? 0);

            StockCountItem::create([
                'stock_count_id' => $stockCount->id,
                'product_id' => $material->id,
                'system_qty' => $systemQty,
                'actual_qty' => $systemQty,
                'difference' => 0,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function transformSummary(StockCount $stockCount): array
    {
        return [
            'id' => $stockCount->id,
            'reference' => $this->reference($stockCount),
            'status' => $stockCount->status,
            'status_label' => $this->statusLabel($stockCount->status),
            'warehouse_name' => $stockCount->warehouse?->name ?? '—',
            'items_count' => (int) $stockCount->items_count,
            'approver_name' => $stockCount->approver?->name,
            'counted_at' => $stockCount->counted_at?->toIso8601String(),
            'created_at' => $stockCount->created_at?->toIso8601String(),
            'can_edit' => $stockCount->status === 'draft',
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(StockCount $stockCount): array
    {
        $items = $stockCount->items->map(function (StockCountItem $item) {
            $systemQty = (float) $item->system_qty;
            $actualQty = (float) $item->actual_qty;
            $difference = $actualQty - $systemQty;
            $costPrice = (float) ($item->product?->cost_price ?? 0);

            return [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? '—',
                'product_sku' => $item->product?->sku,
                'unit' => $item->product?->unit ? [
                    'name' => $item->product->unit->name,
                    'abbreviation' => $item->product->unit->abbreviation,
                ] : null,
                'cost_price' => $costPrice,
                'system_qty' => $systemQty,
                'actual_qty' => $actualQty,
                'difference' => $difference,
                'variance_value' => round($difference * $costPrice, 4),
            ];
        })->values()->all();

        $differencesCount = collect($items)->filter(fn (array $item) => $item['difference'] != 0.0)->count();
        $totalVarianceValue = round(collect($items)->sum('variance_value'), 2);

        return [
            'id' => $stockCount->id,
            'reference' => $this->reference($stockCount),
            'status' => $stockCount->status,
            'status_label' => $this->statusLabel($stockCount->status),
            'warehouse_name' => $stockCount->warehouse?->name ?? '—',
            'approver_name' => $stockCount->approver?->name,
            'counted_at' => $stockCount->counted_at?->toIso8601String(),
            'created_at' => $stockCount->created_at?->toIso8601String(),
            'can_edit' => $stockCount->status === 'draft',
            'items' => $items,
            'summary' => [
                'items_count' => count($items),
                'differences_count' => $differencesCount,
                'matched_count' => count($items) - $differencesCount,
                'total_variance_value' => $totalVarianceValue,
            ],
        ];
    }

    private function reference(StockCount $stockCount): string
    {
        return 'JRD-'.str_pad((string) $stockCount->id, 4, '0', STR_PAD_LEFT);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'مسودة',
            'approved' => 'معتمد',
            'completed' => 'مكتمل',
            default => $status,
        };
    }
}
