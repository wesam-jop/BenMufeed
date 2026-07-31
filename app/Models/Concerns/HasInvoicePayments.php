<?php

namespace App\Models\Concerns;

use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasInvoicePayments
{
    public static function bootHasInvoicePayments(): void
    {
        static::deleting(function ($invoice): void {
            $invoice->payments()->delete();
        });
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(InvoicePayment::class, 'invoice');
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    public function recordPayment(float $amount, string $paidAt, ?string $notes = null): InvoicePayment
    {
        $payment = $this->payments()->create([
            'amount' => round($amount, 2),
            'paid_at' => $paidAt,
            'notes' => $notes,
        ]);

        $this->increment('paid_amount', round($amount, 2));

        return $payment;
    }

    public function syncCashPayment(string $paidAt): void
    {
        $remaining = $this->remainingAmount();

        if ($remaining <= 0) {
            return;
        }

        $this->recordPayment($remaining, $paidAt, 'دفع نقدي');
    }
}
