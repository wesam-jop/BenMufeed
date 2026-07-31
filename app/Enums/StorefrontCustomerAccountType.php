<?php

namespace App\Enums;

enum StorefrontCustomerAccountType: string
{
    case TableGuest = 'table_guest';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::TableGuest => 'زبون طاولة',
            self::Verified => 'حساب كامل',
        };
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}
