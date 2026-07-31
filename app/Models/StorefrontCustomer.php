<?php

namespace App\Models;

use App\Enums\StorefrontCustomerAccountType;
use App\Enums\StorefrontCustomerCreatedVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StorefrontCustomer extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'phone',
        'phone_verified_at',
        'account_type',
        'default_restaurant_table_id',
        'created_via',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'account_type' => StorefrontCustomerAccountType::class,
            'created_via' => StorefrontCustomerCreatedVia::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StorefrontCustomer $customer): void {
            if (blank($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
        });
    }

    public function defaultTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'default_restaurant_table_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StorefrontCustomerSession::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StorefrontCustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isVerified(): bool
    {
        return $this->account_type === StorefrontCustomerAccountType::Verified
            && $this->phone_verified_at !== null;
    }

    public function isTableGuest(): bool
    {
        return $this->account_type === StorefrontCustomerAccountType::TableGuest;
    }

    public function hasDefaultTableName(): bool
    {
        return str_starts_with($this->name, 'زبون — طاولة ')
            || str_starts_with($this->name, 'زبون طاولة ');
    }

    /**
     * @return array<string, mixed>
     */
    public function toStorefrontArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_verified' => $this->isVerified(),
            'account_type' => $this->account_type->value,
            'account_type_label' => $this->account_type->label(),
            'needs_profile_for_pickup' => $this->isTableGuest() || ! $this->isVerified(),
            'default_table_label' => $this->defaultTable?->displayName(),
        ];
    }
}
