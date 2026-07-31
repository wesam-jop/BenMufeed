<?php

namespace App\Enums;

enum PosServiceType: string
{
    case Takeaway = 'takeaway';
    case Delivery = 'delivery';
    case Table = 'table';

    public function label(): string
    {
        return match ($this) {
            self::Table => 'بيع عالطاولة',
            self::Takeaway => 'بيع سفر بدون طاولة',
            self::Delivery => 'اونلاين مع توصيل',
        };
    }

    public function requiresDeliveryAddress(): bool
    {
        return $this === self::Delivery;
    }

    public function isWebsitePickup(): bool
    {
        return $this === self::Takeaway;
    }
}
