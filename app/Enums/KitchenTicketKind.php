<?php

namespace App\Enums;

enum KitchenTicketKind: string
{
    case NewTable = 'new_table';
    case TableAppend = 'table_append';
    case TakeawayPos = 'takeaway_pos';
    case TakeawayWebsite = 'takeaway_website';
    case DeliveryWebsite = 'delivery_website';

    public function label(): string
    {
        return match ($this) {
            self::NewTable => 'طلب طاولة جديد',
            self::TableAppend => 'إضافة على طاولة',
            self::TakeawayPos => 'سفري — كاشير',
            self::TakeawayWebsite => 'استلام — موقع',
            self::DeliveryWebsite => 'توصيل — موقع',
        };
    }

    public function isAppend(): bool
    {
        return $this === self::TableAppend;
    }
}
