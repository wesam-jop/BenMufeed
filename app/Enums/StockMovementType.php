<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case ProductionIn = 'production_in';
    case ProductionOut = 'production_out';
    case Wastage = 'wastage';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case OpeningBalance = 'opening_balance';
}
