<?php

namespace App\Enums;

enum PrintJobKind: string
{
    case KitchenTicket = 'kitchen_ticket';
    case CashierReceipt = 'cashier_receipt';

    public function label(): string
    {
        return match ($this) {
            self::KitchenTicket => 'تذكرة مطبخ',
            self::CashierReceipt => 'فاتورة كاشير',
        };
    }
}
