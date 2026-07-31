<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Cashier = 'cashier';
    case Kitchen = 'kitchen';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير',
            self::Cashier => 'كاشير',
            self::Kitchen => 'مطبخ',
        };
    }

    public function canAccessPanel(): bool
    {
        return $this === self::Admin;
    }

    public function canAccessPos(): bool
    {
        return in_array($this, [self::Admin, self::Cashier], true);
    }

    public function canAccessKitchen(): bool
    {
        return in_array($this, [self::Admin, self::Kitchen], true);
    }

    public function canAccessInvoices(): bool
    {
        return $this === self::Admin;
    }
}
