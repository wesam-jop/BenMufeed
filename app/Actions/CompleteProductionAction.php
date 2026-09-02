<?php

namespace App\Actions;

use App\Enums\ProductionOrderStatus;
use App\Enums\StockMovementType;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\Shared\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompleteProductionAction
{
    public function __construct(
        private StockService $stockService,
        private AuditService $auditService,
    ) {}

    public function execute(
        ProductionOrder $order,
        ?float $actualQty = null,
        ?User $user = null,
    ): ProductionOrder {
        return DB::transaction(function () use ($order, $actualQty, $user) {
            $user ??= auth()->user();

            if (! in_array($order->status, [ProductionOrderStatus::Planned->value, ProductionOrderStatus::InProgress->value], true)) {
                throw new RuntimeException('Production order cannot be completed in its current status.');
            }

            $order->loadMissing(['items.product', 'product']);

            $producedQty = $actualQty ?? (float) $order->planned_qty;

            if ($producedQty <= 0) {
                throw new RuntimeException('Produced quantity must be greater than zero.');
            }

            $totalMaterialCost = 0.0;
            $scale = $producedQty / max((float) $order->planned_qty, 1);

            foreach ($order->items as $item) {
                $consumedQty = (float) ($item->actual_qty ?? $item->planned_qty) * $scale;
                $unitCost = (float) ($item->unit_cost ?: $item->product->cost_price);

                $this->stockService->recordMovement([
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::ProductionOut,
                    'quantity' => $consumedQty,
                    'unit_cost' => $unitCost,
                    'reference' => $order,
                    'user_id' => $user?->id,
                ]);

                $item->update(['actual_qty' => $consumedQty]);
                $totalMaterialCost += $consumedQty * $unitCost;
            }

            $unitCost = $producedQty > 0 ? $totalMaterialCost / $producedQty : 0;

            $this->stockService->recordMovement([
                'warehouse_id' => $order->warehouse_id,
                'product_id' => $order->product_id,
                'type' => StockMovementType::ProductionIn,
                'quantity' => $producedQty,
                'unit_cost' => $unitCost,
                'reference' => $order,
                'user_id' => $user?->id,
            ]);

            $order->update([
                'status' => ProductionOrderStatus::Completed->value,
                'actual_qty' => $producedQty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalMaterialCost,
                'produced_at' => now(),
            ]);

            $this->auditService->log(
                event: 'production.completed',
                auditable: $order,
                newValues: [
                    'order_number' => $order->order_number,
                    'actual_qty' => $producedQty,
                    'total_cost' => $totalMaterialCost,
                ],
                user: $user,
            );

            return $order->fresh(['items', 'product']);
        });
    }
}
