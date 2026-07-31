<?php

namespace App\Models;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomeSlider extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected static function booted(): void
    {
        static::creating(function (HomeSlider $slider): void {
            if ($slider->sort_order !== null && $slider->sort_order > 0) {
                return;
            }

            $max = static::query()->max('sort_order');

            $slider->sort_order = ((int) $max) + 1;
        });

        static::saved(fn () => StorefrontCache::forgetHome());
        static::deleted(fn () => StorefrontCache::forgetHome());
    }

    protected $fillable = [
        'title',
        'subtitle',
        'button_text',
        'button_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('slide')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('banner')
            ->fit(Fit::Crop, 1920, 720)
            ->format('webp')
            ->quality(88)
            ->performOnCollections('slide')
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 150)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('slide')
            ->nonQueued();
    }

    public function slideImageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('slide', 'banner');

        return $url !== '' ? $url : ($this->getFirstMediaUrl('slide') ?: null);
    }
}
