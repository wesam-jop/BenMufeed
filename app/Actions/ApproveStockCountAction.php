<?php

namespace App\Actions;

use App\Models\StockCount;
use App\Models\User;
use App\Services\Shared\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveStockCountAction
{
    public function __construct(
        private StockService $stockService,
        private AuditService $auditService,
    ) {}

    public function execute(StockCount $stockCount, User $approver): StockCount
    {
        return DB::transaction(function () use ($stockCount, $approver) {
            if ($stockCount->status !== 'draft') {
                throw new RuntimeException('Only draft stock counts can be approved.');
            }

            $stockCount->loadMissing('items.product');

            foreach ($stockCount->items as $item) {
                $difference = (float) $item->actual_qty - (float) $item->system_qty;

                if ($difference == 0.0) {
                    continue;
                }

                $item->update(['difference' => $difference]);

                $this->stockService->adjustStock(
                    warehouseId: $stockCount->warehouse_id,
                    productId: $item->product_id,
                    newQuantity: (float) $item->actual_qty,
                    reason: 'Stock count adjustment',
                    reference: $stockCount,
                    user: $approver,
                );
            }

            $stockCount->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'counted_at' => $stockCount->counted_at ?? now(),
            ]);

            $this->auditService->log(
                event: 'stock_count.approved',
                auditable: $stockCount,
                newValues: [
                    'warehouse_id' => $stockCount->warehouse_id,
                    'items_count' => $stockCount->items->count(),
                ],
                user: $approver,
            );

            return $stockCount->fresh(['items']);
        });
    }
}
