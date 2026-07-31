<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

final class PrintableOrder
{
    public static function canPrintCashierReceipt(Order $order): bool
    {
        return ! in_array($order->status, [OrderStatus::New, OrderStatus::Cancelled], true);
    }
}
