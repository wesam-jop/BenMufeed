<?php

namespace App\Models;

use App\Services\EmployeeLedgerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'monthly_salary',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(EmployeeLedgerEntry::class)->latest('entry_date')->latest('id');
    }

    public function financialMovements(): HasMany
    {
        return $this->hasMany(FinancialMovement::class);
    }

    public function balance(): float
    {
        return EmployeeLedgerService::balanceFor($this->getKey());
    }
}
