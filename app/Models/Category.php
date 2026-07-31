<?php

namespace App\Models;

use App\Support\CategoryMetadataGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Category extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'seo_focus_keywords',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 640, 400)
            ->format('webp')
            ->quality(86)
            ->performOnCollections('image')
            ->nonQueued();
    }

    public function coverImageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('image', 'thumb');

        return $url !== '' ? $url : ($this->getFirstMediaUrl('image') ?: null);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            CategoryMetadataGenerator::apply($category);
        });

        static::saved(function (): void {
            \App\Support\PosCatalogCache::forget();
            \App\Support\StorefrontCache::forgetNavigation();
            \App\Filament\Support\CategoryOptions::forgetCache();
        });
        static::deleted(function (): void {
            \App\Support\PosCatalogCache::forget();
            \App\Support\StorefrontCache::forgetNavigation();
            \App\Filament\Support\CategoryOptions::forgetCache();
        });
    }
}
