<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Models\Concerns\HasInvoicePayments;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use HasInvoicePayments;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'payment_type',
        'customer_id',
        'invoice_date',
        'status',
        'subtotal',
        'total',
        'paid_amount',
        'stock_applied',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'status' => InvoiceStatus::class,
            'payment_type' => PaymentType::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'stock_applied' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesInvoice $invoice): void {
            if ($invoice->customer_id !== null) {
                $customer = $invoice->relationLoaded('customer')
                    ? $invoice->customer
                    : Customer::query()->find($invoice->customer_id);

                if ($customer !== null) {
                    $invoice->customer_name = $customer->name;
                }
            }
        });

        static::creating(function (SalesInvoice $invoice): void {
            if (filled($invoice->invoice_number)) {
                return;
            }

            $year = now()->format('Y');
            $last = static::query()
                ->where('invoice_number', 'like', "S-{$year}-%")
                ->orderByDesc('id')
                ->value('invoice_number');

            $sequence = 1;

            if (is_string($last) && preg_match('/S-'.$year.'-(\d+)$/', $last, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $invoice->invoice_number = sprintf('S-%s-%04d', $year, $sequence);
        });

        static::deleting(function (SalesInvoice $invoice): void {
            app(InventoryService::class)->reverseSalesInvoice($invoice);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ])->saveQuietly();
    }
}
