<?php

namespace App\Enums;

enum KitchenTicketStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
}
