<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Sale = 'sale';
    case Refund = 'refund';
    case Expense = 'expense';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Adjustment = 'adjustment';
}
