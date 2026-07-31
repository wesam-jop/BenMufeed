<?php

namespace App\Models;

use App\Enums\EmployeeLedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLedgerEntry extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'amount',
        'entry_date',
        'period_year',
        'period_month',
        'financial_movement_id',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmployeeLedgerEntryType::class,
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function financialMovement(): BelongsTo
    {
        return $this->belongsTo(FinancialMovement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->type->isCredit() ? $amount : -$amount;
    }

    public function periodLabel(): ?string
    {
        if ($this->period_year === null || $this->period_month === null) {
            return null;
        }

        return sprintf('%02d/%d', $this->period_month, $this->period_year);
    }
}
