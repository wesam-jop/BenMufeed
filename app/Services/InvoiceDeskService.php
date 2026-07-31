<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceDeskService
{
    /**
     * @param  array<int, array{material_id: int, material_name: string, unit: string, quantity: float, unit_price: float, line_total: float}>  $lines
     */
    public function savePurchaseInvoice(
        array $header,
        array $lines,
        ?PurchaseInvoice $invoice = null,
        float $paymentAmount = 0,
    ): PurchaseInvoice {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'أضف مادة واحدة على الأقل.',
            ]);
        }

        return DB::transaction(function () use ($header, $lines, $invoice, $paymentAmount): PurchaseInvoice {
            $previousStatus = $invoice !== null
                ? ($invoice->status instanceof InvoiceStatus ? $invoice->status : InvoiceStatus::from((string) $invoice->status))
                : InvoiceStatus::Draft;

            if ($invoice !== null) {
                $invoice->update($header);
                $invoice->items()->delete();
            } else {
                $invoice = PurchaseInvoice::create($header);
            }

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'material_id' => $line['material_id'],
                    'material_name' => $line['material_name'],
                    'unit' => $line['unit'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $invoice->recalculateTotals();

            app(InventoryService::class)->syncPurchaseInvoiceStock(
                $invoice->fresh(['items']),
                $previousStatus,
            );

            $invoice = $invoice->fresh(['items', 'customer']);

            $this->applyPurchasePayment(
                $invoice,
                $header['payment_type'] instanceof PaymentType
                    ? $header['payment_type']
                    : PaymentType::from((string) $header['payment_type']),
                $paymentAmount,
                (string) $header['invoice_date'],
            );

            return $invoice->fresh(['items', 'customer', 'payments']);
        });
    }

    /**
     * @param  array<int, array{material_id: int, material_name: string, unit: string, quantity: float, unit_price: float, line_total: float}>  $lines
     */
    public function saveSalesInvoice(
        array $header,
        array $lines,
        ?SalesInvoice $invoice = null,
        float $paymentAmount = 0,
    ): SalesInvoice {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'أضف مادة واحدة على الأقل.',
            ]);
        }

        return DB::transaction(function () use ($header, $lines, $invoice, $paymentAmount): SalesInvoice {
            $previousStatus = $invoice !== null
                ? ($invoice->status instanceof InvoiceStatus ? $invoice->status : InvoiceStatus::from((string) $invoice->status))
                : InvoiceStatus::Draft;

            if ($invoice !== null) {
                $invoice->update($header);
                $invoice->items()->delete();
            } else {
                $invoice = SalesInvoice::create($header);
            }

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'material_id' => $line['material_id'],
                    'material_name' => $line['material_name'],
                    'unit' => $line['unit'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $invoice->recalculateTotals();

            app(InventoryService::class)->syncSalesInvoiceStock(
                $invoice->fresh(['items']),
                $previousStatus,
            );

            $invoice = $invoice->fresh(['items', 'customer']);

            $this->applySalesPayment(
                $invoice,
                $header['payment_type'] instanceof PaymentType
                    ? $header['payment_type']
                    : PaymentType::from((string) $header['payment_type']),
                $paymentAmount,
                (string) $header['invoice_date'],
            );

            return $invoice->fresh(['items', 'customer', 'payments']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseHeaderFromDesk(
        string $paymentType,
        ?int $customerId,
        string $invoiceDate,
        string $status,
        ?string $notes,
    ): array {
        return [
            'payment_type' => PaymentType::from($paymentType),
            'customer_id' => $customerId,
            'invoice_date' => $invoiceDate,
            'status' => InvoiceStatus::from($status),
            'notes' => filled($notes) ? $notes : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function salesHeaderFromDesk(
        string $paymentType,
        ?int $customerId,
        string $invoiceDate,
        string $status,
        ?string $notes,
    ): array {
        return [
            'payment_type' => PaymentType::from($paymentType),
            'customer_id' => $customerId,
            'invoice_date' => $invoiceDate,
            'status' => InvoiceStatus::from($status),
            'notes' => filled($notes) ? $notes : null,
        ];
    }

    protected function applyPurchasePayment(
        PurchaseInvoice $invoice,
        PaymentType $paymentType,
        float $paymentAmount,
        string $paidAt,
    ): void {
        if ($paymentType === PaymentType::Cash) {
            $invoice->syncCashPayment($paidAt);

            return;
        }

        if ($paymentAmount <= 0) {
            return;
        }

        if ($paymentAmount > $invoice->remainingAmount() + 0.001) {
            throw ValidationException::withMessages([
                'paymentAmount' => 'المبلغ المدفوع أكبر من المتبقي على الفاتورة.',
            ]);
        }

        $invoice->recordPayment($paymentAmount, $paidAt, 'دفعة');
    }

    protected function applySalesPayment(
        SalesInvoice $invoice,
        PaymentType $paymentType,
        float $paymentAmount,
        string $paidAt,
    ): void {
        if ($paymentType === PaymentType::Cash) {
            $invoice->syncCashPayment($paidAt);

            return;
        }

        if ($paymentAmount <= 0) {
            return;
        }

        if ($paymentAmount > $invoice->remainingAmount() + 0.001) {
            throw ValidationException::withMessages([
                'paymentAmount' => 'المبلغ المدفوع أكبر من المتبقي على الفاتورة.',
            ]);
        }

        $invoice->recordPayment($paymentAmount, $paidAt, 'دفعة');
    }
}
