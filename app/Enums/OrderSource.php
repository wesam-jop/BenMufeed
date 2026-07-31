<?php

namespace App\Enums;

enum OrderSource: string
{
    case Website = 'website';
    case Pos = 'pos';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'الموقع',
            self::Pos => 'نقطة البيع',
        };
    }
}
