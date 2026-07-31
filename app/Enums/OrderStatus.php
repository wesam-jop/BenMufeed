<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Open = 'open';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد — بانتظار القبول',
            self::Reviewed => 'مقبول — في المطبخ',
            self::Open => 'طاولة مفتوحة',
            self::Completed => 'مكتمل',
            self::Cancelled => 'ملغى',
        };
    }

    public function isPendingWebsiteReview(): bool
    {
        return $this === self::New;
    }
}
