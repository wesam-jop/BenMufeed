<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'price',
        'cost',
        'is_default',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductVariantRecipeItem::class);
    }

    public function unitCost(): float
    {
        if ($this->cost !== null) {
            return (float) $this->cost;
        }

        $this->loadMissing('recipeItems.material');

        if ($this->recipeItems->isNotEmpty()) {
            return round($this->recipeItems->sum(fn (ProductVariantRecipeItem $item) => $item->lineCost()), 2);
        }

        return $this->product?->recipeCost() ?? 0.0;
    }

    public function recipeCost(): float
    {
        return $this->unitCost();
    }
}
