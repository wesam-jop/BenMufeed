<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontCustomerAddress extends Model
{
    protected $fillable = [
        'storefront_customer_id',
        'label',
        'latitude',
        'longitude',
        'location_note',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StorefrontCustomer::class, 'storefront_customer_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toStorefrontArray(): array
    {
        return [
            'id' => $this->getKey(),
            'label' => $this->label,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'location_note' => $this->location_note,
            'is_default' => $this->is_default,
        ];
    }
}
