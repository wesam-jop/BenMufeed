<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'label',
        'city',
        'district',
        'street',
        'building',
        'floor',
        'apartment',
        'notes',
        'lat',
        'lng',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function formatted(): string
    {
        $parts = array_filter([
            $this->label,
            $this->city !== '—' ? $this->city : null,
            $this->district,
            $this->street,
            $this->building ? "مبنى {$this->building}" : null,
            $this->floor ? "طابق {$this->floor}" : null,
            $this->apartment ? "شقة {$this->apartment}" : null,
            $this->notes,
        ]);

        return implode(' — ', $parts) ?: '—';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
