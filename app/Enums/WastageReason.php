<?php

namespace App\Enums;

enum WastageReason: string
{
    case Expired = 'expired';
    case Damaged = 'damaged';
    case Spillage = 'spillage';
    case Overproduction = 'overproduction';
    case Theft = 'theft';
    case Other = 'other';
}
