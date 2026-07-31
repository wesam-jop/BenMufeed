<?php

namespace App\Enums;

enum EmployeeLedgerEntryType: string
{
    case SalaryAccrual = 'salary_accrual';
    case Advance = 'advance';
    case SalaryPayment = 'salary_payment';

    public function label(): string
    {
        return match ($this) {
            self::SalaryAccrual => 'استحقاق راتب (دائن)',
            self::Advance => 'سلفة (مدين)',
            self::SalaryPayment => 'دفعة راتب (مدين)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::SalaryAccrual => 'استحقاق',
            self::Advance => 'سلفة',
            self::SalaryPayment => 'دفعة راتب',
        };
    }

    public function isCredit(): bool
    {
        return $this === self::SalaryAccrual;
    }

    public function isDebit(): bool
    {
        return ! $this->isCredit();
    }

    public function color(): string
    {
        return $this->isCredit() ? 'success' : 'danger';
    }

    public function icon(): string
    {
        return $this->isCredit() ? '+' : '−';
    }
}
