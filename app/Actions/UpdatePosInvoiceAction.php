<?php

namespace App\Actions;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\CashRegisterService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdatePosInvoiceAction
{
    public function __construct(
        private VoidSaleAction $voidSaleAction,
        private StockService $stockService,
        private CashRegisterService $cashRegisterService,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float|int|string, variant_id?: int|null}>  $items
     */
    public function execute(
        Invoice $invoice,
        array $items,
        Warehouse $warehouse,
        ?User $user = null,
    ): Invoice {
        return DB::transaction(function () use ($invoice, $items, $warehouse, $user) {
            $user ??= auth()->user();

            if ($invoice->status === InvoiceStatus::Void) {
                throw new RuntimeException('لا يمكن تعديل فاتورة ملغاة.');
            }

            $invoice->loadMissing(['items', 'payments.cashRegisterSession', 'order']);

            if ($invoice->order === null) {
                throw new RuntimeException('لا يمكن تعديل هذه الفاتورة.');
            }

            if ($items === []) {
                throw new RuntimeException('يجب أن تحتوي الفاتورة على صنف واحد على الأقل.');
            }

            $this->voidSaleAction->reverseOrderStock($invoice->order, $invoice, $user);

            $invoice->items()->delete();
            $invoice->order->items()->delete();

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($items as $payload) {
                $product = Product::with('taxRate')->findOrFail($payload['product_id']);
                $variant = isset($payload['variant_id'])
                    ? ProductVariant::findOrFail($payload['variant_id'])
                    : null;

                $quantity = (float) $payload['quantity'];

                if ($quantity <= 0) {
                    throw new RuntimeException("الكمية يجب أن تكون أكبر من صفر للمنتج {$product->name}.");
                }

                $unitPrice = (float) ($variant?->sale_price ?? $product->sale_price);
                $costPrice = (float) ($variant?->cost_price ?? $product->cost_price);
                $lineSubtotal = $unitPrice * $quantity;
                $tax = $this->calculateTax($product, $lineSubtotal);
                $lineTotal = round($lineSubtotal + ($product->taxRate?->is_inclusive ? 0 : $tax), 2);

                OrderItem::create([
                    'order_id' => $invoice->order_id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'tax' => $tax,
                    'status' => OrderItemStatus::Pending,
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'discount' => 0,
                    'tax' => $tax,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $tax;
            }

            $invoice->refresh()->load('items');
            $invoice->order->refresh()->load('items.product');

            foreach ($invoice->order->items as $orderItem) {
                $product = $orderItem->product;

                if ($product === null || ! $product->track_stock) {
                    continue;
                }

                $this->stockService->consumeFromRecipe(
                    product: $product,
                    quantity: (float) $orderItem->quantity,
                    warehouseId: $warehouse->id,
                    variantId: $orderItem->variant_id,
                    reference: $invoice->order,
                    user: $user,
                );
            }

            $discount = (float) $invoice->discount;
            $total = round(max(0, $subtotal - $discount + $taxTotal), 2);
            $oldTotal = (float) $invoice->total;
            $paid = (float) $invoice->paid;

            $invoice->update([
                'subtotal' => round($subtotal, 2),
                'tax' => round($taxTotal, 2),
                'total' => $total,
                'balance' => max($total - $paid, 0),
                'status' => $paid >= $total
                    ? InvoiceStatus::Paid->value
                    : ($paid > 0 ? InvoiceStatus::Partial->value : InvoiceStatus::Issued->value),
            ]);

            $invoice->order->update([
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxTotal, 2),
                'total' => $total,
            ]);

            $difference = round($total - $oldTotal, 2);

            if ($difference !== 0.0) {
                $cashPayment = $invoice->payments
                    ->first(fn ($payment) => $payment->method === PaymentMethod::Cash && $payment->cash_register_session_id !== null);

                if ($cashPayment !== null) {
                    $newAmount = round((float) $cashPayment->amount + $difference, 2);

                    if ($newAmount < 0) {
                        throw new RuntimeException('لا يمكن تقليل المبلغ دون المدفوع.');
                    }

                    $cashPayment->update(['amount' => $newAmount]);

                    $invoice->update([
                        'paid' => $newAmount,
                        'balance' => max($total - $newAmount, 0),
                        'status' => $newAmount >= $total ? InvoiceStatus::Paid->value : InvoiceStatus::Partial->value,
                    ]);

                    if ($cashPayment->cashRegisterSession !== null) {
                        $movementType = $difference > 0 ? CashMovementType::Sale : CashMovementType::Refund;

                        $this->cashRegisterService->recordMovement(
                            $cashPayment->cashRegisterSession,
                            $movementType,
                            abs($difference),
                            $cashPayment,
                            notes: "تعديل فاتورة {$invoice->invoice_number}",
                        );
                    }
                }
            }

            return $invoice->fresh(['items', 'payments']);
        });
    }

    private function calculateTax(Product $product, float $lineSubtotal): float
    {
        $taxRate = $product->taxRate;

        if ($taxRate === null || ! $taxRate->is_active) {
            return 0;
        }

        $rate = (float) $taxRate->rate;

        if ($taxRate->is_inclusive) {
            return round($lineSubtotal - ($lineSubtotal / (1 + ($rate / 100))), 2);
        }

        return round($lineSubtotal * ($rate / 100), 2);
    }
}
