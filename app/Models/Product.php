<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use BelongsToBranch, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'category_id',
        'type',
        'name',
        'sku',
        'barcode',
        'description',
        'image',
        'unit_id',
        'tax_rate_id',
        'sale_price',
        'cost_price',
        'track_stock',
        'allow_negative_stock',
        'is_active',
        'min_stock',
        'max_stock',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:4',
            'track_stock' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
            'min_stock' => 'decimal:4',
            'max_stock' => 'decimal:4',
        ];
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_groups')
            ->using(ProductModifierGroup::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function recipeItemsAsIngredient(): HasMany
    {
        return $this->hasMany(RecipeItem::class, 'ingredient_product_id');
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function favoritedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_favorites')
            ->using(CustomerFavorite::class);
    }
}
