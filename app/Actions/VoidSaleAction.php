<?php

namespace App\Actions;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Shared\AuditService;
use App\Services\Accounting\CashRegisterService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoidSaleAction
{
    public function __construct(
        private StockService $stockService,
        private CashRegisterService $cashRegisterService,
        private AuditService $auditService,
    ) {}

    public function execute(Invoice $invoice, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            $user ??= auth()->user();

            if ($invoice->status === InvoiceStatus::Void) {
                throw new RuntimeException('الفاتورة ملغاة مسبقاً.');
            }

            $invoice->loadMissing(['payments.cashRegisterSession', 'order']);

            if ($invoice->order_id !== null) {
                $this->reverseOrderStock($invoice->order, $invoice, $user);
            }

            foreach ($invoice->payments as $payment) {
                if (
                    $payment->cash_register_session_id !== null
                    && $payment->method === PaymentMethod::Cash
                ) {
                    $this->cashRegisterService->recordMovement(
                        $payment->cashRegisterSession,
                        CashMovementType::Refund,
                        (float) $payment->amount,
                        $payment,
                        notes: "إلغاء فاتورة {$invoice->invoice_number}",
                    );
                }
            }

            $invoice->update([
                'status' => InvoiceStatus::Void,
                'voided_at' => now(),
                'paid' => 0,
                'balance' => 0,
            ]);

            if ($invoice->order !== null) {
                $invoice->order->update(['status' => OrderStatus::Cancelled->value]);
            }

            $this->auditService->log(
                event: 'sale.voided',
                auditable: $invoice,
                newValues: [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                user: $user,
            );

            return $invoice->fresh(['items', 'payments']);
        });
    }

    public function reverseOrderStock(Order $order, ?Invoice $reference = null, ?User $user = null): void
    {
        $movements = StockMovement::query()
            ->where('reference_type', $order->getMorphClass())
            ->where('reference_id', $order->id)
            ->get();

        foreach ($movements as $movement) {
            if (! $this->isOutbound($movement->type)) {
                continue;
            }

            $this->stockService->recordMovement([
                'warehouse_id' => $movement->warehouse_id,
                'product_id' => $movement->product_id,
                'type' => StockMovementType::ReturnIn,
                'quantity' => (float) $movement->quantity,
                'unit_cost' => (float) $movement->unit_cost,
                'reference' => $reference ?? $order,
                'user_id' => $user?->id,
                'notes' => $reference
                    ? "إرجاع مخزون — إلغاء فاتورة {$reference->invoice_number}"
                    : 'إرجاع مخزون — تعديل فاتورة',
            ]);
        }
    }

    private function isOutbound(StockMovementType $type): bool
    {
        return in_array($type, [
            StockMovementType::Out,
            StockMovementType::TransferOut,
            StockMovementType::ProductionOut,
            StockMovementType::Wastage,
            StockMovementType::ReturnOut,
        ], true);
    }
}
