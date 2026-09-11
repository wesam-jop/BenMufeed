<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'decimal_places',
        'rate_to_primary',
        'is_primary',
        'show_on_receipt',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'rate_to_primary' => 'float',
            'is_primary' => 'boolean',
            'show_on_receipt' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function convertFromPrimary(float $amountInPrimary): float
    {
        $rate = (float) $this->rate_to_primary;

        if ($rate <= 0) {
            return 0.0;
        }

        // rate_to_primary = كم تساوي وحدة واحدة من العملة الرئيسية بهذه العملة
        // مثال: 1 USD = 12500 SYP → المبلغ بالسوري = المبلغ بالدولار × 12500
        return round($amountInPrimary * $rate, (int) $this->decimal_places);
    }

    public function format(float $amount): string
    {
        $decimals = (int) $this->decimal_places;
        $number = number_format($amount, $decimals, '.', ',');

        return $this->symbol_position === 'before'
            ? trim($this->symbol.' '.$number)
            : trim($number.' '.$this->symbol);
    }
}
