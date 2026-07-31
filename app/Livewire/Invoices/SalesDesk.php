<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Livewire\Invoices\Concerns\HandlesInvoicePayments;
use App\Livewire\Invoices\Concerns\InteractsWithInvoiceLines;
use App\Models\SalesInvoice;
use App\Services\InvoiceDeskService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.desk')]
#[Title('فواتير المبيع — بن مفيد')]
class SalesDesk extends Component
{
    use BuildsInvoiceOptions;
    use HandlesInvoicePayments;
    use InteractsWithInvoiceLines;

    public string $panel = 'create';

    public ?int $editingId = null;

    public string $paymentType = 'cash';

    public ?int $customerId = null;

    public string $invoiceDate = '';

    public string $status = 'confirmed';

    public string $notes = '';

    public ?string $savedInvoiceNumber = null;

    public function mount(?SalesInvoice $salesInvoice = null): void
    {
        $this->invoiceDate = now()->format('Y-m-d');
        $this->ensureInitialLine();

        if ($salesInvoice !== null) {
            $this->loadInvoice($salesInvoice);
        }
    }

    protected function materialPriceField(): string
    {
        return 'sale_price';
    }

    public function setPanel(string $panel): void
    {
        $this->panel = $panel;
    }

    public function save(): void
    {
        $this->pruneEmptyLines();

        if ($this->lines === []) {
            $this->addError('save', 'أضف مادة واحدة على الأقل.');
            $this->ensureInitialLine();

            return;
        }

        $this->validate(array_merge([
            'invoiceDate' => ['required', 'date'],
            'paymentType' => ['required', Rule::enum(PaymentType::class)],
            'customerId' => [
                Rule::requiredIf($this->paymentType === PaymentType::Credit->value),
                'nullable',
                'exists:customers,id',
            ],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'exists:materials,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], $this->paymentValidationRules()), [
            'lines.required' => 'أضف مادة واحدة على الأقل.',
            'lines.min' => 'أضف مادة واحدة على الأقل.',
            'lines.*.material_id.required' => 'اختر المادة في كل بند.',
            'customerId.required' => 'اختر العميل عند الدفع دين.',
        ]);

        try {
            $invoice = app(InvoiceDeskService::class)->saveSalesInvoice(
                app(InvoiceDeskService::class)->salesHeaderFromDesk(
                    $this->paymentType,
                    $this->customerId,
                    $this->invoiceDate,
                    $this->status,
                    $this->notes,
                ),
                $this->normalizedLines(),
                $this->editingId !== null
                    ? SalesInvoice::query()->findOrFail($this->editingId)
                    : null,
                $this->paymentAmountValue,
            );

            $this->savedInvoiceNumber = $invoice->invoice_number;
            $this->editingId = $invoice->getKey();
            $this->loadPaymentFields((float) $invoice->paid_amount);
            $this->paymentAmount = null;
        } catch (ValidationException $e) {
            $this->addError('save', collect($e->errors())->flatten()->first() ?? 'تحقق من البيانات.');
        } catch (\Throwable $e) {
            $this->addError('save', $e->getMessage());
        }
    }

    public function newInvoice(): void
    {
        $this->resetForm();
        $this->panel = 'create';
    }

    public function editInvoice(int $id): void
    {
        $invoice = SalesInvoice::query()->with('items')->findOrFail($id);
        $this->loadInvoice($invoice);
        $this->panel = 'create';
    }

    public function deleteInvoice(int $id): void
    {
        SalesInvoice::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function render()
    {
        $recentInvoices = SalesInvoice::query()
            ->with('customer')
            ->latest('invoice_date')
            ->latest('id')
            ->limit(40)
            ->get();

        return view('livewire.invoices.sales-desk', [
            'recentInvoices' => $recentInvoices,
            'currency' => config('store.currency_symbol', '$'),
            'customerOptions' => $this->customerOptions(),
            'materialOptions' => $this->materialOptions(),
            'paymentTypeOptions' => $this->enumOptions(PaymentType::cases()),
            'statusOptions' => $this->enumOptions(InvoiceStatus::cases()),
        ]);
    }

    private function loadInvoice(SalesInvoice $invoice): void
    {
        $invoice->loadMissing('items');

        $this->editingId = $invoice->getKey();
        $this->paymentType = $invoice->payment_type instanceof PaymentType
            ? $invoice->payment_type->value
            : (string) ($invoice->payment_type ?? PaymentType::Cash->value);
        $this->customerId = $invoice->customer_id;
        $this->invoiceDate = $invoice->invoice_date->format('Y-m-d');
        $this->status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status->value
            : (string) $invoice->status;
        $this->notes = (string) ($invoice->notes ?? '');
        $this->loadLinesFromInvoiceItems($invoice->items);
        $this->loadPaymentFields((float) $invoice->paid_amount);
        $this->savedInvoiceNumber = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->paymentType = PaymentType::Cash->value;
        $this->customerId = null;
        $this->invoiceDate = now()->format('Y-m-d');
        $this->status = InvoiceStatus::Confirmed->value;
        $this->notes = '';
        $this->clearLines();
        $this->resetPaymentFields();
        $this->savedInvoiceNumber = null;
        $this->resetErrorBag();
    }
}
