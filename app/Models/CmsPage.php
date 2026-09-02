<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    protected $fillable = [
        'title',
        'path',
        'locale',
        'is_system',
        'is_published',
        'theme',
        'seo_title',
        'seo_description',
        'sections',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_published' => 'boolean',
            'theme' => 'array',
            'sections' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CmsPage $page): void {
            $page->locale = Locale::normalize($page->locale ?: Locale::DEFAULT);
        });

        static::saved(function (CmsPage $page): void {
            static::forgetCache($page->path, $page->locale);
        });

        static::deleted(function (CmsPage $page): void {
            static::forgetCache($page->path, $page->locale);
        });

        static::deleting(function (CmsPage $page): void {
            if ($page->is_system) {
                throw new \RuntimeException('لا يمكن حذف صفحات النظام.');
            }
        });

        static::updating(function (CmsPage $page): void {
            if (! $page->is_system) {
                return;
            }

            if ($page->isDirty('path')) {
                $page->path = $page->getOriginal('path');
            }

            if ($page->isDirty('locale')) {
                $page->locale = $page->getOriginal('locale');
            }

            if ($page->isDirty('is_system') && ! $page->is_system) {
                $page->is_system = true;
            }

            if ($page->isDirty('is_published') && ! $page->is_published) {
                $page->is_published = true;
            }
        });
    }

    public static function cacheKey(string $path, string $locale): string
    {
        return 'cms.page.'.Locale::normalize($locale).'.'.static::normalizePath($path);
    }

    public static function forgetCache(string $path, string $locale): void
    {
        Cache::forget(static::cacheKey($path, $locale));
    }

    /** @return list<string> */
    public static function systemPaths(): array
    {
        return [
            'home',
            'about',
            'contact',
            'categories',
            'products',
            'cart',
            'checkout-success',
            'errors/404',
            'errors/403',
            'errors/500',
        ];
    }

    /** @return list<string> */
    public static function reservedPaths(): array
    {
        return [
            'admin', 'pos', 'api', 'login', 'logout', 'register', 'password',
            'email', 'dashboard', 'settings', 'cart', 'checkout', 'products',
            'categories', 'customer', 'customers', 'table-order', 'order', 'orders',
            'kitchen', 'storage', 'sanctum', 'up', 'horizon', 'livewire',
            'two-factor-challenge', 'user', 'profile', 'errors', 'build',
            'vendor', 'assets', 'css', 'js', 'fonts', 'favicon.ico',
            'checkout-success', 'home', 'about', 'contact', 'locale',
        ];
    }

    /** @return list<string> */
    public static function blockedCustomPaths(): array
    {
        return array_values(array_unique([
            ...static::reservedPaths(),
            ...static::systemPaths(),
        ]));
    }

    public static function normalizePath(string $path): string
    {
        $path = Str::lower(trim($path));
        $path = trim($path, '/');
        $path = preg_replace('/[^a-z0-9\-\/]+/', '-', $path) ?? $path;
        $path = preg_replace('/-+/', '-', $path) ?? $path;

        return trim($path, '/-') ?: 'page';
    }

    public function publicUrl(): string
    {
        return match ($this->path) {
            'home' => url('/'),
            'checkout-success' => url('/checkout/success-preview'),
            default => url('/'.$this->path),
        };
    }

    public function displayPath(): string
    {
        return match ($this->path) {
            'home' => '/',
            'checkout-success' => '/checkout/success/{order}',
            'errors/404' => '404',
            'errors/403' => '403',
            'errors/500' => '500',
            default => '/'.$this->path,
        };
    }

    public static function findPublishedByPath(string $path, ?string $locale = null): ?self
    {
        $normalized = $path === '' || $path === '/' ? 'home' : static::normalizePath($path);
        $locale = Locale::normalize($locale ?? app()->getLocale());

        return Cache::remember(static::cacheKey($normalized, $locale), 300, function () use ($normalized, $locale) {
            return static::query()
                ->where('path', $normalized)
                ->where('locale', $locale)
                ->where('is_published', true)
                ->first();
        });
    }

    public static function systemPage(string $path, ?string $locale = null): ?self
    {
        $locale = Locale::normalize($locale ?? app()->getLocale());

        return static::query()
            ->where('path', $path)
            ->where('locale', $locale)
            ->where('is_system', true)
            ->first();
    }

    public static function pageGroup(string $path): string
    {
        return match (true) {
            str_starts_with($path, 'errors/') => 'errors',
            in_array($path, ['cart', 'checkout-success'], true) => 'commerce',
            in_array($path, ['home', 'about', 'contact', 'categories', 'products'], true) => 'site',
            default => 'custom',
        };
    }

    public static function groupLabel(string $group): string
    {
        return match ($group) {
            'site' => app()->getLocale() === 'en' ? 'Site pages' : 'صفحات الموقع',
            'commerce' => app()->getLocale() === 'en' ? 'Cart & orders' : 'السلة والطلب',
            'errors' => app()->getLocale() === 'en' ? 'Error pages' : 'صفحات الأخطاء',
            default => app()->getLocale() === 'en' ? 'Custom pages' : 'صفحات مخصصة',
        };
    }

    /** @return array{primary: string, secondary: string, font_family: string, base_font_size: int} */
    public static function defaultTheme(): array
    {
        return [
            'primary' => '#78350f',
            'secondary' => '#a16207',
            'font_family' => 'Cairo',
            'base_font_size' => 16,
        ];
    }

    public function resolvedTheme(): array
    {
        return array_merge(static::defaultTheme(), is_array($this->theme) ? $this->theme : []);
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_system;
    }
}
