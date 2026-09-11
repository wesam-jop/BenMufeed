<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Shared\NumberGeneratorService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
    ) {}

    public function createFromOrder(Order $order, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($order, $user) {
            if ($order->invoices()->exists()) {
                throw new RuntimeException('يوجد فاتورة مسبقة لهذا الطلب.');
            }

            $order->loadMissing(['items.product', 'items.variant']);

            $invoice = Invoice::create([
                'branch_id' => $order->branch_id,
                'invoice_number' => $this->numberGenerator->next('INV', Invoice::class, 'invoice_number'),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => InvoiceStatus::Draft->value,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount_amount,
                'tax' => $order->tax_amount,
                'total' => $order->total,
                'paid' => 0,
                'balance' => $order->total,
                'user_id' => $user?->id ?? $order->user_id,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'cost_price' => $item->cost_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'modifiers' => $item->modifiers,
                    'total' => ($item->quantity * $item->unit_price) - $item->discount + $item->tax,
                ]);
            }

            return $invoice->fresh(['items']);
        });
    }

    public function finalize(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new RuntimeException('يمكن اعتماد الفواتير المسودة فقط.');
            }

            $invoice->update([
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'balance' => max((float) $invoice->total - (float) $invoice->paid, 0),
            ]);

            return $invoice->fresh();
        });
    }

    public function void(Invoice $invoice, ?string $reason = null): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status === InvoiceStatus::Void) {
                throw new RuntimeException('الفاتورة ملغاة مسبقاً.');
            }

            if ((float) $invoice->paid > 0) {
                throw new RuntimeException('يجب استرداد المدفوعات قبل إلغاء الفاتورة.');
            }

            $invoice->update([
                'status' => InvoiceStatus::Void,
                'voided_at' => now(),
            ]);

            return $invoice->fresh();
        });
    }
}
