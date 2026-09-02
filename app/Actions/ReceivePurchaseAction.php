<?php

namespace App\Actions;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Shared\AuditService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ReceivePurchaseAction
{
    public function __construct(
        private StockService $stockService,
        private AccountingService $accountingService,
        private AuditService $auditService,
    ) {}

    public function execute(PurchaseInvoice $invoice, ?User $user = null): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            $user ??= auth()->user();

            if ($invoice->status !== PurchaseInvoiceStatus::Draft) {
                throw new RuntimeException('Only draft purchase invoices can be received.');
            }

            if ($invoice->warehouse_id === null) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'يجب تحديد المستودع قبل استلام الشراء.',
                ]);
            }

            $invoice->loadMissing(['items.product', 'supplier']);

            if ($invoice->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'أضف مادة واحدة على الأقل قبل استلام الشراء.',
                ]);
            }

            foreach ($invoice->items as $item) {
                if (! $item->product?->track_stock) {
                    continue;
                }

                $this->stockService->receiveStock(
                    warehouseId: $invoice->warehouse_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    unitCost: (float) $item->unit_price,
                    reference: $invoice,
                    user: $user,
                    notes: "شراء — فاتورة {$invoice->invoice_number}",
                );

                $item->product->update([
                    'cost_price' => (float) $item->unit_price,
                ]);
            }

            $invoice->update([
                'status' => PurchaseInvoiceStatus::Posted,
                'balance' => (float) $invoice->total - (float) $invoice->paid,
            ]);

            if ($invoice->supplier !== null) {
                $invoice->supplier->increment('balance', (float) $invoice->total);
            }

            $this->accountingService->postPurchaseEntry($invoice, $user);

            $this->auditService->log(
                event: 'purchase.received',
                auditable: $invoice,
                newValues: [
                    'warehouse_id' => $invoice->warehouse_id,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $invoice->total,
                ],
                user: $user,
            );

            return $invoice->fresh(['items.product', 'supplier', 'warehouse']);
        });
    }
}
