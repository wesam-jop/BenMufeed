<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'status',
        'source',
        'service_type',
        'table_number',
        'restaurant_table_id',
        'customer_session_token',
        'storefront_customer_id',
        'saved_address_id',
        'cashier_id',
        'total',
        'delivery_fee',
        'paid_amount',
        'reviewed_at',
        'cancelled_at',
        'closed_at',
        'customer_name',
        'customer_phone',
        'customer_note',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_location_note',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => OrderStatus::class,
            'source' => OrderSource::class,
            'service_type' => PosServiceType::class,
            'table_number' => 'integer',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'closed_at' => 'datetime',
        ];
    }

    public function isOpenTable(): bool
    {
        return $this->service_type === PosServiceType::Table
            && $this->status === OrderStatus::Open;
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->total - (float) ($this->paid_amount ?? 0), 2));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function storefrontCustomer(): BelongsTo
    {
        return $this->belongsTo(StorefrontCustomer::class);
    }

    public function savedAddress(): BelongsTo
    {
        return $this->belongsTo(StorefrontCustomerAddress::class, 'saved_address_id');
    }

    public function deliveryMapUrl(): ?string
    {
        if ($this->delivery_latitude === null || $this->delivery_longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.$this->delivery_latitude.','.$this->delivery_longitude;
    }

    public function getItemsSummaryAttribute(): string
    {
        return $this->items
            ->map(fn (OrderItem $item) => $item->product_name.' × '.$item->quantity.' — '.$item->line_total.' $')
            ->implode("\n");
    }
}
