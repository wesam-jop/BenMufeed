<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends Model
{
    protected $fillable = [
        'production_order_id',
        'product_id',
        'planned_qty',
        'actual_qty',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:4',
            'actual_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
