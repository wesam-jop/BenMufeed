<?php

namespace App\Models;

use App\Enums\FinancialMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialMovement extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'movement_date',
        'title',
        'details',
        'customer_id',
        'employee_id',
        'purchase_invoice_id',
        'sales_invoice_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancialMovementType::class,
            'amount' => 'decimal:2',
            'movement_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeLedgerEntry(): HasOne
    {
        return $this->hasOne(EmployeeLedgerEntry::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->type->isIncoming() ? $amount : -$amount;
    }
}
