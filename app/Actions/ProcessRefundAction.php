<?php

namespace App\Actions;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\RefundStatus;
use App\Enums\StockMovementType;
use App\Models\CashRegisterSession;
use App\Models\Refund;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Shared\AuditService;
use App\Services\Accounting\CashRegisterService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessRefundAction
{
    public function __construct(
        private StockService $stockService,
        private CashRegisterService $cashRegisterService,
        private AuditService $auditService,
    ) {}

    public function execute(
        Refund $refund,
        ?Warehouse $warehouse = null,
        ?CashRegisterSession $session = null,
        ?User $user = null,
    ): Refund {
        return DB::transaction(function () use ($refund, $warehouse, $session, $user) {
            $user ??= auth()->user();

            if (! in_array($refund->status, [RefundStatus::Approved->value, RefundStatus::Pending->value], true)) {
                throw new RuntimeException('Refund cannot be processed in its current status.');
            }

            $refund->loadMissing('invoice.items.product');

            if ($warehouse !== null) {
                foreach ($refund->invoice->items as $item) {
                    if (! $item->product->track_stock) {
                        continue;
                    }

                    $returnQty = min((float) $item->quantity, (float) $refund->amount / max((float) $item->unit_price, 1));

                    $movement = $this->stockService->recordMovement([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $item->product_id,
                        'type' => StockMovementType::ReturnIn,
                        'quantity' => $returnQty,
                        'reference' => $refund,
                        'user_id' => $user?->id,
                        'notes' => "Refund for invoice {$refund->invoice->invoice_number}",
                    ]);

                    $refund->update(['stock_movement_id' => $movement->id]);
                }
            }

            if ($session !== null) {
                $this->cashRegisterService->recordMovement(
                    session: $session,
                    type: CashMovementType::Refund,
                    amount: (float) $refund->amount,
                    reference: $refund,
                    notes: $refund->reason,
                );
            }

            $invoice = $refund->invoice;
            $invoice->decrement('paid', (float) $refund->amount);
            $invoice->increment('balance', (float) $refund->amount);

            $invoice->refresh();

            if ((float) $invoice->paid <= 0) {
                $invoice->update(['status' => InvoiceStatus::Issued->value]);
            } else {
                $invoice->update(['status' => InvoiceStatus::Partial->value]);
            }

            $refund->update([
                'status' => RefundStatus::Processed->value,
                'approved_by' => $refund->approved_by ?? $user?->id,
            ]);

            $this->auditService->log(
                event: 'refund.processed',
                auditable: $refund,
                newValues: [
                    'invoice_id' => $invoice->id,
                    'amount' => $refund->amount,
                    'reason' => $refund->reason,
                ],
                user: $user,
            );

            return $refund->fresh(['invoice']);
        });
    }
}
