<?php

namespace App\Enums;

enum PrintJobStatus: string
{
    case Pending = 'pending';
    case Printing = 'printing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار الطباعة',
            self::Printing => 'جاري الطباعة',
            self::Succeeded => 'تمت بنجاح',
            self::Failed => 'فشلت',
        };
    }

    public function canRetry(): bool
    {
        return $this === self::Failed || $this === self::Pending;
    }
}
