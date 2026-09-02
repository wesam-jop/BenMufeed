<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Actions\RecordWastageAction;
use App\Enums\ProductType;
use App\Enums\WastageReason;
use App\Http\Requests\Admin\Inventory\WastageRecord\StoreWastageRecordRequest;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\WastageRecord;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WastageController extends AdminController
{
    public function __construct(
        private StockService $stockService,
        private RecordWastageAction $recordWastageAction,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WastageRecord::class);

        $baseQuery = $this->forBranchThrough(
            WastageRecord::query()->with(['warehouse:id,name', 'product.unit', 'user:id,name']),
            $request,
            'warehouse',
        );

        $summaryQuery = (clone $baseQuery)->with('product:id,cost_price');

        $wastageRecords = (clone $baseQuery)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $wastageRecords->through(fn (WastageRecord $record) => $this->transformSummary($record));

        return Inertia::render('admin/wastage-records/index', [
            'wastageRecords' => $wastageRecords,
            'summary' => $this->buildSummary($summaryQuery),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', WastageRecord::class);

        $warehouseId = $request->integer('warehouse_id') ?: null;

        return Inertia::render('admin/wastage-records/create', [
            'warehouses' => $this->forBranch(Warehouse::query(), $request)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'materials' => $warehouseId ? $this->availableMaterials($warehouseId) : [],
            'reasonOptions' => $this->reasonOptions(),
            'selectedWarehouseId' => $warehouseId,
        ]);
    }

    public function store(StoreWastageRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', WastageRecord::class);

        $record = $this->recordWastageAction->execute($request->validated(), $request->user());

        return to_route('admin.wastage-records.show', $record)
            ->with('toast', ['type' => 'success', 'message' => __('toast.wastage_recorded')]);
    }

    public function show(WastageRecord $wastageRecord): Response
    {
        $this->authorize('view', $wastageRecord);

        $wastageRecord->load([
            'warehouse:id,name',
            'product.unit',
            'user:id,name',
            'stockMovement',
        ]);

        return Inertia::render('admin/wastage-records/show', [
            'wastageRecord' => $this->transformDetail($wastageRecord),
        ]);
    }

    public function edit(WastageRecord $wastageRecord): RedirectResponse
    {
        $this->authorize('view', $wastageRecord);

        return to_route('admin.wastage-records.show', $wastageRecord);
    }

    public function update(Request $request, WastageRecord $wastageRecord): RedirectResponse
    {
        abort(405, 'لا يمكن تعديل سجل هدر بعد خصم المخزون.');
    }

    public function destroy(WastageRecord $wastageRecord): RedirectResponse
    {
        $this->authorize('delete', $wastageRecord);

        DB::transaction(function () use ($wastageRecord) {
            if ($wastageRecord->stock_movement_id !== null) {
                $currentQty = (float) ($this->stockService->getBalance(
                    $wastageRecord->warehouse_id,
                    $wastageRecord->product_id,
                )?->quantity ?? 0);

                $this->stockService->adjustStock(
                    warehouseId: $wastageRecord->warehouse_id,
                    productId: $wastageRecord->product_id,
                    newQuantity: $currentQty + (float) $wastageRecord->quantity,
                    reason: 'إلغاء سجل هدر',
                    reference: $wastageRecord,
                    user: auth()->user(),
                );
            }

            $wastageRecord->delete();
        });

        return to_route('admin.wastage-records.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.wastage_deleted')]);
    }

    /** @return list<array{value: string, label: string}> */
    private function reasonOptions(): array
    {
        return collect(WastageReason::cases())
            ->map(fn (WastageReason $reason) => [
                'value' => $reason->value,
                'label' => $this->reasonLabel($reason),
            ])
            ->values()
            ->all();
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

    /** @param  \Illuminate\Database\Eloquent\Builder<WastageRecord>  $query */
    private function buildSummary($query): array
    {
        $records = $query->get();

        $totalValue = $records->sum(function (WastageRecord $record) {
            $costPrice = (float) ($record->product?->cost_price ?? 0);

            return (float) $record->quantity * $costPrice;
        });

        $thisMonthValue = $records
            ->filter(fn (WastageRecord $record) => $record->created_at?->isCurrentMonth())
            ->sum(function (WastageRecord $record) {
                $costPrice = (float) ($record->product?->cost_price ?? 0);

                return (float) $record->quantity * $costPrice;
            });

        return [
            'records_count' => $records->count(),
            'total_value' => round($totalValue, 2),
            'this_month_value' => round($thisMonthValue, 2),
            'this_month_count' => $records->filter(
                fn (WastageRecord $record) => $record->created_at?->isCurrentMonth(),
            )->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(WastageRecord $record): array
    {
        $costPrice = (float) ($record->product?->cost_price ?? 0);
        $quantity = (float) $record->quantity;

        return [
            'id' => $record->id,
            'reference' => $this->reference($record),
            'warehouse_name' => $record->warehouse?->name ?? '—',
            'product_name' => $record->product?->name ?? '—',
            'product_sku' => $record->product?->sku,
            'unit' => $record->product?->unit ? [
                'name' => $record->product->unit->name,
                'abbreviation' => $record->product->unit->abbreviation,
            ] : null,
            'quantity' => $quantity,
            'reason' => $record->reason->value,
            'reason_label' => $this->reasonLabel($record->reason),
            'line_value' => round($quantity * $costPrice, 2),
            'user_name' => $record->user?->name,
            'created_at' => $record->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(WastageRecord $record): array
    {
        $summary = $this->transformSummary($record);
        $costPrice = (float) ($record->product?->cost_price ?? 0);

        return [
            ...$summary,
            'notes' => $record->notes,
            'stock_movement_id' => $record->stock_movement_id,
            'unit_cost' => $costPrice,
        ];
    }

    private function reference(WastageRecord $record): string
    {
        return 'WST-'.str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
    }

    private function reasonLabel(WastageReason $reason): string
    {
        return match ($reason) {
            WastageReason::Expired => 'منتهي الصلاحية',
            WastageReason::Damaged => 'تالف',
            WastageReason::Spillage => 'انسكاب',
            WastageReason::Overproduction => 'إنتاج زائد',
            WastageReason::Theft => 'سرقة',
            WastageReason::Other => 'أخرى',
        };
    }
}
