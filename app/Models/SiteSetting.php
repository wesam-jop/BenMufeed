<?php

namespace App\Models;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_name',
        'site_description',
        'whatsapp_phone',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'venue_scan_token',
        'hero_title',
        'hero_subtitle',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));

        $this->addMediaCollection('hero_banner')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('logo_webp')
            ->fit(Fit::Contain, 320, 120)
            ->format('webp')
            ->quality(90)
            ->performOnCollections('logo')
            ->nonQueued();

        $this->addMediaConversion('banner_webp')
            ->fit(Fit::Crop, 1920, 640)
            ->format('webp')
            ->quality(88)
            ->performOnCollections('hero_banner')
            ->nonQueued();
    }

    public static function current(): self
    {
        return static::query()
            ->with('media')
            ->firstOrCreate(
                ['id' => 1],
                [
                    'site_name' => config('app.name'),
                    'whatsapp_phone' => '',
                ]
            );
    }

    /**
     * رمز الدخول الموحّد للطاولات (باركود واحد لكل المطعم).
     * يُولّد تلقائياً عند أول استدعاء إن لم يكن موجوداً.
     */
    public function venueScanToken(): string
    {
        if (blank($this->venue_scan_token)) {
            $this->forceFill([
                'venue_scan_token' => static::generateVenueScanToken(),
            ])->save();
        }

        return $this->venue_scan_token;
    }

    public static function generateVenueScanToken(): string
    {
        return \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(32));
    }

    protected static function booted(): void
    {
        static::saved(fn () => StorefrontCache::forgetSite());
        static::deleted(fn () => StorefrontCache::forgetSite());
    }

    /**
     * شعار المتجر من لوحة الإعدادات (مجموعة media: logo).
     */
    public function logoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo');

        if ($media === null) {
            return null;
        }

        if ($media->hasGeneratedConversion('logo_webp')) {
            $webp = $media->getUrl('logo_webp');

            if ($webp !== '') {
                return $webp;
            }
        }

        $url = $media->getUrl();

        return $url !== '' ? $url : null;
    }
}
