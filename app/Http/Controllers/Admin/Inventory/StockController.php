<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\StockMovementType;
use App\Http\Requests\Admin\Inventory\Stock\AdjustStockRequest;
use App\Models\StockMovement;
use App\Services\Inventory\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends AdminController
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        $baseQuery = $this->movementsQuery($request);
        $summary = $this->buildSummary(clone $baseQuery);

        $movements = (clone $baseQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $movements->through(fn (StockMovement $movement) => $this->transformMovement($movement));

        return Inertia::render('admin/inventory/movements', [
            'movements' => $movements,
            'summary' => $summary,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
            ],
            'typeOptions' => collect(StockMovementType::cases())->map(fn (StockMovementType $type) => [
                'value' => $type->value,
                'label' => $this->typeLabel($type),
            ])->values()->all(),
        ]);
    }

    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $this->authorize('adjust', StockMovement::class);

        $this->stockService->recordMovement([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('toast.stock_adjusted')]);
    }

    /** @return Builder<StockMovement> */
    private function movementsQuery(Request $request): Builder
    {
        $query = $this->forBranch(
            StockMovement::query()->with([
                'warehouse:id,name',
                'product:id,name,sku,unit_id',
                'product.unit:id,name,abbreviation',
                'user:id,name',
            ]),
            $request,
        );

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->whereHas('product', fn (Builder $productQuery) => $productQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function transformMovement(StockMovement $movement): array
    {
        $type = $movement->type->value;

        return [
            'id' => $movement->id,
            'type' => $type,
            'type_label' => $this->typeLabel($movement->type),
            'direction' => $this->direction($movement->type),
            'product_name' => $movement->product?->name ?? '—',
            'product_sku' => $movement->product?->sku,
            'unit' => $movement->product?->unit ? [
                'name' => $movement->product->unit->name,
                'abbreviation' => $movement->product->unit->abbreviation,
            ] : null,
            'warehouse_name' => $movement->warehouse?->name ?? '—',
            'user_name' => $movement->user?->name,
            'quantity' => (float) $movement->quantity,
            'quantity_before' => $movement->quantity_before !== null ? (float) $movement->quantity_before : null,
            'quantity_after' => $movement->quantity_after !== null ? (float) $movement->quantity_after : null,
            'unit_cost' => $movement->unit_cost !== null ? (float) $movement->unit_cost : null,
            'total_cost' => $movement->total_cost !== null ? (float) $movement->total_cost : null,
            'notes' => $movement->notes,
            'reason' => $movement->reason,
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     movements_count: int,
     *     inbound_count: int,
     *     outbound_count: int,
     *     total_value: float
     * }
     */
    private function buildSummary(Builder $query): array
    {
        $movements = $query->get(['type', 'total_cost']);

        $inboundCount = 0;
        $outboundCount = 0;
        $totalValue = 0.0;

        foreach ($movements as $movement) {
            $direction = $this->direction($movement->type);

            if ($direction === 'in') {
                $inboundCount++;
            } elseif ($direction === 'out') {
                $outboundCount++;
            }

            $totalValue += abs((float) ($movement->total_cost ?? 0));
        }

        return [
            'movements_count' => $movements->count(),
            'inbound_count' => $inboundCount,
            'outbound_count' => $outboundCount,
            'total_value' => round($totalValue, 2),
        ];
    }

    private function typeLabel(StockMovementType $type): string
    {
        return match ($type) {
            StockMovementType::In => 'إدخال',
            StockMovementType::Out => 'إخراج',
            StockMovementType::Adjustment => 'تعديل',
            StockMovementType::TransferIn => 'تحويل وارد',
            StockMovementType::TransferOut => 'تحويل صادر',
            StockMovementType::ProductionIn => 'إنتاج وارد',
            StockMovementType::ProductionOut => 'إنتاج صادر',
            StockMovementType::Wastage => 'هدر',
            StockMovementType::ReturnIn => 'مرتجع وارد',
            StockMovementType::ReturnOut => 'مرتجع صادر',
            StockMovementType::OpeningBalance => 'رصيد افتتاحي',
        };
    }

    private function direction(StockMovementType $type): string
    {
        return match ($type) {
            StockMovementType::In,
            StockMovementType::TransferIn,
            StockMovementType::ProductionIn,
            StockMovementType::ReturnIn,
            StockMovementType::OpeningBalance => 'in',
            StockMovementType::Out,
            StockMovementType::TransferOut,
            StockMovementType::ProductionOut,
            StockMovementType::Wastage,
            StockMovementType::ReturnOut => 'out',
            StockMovementType::Adjustment => 'neutral',
        };
    }
}
