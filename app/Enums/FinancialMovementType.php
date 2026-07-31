<?php

namespace App\Enums;

enum FinancialMovementType: string
{
    case Expense = 'expense';
    case CustomerPayment = 'customer_payment';
    case ExternalReceipt = 'external_receipt';
    case EmployeeAdvance = 'employee_advance';
    case OwnerWithdrawal = 'owner_withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Expense => 'مصروف',
            self::CustomerPayment => 'دفعة إلى عميل',
            self::ExternalReceipt => 'قبض خارجي',
            self::EmployeeAdvance => 'سلفة لعامل',
            self::OwnerWithdrawal => 'مسحوبات الشريك',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Expense, self::CustomerPayment, self::EmployeeAdvance => 'danger',
            self::ExternalReceipt => 'success',
            self::OwnerWithdrawal => 'warning',
        };
    }

    public function isIncoming(): bool
    {
        return $this === self::ExternalReceipt;
    }

    public function isOwnerWithdrawal(): bool
    {
        return $this === self::OwnerWithdrawal;
    }

    /** Outflow that reduces operating profit (expenses, payroll-related, debt settlement). */
    public function isOperatingOutflow(): bool
    {
        return match ($this) {
            self::Expense, self::CustomerPayment, self::EmployeeAdvance => true,
            self::ExternalReceipt, self::OwnerWithdrawal => false,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Expense => '↓',
            self::CustomerPayment => '↗',
            self::ExternalReceipt => '↑',
            self::EmployeeAdvance => '↘',
            self::OwnerWithdrawal => '⇣',
        };
    }
}
