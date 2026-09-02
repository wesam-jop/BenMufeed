<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Partial = 'partial';
    case Void = 'void';
    case Overdue = 'overdue';
}
