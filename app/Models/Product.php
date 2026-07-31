<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Support\ProductMetadataGenerator;
use App\Support\SeoText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use Searchable;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'is_featured',
        'is_delivery_available',
        'status',
        'seo_title',
        'seo_description',
        'seo_focus_keywords',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_delivery_available' => 'boolean',
            'status' => ProductStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 480, 480)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('images')
            ->nonQueued();

        $this->addMediaConversion('large')
            ->fit(Fit::Contain, 1200, 1200)
            ->format('webp')
            ->quality(88)
            ->performOnCollections('images')
            ->nonQueued();
    }

    public function primaryImageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('images', 'thumb');

        return $url !== '' ? $url : ($this->getFirstMediaUrl('images') ?: null);
    }

    public function socialImageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('images', 'large');

        return $url !== '' ? $url : $this->primaryImageUrl();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeAddons(): HasMany
    {
        return $this->addons()->where('is_active', true);
    }

    public function hasOptions(): bool
    {
        $this->loadCount(['activeVariants', 'activeAddons']);

        return ($this->active_variants_count ?? 0) > 0 || ($this->active_addons_count ?? 0) > 0;
    }

    public function startingPrice(): float
    {
        $this->loadMissing('activeVariants');

        if ($this->activeVariants->isNotEmpty()) {
            return (float) $this->activeVariants->min('price');
        }

        return (float) $this->price;
    }

    public function recipeCost(): float
    {
        $this->loadMissing('recipeItems.material');

        return round(
            $this->recipeItems->sum(fn (ProductRecipeItem $item) => $item->lineCost()),
            2,
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            ProductMetadataGenerator::apply($product);
        });

        static::saved(function (): void {
            \App\Support\PosCatalogCache::forget();
            \App\Support\StorefrontCache::forgetNavigation();
        });
        static::deleted(function (): void {
            \App\Support\PosCatalogCache::forget();
            \App\Support\StorefrontCache::forgetNavigation();
        });
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => SeoText::plainText($this->description),
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'price' => (float) $this->price,
            'is_featured' => $this->is_featured,
            'is_delivery_available' => (bool) $this->is_delivery_available,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === ProductStatus::Published;
    }
}
