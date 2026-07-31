<?php

namespace App\Livewire\Invoices\Concerns;

use App\Enums\PaymentType;

trait HandlesInvoicePayments
{
    public ?string $paymentAmount = null;

    public float $existingPaidAmount = 0;

    public function updatedPaymentType(): void
    {
        if ($this->paymentType === PaymentType::Cash->value) {
            $this->paymentAmount = null;
        }
    }

    public function getPaymentAmountValueProperty(): float
    {
        if ($this->paymentAmount === null || $this->paymentAmount === '') {
            return 0.0;
        }

        return round((float) $this->paymentAmount, 2);
    }

    public function getRemainingAfterPaymentProperty(): float
    {
        return max(0, round($this->linesTotal - $this->existingPaidAmount - $this->paymentAmountValue, 2));
    }

    protected function resetPaymentFields(): void
    {
        $this->paymentAmount = null;
        $this->existingPaidAmount = 0;
    }

    protected function loadPaymentFields(float $paidAmount): void
    {
        $this->existingPaidAmount = round($paidAmount, 2);
        $this->paymentAmount = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentValidationRules(): array
    {
        return [
            'paymentAmount' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->paymentType !== PaymentType::Credit->value) {
                        return;
                    }

                    if ($value === null || $value === '') {
                        return;
                    }

                    $amount = round((float) $value, 2);
                    $remaining = round($this->linesTotal - $this->existingPaidAmount, 2);

                    if ($amount > $remaining + 0.001) {
                        $fail('المبلغ المدفوع أكبر من المتبقي ('.number_format($remaining, 2).').');
                    }
                },
            ],
        ];
    }
}
