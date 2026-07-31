<?php

namespace App\Enums;

enum MaterialUnit: string
{
    case Piece = 'قطعة';
    case Kg = 'كيلو';
    case Liter = 'لتر';
    case Box = 'علبة';
    case Pack = 'رزمة';

    public function label(): string
    {
        return $this->value;
    }
}
