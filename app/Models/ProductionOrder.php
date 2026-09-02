<?php

namespace App\Models;

use App\Enums\ProductionOrderStatus;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'product_id',
        'warehouse_id',
        'order_number',
        'planned_qty',
        'actual_qty',
        'status',
        'unit_cost',
        'total_cost',
        'produced_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:4',
            'actual_qty' => 'decimal:4',
            'status' => ProductionOrderStatus::class,
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'produced_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }
}
