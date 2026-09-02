<?php

namespace App\Enums;

enum ApprovalType: string
{
    case Discount = 'discount';
    case Refund = 'refund';
    case VoidInvoice = 'void_invoice';
    case StockAdjustment = 'stock_adjustment';
    case PriceOverride = 'price_override';
    case Wastage = 'wastage';
    case Purchase = 'purchase';
}
