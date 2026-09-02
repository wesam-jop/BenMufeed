<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\ProductType;
use App\Http\Requests\Admin\Inventory\StockTransfer\StoreStockTransferRequest;
use App\Http\Requests\Admin\Inventory\StockTransfer\UpdateStockTransferRequest;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends AdminController
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockTransfer::class);

        $stockTransfers = $this->forBranchThrough(StockTransfer::query(), $request, 'fromWarehouse')
            ->with([
                'fromWarehouse:id,name',
                'toWarehouse:id,name',
                'user:id,name',
            ])
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stockTransfers->through(fn (StockTransfer $transfer) => $this->transformSummary($transfer));

        return Inertia::render('admin/stock-transfers/index', [
            'stockTransfers' => $stockTransfers,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', StockTransfer::class);

        return Inertia::render('admin/stock-transfers/create', [
            'warehouses' => $this->forBranch(Warehouse::query(), $request)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreStockTransferRequest $request): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $transfer = StockTransfer::create([
            ...$request->validated(),
            'status' => 'draft',
            'user_id' => $request->user()->id,
        ]);

        return to_route('admin.stock-transfers.show', $transfer)
            ->with('toast', ['type' => 'success', 'message' => __('toast.transfer_created')]);
    }

    public function show(StockTransfer $stockTransfer): Response
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load([
            'fromWarehouse:id,name',
            'toWarehouse:id,name',
            'user:id,name',
            'approver:id,name',
            'items.product.unit:id,name,abbreviation',
        ]);

        return Inertia::render('admin/stock-transfers/show', [
            'stockTransfer' => $this->transformDetail($stockTransfer),
            'availableMaterials' => $this->availableMaterials($stockTransfer->from_warehouse_id),
        ]);
    }

    public function edit(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('update', $stockTransfer);

        return to_route('admin.stock-transfers.show', $stockTransfer);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('update', $stockTransfer);

        abort_unless($stockTransfer->status === 'draft', 422, 'لا يمكن تعديل تحويل منفّذ.');

        DB::transaction(function () use ($request, $stockTransfer) {
            $this->syncItems($stockTransfer, $request->validated('items'));
        });

        return to_route('admin.stock-transfers.show', $stockTransfer)
            ->with('toast', ['type' => 'success', 'message' => __('toast.transfer_items_saved')]);
    }

    public function execute(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('update', $stockTransfer);

        abort_unless($stockTransfer->status === 'draft', 422, 'تم تنفيذ هذا التحويل مسبقاً.');

        $stockTransfer->loadMissing('items.product');

        if ($stockTransfer->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'أضف مادة واحدة على الأقل قبل تنفيذ التحويل.',
            ]);
        }

        $this->validateAvailableQuantities($stockTransfer);

        $this->stockService->transfer($stockTransfer, $request->user());

        return to_route('admin.stock-transfers.show', $stockTransfer)
            ->with('toast', ['type' => 'success', 'message' => __('toast.transfer_completed')]);
    }

    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        $this->authorize('delete', $stockTransfer);

        abort_unless($stockTransfer->status === 'draft', 422, 'لا يمكن حذف تحويل منفّذ.');

        $stockTransfer->delete();

        return to_route('admin.stock-transfers.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.transfer_deleted')]);
    }

    /**
     * @param  array<int, array{id?: int, product_id: int, quantity: float|int|string}>  $items
     */
    private function syncItems(StockTransfer $stockTransfer, array $items): void
    {
        $keepIds = [];

        foreach ($items as $itemData) {
            if (! empty($itemData['id'])) {
                $item = StockTransferItem::query()
                    ->where('stock_transfer_id', $stockTransfer->id)
                    ->findOrFail($itemData['id']);

                $item->update([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                ]);

                $keepIds[] = $item->id;

                continue;
            }

            $item = StockTransferItem::create([
                'stock_transfer_id' => $stockTransfer->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
            ]);

            $keepIds[] = $item->id;
        }

        StockTransferItem::query()
            ->where('stock_transfer_id', $stockTransfer->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    private function validateAvailableQuantities(StockTransfer $stockTransfer): void
    {
        $balances = StockBalance::query()
            ->where('warehouse_id', $stockTransfer->from_warehouse_id)
            ->whereIn('product_id', $stockTransfer->items->pluck('product_id'))
            ->pluck('quantity', 'product_id');

        foreach ($stockTransfer->items as $item) {
            $available = (float) ($balances[$item->product_id] ?? 0);
            $requested = (float) $item->quantity;

            if ($requested > $available) {
                throw ValidationException::withMessages([
                    'items' => "الكمية المطلوبة من {$item->product->name} ({$requested}) أكبر من المتاح ({$available}).",
                ]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function availableMaterials(int $warehouseId): array
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        return Product::query()
            ->where('branch_id', $warehouse->branch_id)
            ->whereIn('type', [ProductType::Raw, ProductType::SemiFinished])
            ->where('track_stock', true)
            ->where('is_active', true)
            ->with('unit:id,name,abbreviation')
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'unit_id'])
            ->map(function (Product $product) use ($warehouseId) {
                $availableQty = (float) (StockBalance::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->value('quantity') ?? 0);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'cost_price' => (float) $product->cost_price,
                    'available_qty' => $availableQty,
                    'unit' => $product->unit ? [
                        'name' => $product->unit->name,
                        'abbreviation' => $product->unit->abbreviation,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function transformSummary(StockTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'reference' => $this->reference($transfer),
            'status' => $transfer->status,
            'status_label' => $this->statusLabel($transfer->status),
            'from_warehouse_name' => $transfer->fromWarehouse?->name ?? '—',
            'to_warehouse_name' => $transfer->toWarehouse?->name ?? '—',
            'items_count' => (int) $transfer->items_count,
            'user_name' => $transfer->user?->name,
            'created_at' => $transfer->created_at?->toIso8601String(),
            'can_edit' => $transfer->status === 'draft',
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(StockTransfer $transfer): array
    {
        $items = $transfer->items->map(function (StockTransferItem $item) use ($transfer) {
            $availableQty = (float) (StockBalance::query()
                ->where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $item->product_id)
                ->value('quantity') ?? 0);

            $costPrice = (float) ($item->product?->cost_price ?? 0);
            $quantity = (float) $item->quantity;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '—',
                'product_sku' => $item->product?->sku,
                'unit' => $item->product?->unit ? [
                    'name' => $item->product->unit->name,
                    'abbreviation' => $item->product->unit->abbreviation,
                ] : null,
                'quantity' => $quantity,
                'available_qty' => $availableQty,
                'line_value' => round($quantity * $costPrice, 4),
            ];
        })->values()->all();

        return [
            'id' => $transfer->id,
            'reference' => $this->reference($transfer),
            'status' => $transfer->status,
            'status_label' => $this->statusLabel($transfer->status),
            'from_warehouse_name' => $transfer->fromWarehouse?->name ?? '—',
            'to_warehouse_name' => $transfer->toWarehouse?->name ?? '—',
            'user_name' => $transfer->user?->name,
            'approver_name' => $transfer->approver?->name,
            'created_at' => $transfer->created_at?->toIso8601String(),
            'can_edit' => $transfer->status === 'draft',
            'items' => $items,
            'summary' => [
                'items_count' => count($items),
                'total_quantity' => round(collect($items)->sum('quantity'), 4),
                'total_value' => round(collect($items)->sum('line_value'), 2),
            ],
        ];
    }

    private function reference(StockTransfer $transfer): string
    {
        return 'TRF-'.str_pad((string) $transfer->id, 4, '0', STR_PAD_LEFT);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'مسودة',
            'completed' => 'منفّذ',
            default => $status,
        };
    }
}
