<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'unit',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'min_stock',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function productRecipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function isLowStock(): bool
    {
        if ($this->min_stock === null) {
            return false;
        }

        return (float) $this->stock_quantity <= (float) $this->min_stock;
    }

    public function stockValue(): float
    {
        return round((float) $this->stock_quantity * (float) $this->purchase_price, 2);
    }

    public static function totalActiveStockValue(): float
    {
        return round((float) static::query()
            ->where('is_active', true)
            ->selectRaw('COALESCE(SUM(stock_quantity * purchase_price), 0) as total')
            ->value('total'), 2);
    }

    public static function lowStockCount(): int
    {
        return static::query()
            ->where('is_active', true)
            ->whereNotNull('min_stock')
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->count();
    }

    protected static function booted(): void
    {
        static::creating(function (Material $material): void {
            if (filled($material->sku)) {
                return;
            }

            $material->sku = static::generateUniqueSku();
        });
    }

    public static function generateUniqueSku(): string
    {
        $prefix = 'BM-';
        $last = static::query()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('sku');

        $sequence = 1;

        if (is_string($last) && preg_match('/^BM-(\d+)$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        do {
            $sku = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
