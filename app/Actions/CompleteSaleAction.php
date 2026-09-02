<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\CashRegisterSession;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingService;
use App\Services\Shared\AuditService;
use App\Services\Accounting\CashRegisterService;
use App\Services\Sales\InvoiceService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompleteSaleAction
{
    public function __construct(
        private StockService $stockService,
        private InvoiceService $invoiceService,
        private CashRegisterService $cashRegisterService,
        private AccountingService $accountingService,
        private AuditService $auditService,
    ) {}

    /**
     * @param  array<int, array{method: PaymentMethod|string, amount: float|int|string, reference?: string|null}>  $payments
     */
    public function execute(
        Order $order,
        Warehouse $warehouse,
        array $payments = [],
        ?CashRegisterSession $session = null,
        ?User $user = null,
    ): Invoice {
        return DB::transaction(function () use ($order, $warehouse, $payments, $session, $user) {
            $user ??= auth()->user();

            if (! in_array($order->status, [OrderStatus::Ready, OrderStatus::Confirmed, OrderStatus::Preparing], true)) {
                throw new RuntimeException('الطلب غير جاهز للإتمام.');
            }

            $order->loadMissing(['items.product']);

            foreach ($order->items as $item) {
                if (! $item->product->track_stock) {
                    continue;
                }

                $this->stockService->consumeFromRecipe(
                    product: $item->product,
                    quantity: (float) $item->quantity,
                    warehouseId: $warehouse->id,
                    variantId: $item->variant_id,
                    reference: $order,
                    user: $user,
                );
            }

            $invoice = $this->invoiceService->createFromOrder($order, $user);
            $invoice = $this->invoiceService->finalize($invoice);

            $totalPaid = 0.0;

            foreach ($payments as $payment) {
                $amount = (float) $payment['amount'];

                if ($amount <= 0) {
                    continue;
                }

                if ($session !== null) {
                    $this->cashRegisterService->recordPayment(
                        session: $session,
                        invoice: $invoice,
                        method: $payment['method'],
                        amount: $amount,
                        user: $user,
                        reference: $payment['reference'] ?? null,
                    );
                } else {
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'method' => $payment['method'] instanceof PaymentMethod
                            ? $payment['method']->value
                            : $payment['method'],
                        'amount' => $amount,
                        'reference' => $payment['reference'] ?? null,
                        'user_id' => $user?->id,
                        'paid_at' => now(),
                    ]);

                    $invoice->increment('paid', $amount);
                    $invoice->decrement('balance', $amount);
                }

                $totalPaid += $amount;
            }

            $invoice->refresh();

            if ($totalPaid >= (float) $invoice->total) {
                $invoice->update(['status' => InvoiceStatus::Paid->value, 'balance' => 0]);
            } elseif ($totalPaid > 0) {
                $invoice->update(['status' => InvoiceStatus::Partial->value]);
            }

            $this->accountingService->postSaleEntry($invoice, $user);

            $order->update(['status' => OrderStatus::Completed->value]);

            $this->auditService->log(
                event: 'sale.completed',
                auditable: $invoice,
                newValues: [
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'total' => $invoice->total,
                    'paid' => $totalPaid,
                ],
                user: $user,
            );

            return $invoice->fresh(['items', 'payments']);
        });
    }
}
