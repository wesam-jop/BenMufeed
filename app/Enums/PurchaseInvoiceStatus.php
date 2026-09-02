<?php

namespace App\Enums;

enum PurchaseInvoiceStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Paid = 'paid';
    case Partial = 'partial';
    case Cancelled = 'cancelled';
}
