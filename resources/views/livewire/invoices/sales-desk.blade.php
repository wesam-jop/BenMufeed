<x-invoices.page
    title="فواتير المبيع"
    icon="arrow-up-tray"
    alt-link="{{ url('/invoices/purchases') }}"
    alt-label="فواتير الشراء"
    :recent-invoices="$recentInvoices"
    :currency="$currency"
    :customer-options="$customerOptions"
    :material-options="$materialOptions"
    :payment-type-options="$paymentTypeOptions"
    :status-options="$statusOptions"
    invoice-type="sales"
    :lines-total="$this->linesTotal"
    :lines-count="$this->linesCount"
    :remaining-after-payment="$this->remainingAfterPayment"
    :panel="$panel"
    :editing-id="$editingId"
    :lines="$lines"
    :payment-type="$paymentType"
    :saved-invoice-number="$savedInvoiceNumber"
    :existing-paid-amount="$existingPaidAmount"
    :payment-amount="$paymentAmount"
    :invoice-date="$invoiceDate"
    :status="$status"
    :customer-id="$customerId"
    :notes="$notes"
>
    <x-slot:history>
        <section class="inv-history">
            <div class="inv-history-head">
                <h2>سجل فواتير المبيع</h2>
                <button type="button" wire:click="newInvoice" class="inv-btn-secondary">فاتورة جديدة</button>
            </div>
            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>الفاتورة</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>الدفع</th>
                            <th>الحالة</th>
                            <th>الإجمالي</th>
                            <th>مدفوع</th>
                            <th>متبقي</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentInvoices as $invoice)
                            <tr wire:key="sales-{{ $invoice->id }}">
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                <td>{{ $invoice->customer?->name ?? $invoice->customer_name ?: '—' }}</td>
                                <td>{{ $invoice->payment_type?->label() ?? 'نقدي' }}</td>
                                <td><span class="inv-badge inv-badge-{{ $invoice->status->color() }}">{{ $invoice->status->label() }}</span></td>
                                <td>{{ number_format($invoice->total, 2) }} {{ $currency }}</td>
                                <td>{{ number_format($invoice->paid_amount, 2) }} {{ $currency }}</td>
                                <td>{{ number_format($invoice->remainingAmount(), 2) }} {{ $currency }}</td>
                                <td class="inv-row-actions">
                                    <button type="button" wire:click="editInvoice({{ $invoice->id }})" class="inv-btn-link">تعديل</button>
                                    <button type="button" wire:click="deleteInvoice({{ $invoice->id }})" wire:confirm="حذف هذه الفاتورة؟" class="inv-btn-danger">حذف</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="inv-empty-cell">لا توجد فواتير بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </x-slot:history>
</x-invoices.page>
