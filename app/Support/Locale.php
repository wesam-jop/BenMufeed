<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class Locale
{
    /** @var list<string> */
    public const AVAILABLE = ['ar', 'en'];

    public const DEFAULT = 'ar';

    public static function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, self::AVAILABLE, true) ? $locale : self::DEFAULT;
    }

    public static function isRtl(string $locale): bool
    {
        return self::normalize($locale) === 'ar';
    }

    public static function dir(string $locale): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    public static function label(string $locale): string
    {
        return match (self::normalize($locale)) {
            'en' => 'English',
            default => 'العربية',
        };
    }

    /** @return array<string, string> */
    public static function translations(string $locale): array
    {
        $locale = self::normalize($locale);

        return Cache::remember("ui.translations.{$locale}", 3600, function () use ($locale) {
            $path = lang_path("{$locale}.json");

            if (! File::exists($path)) {
                return [];
            }

            /** @var array<string, string> $data */
            $data = json_decode(File::get($path), true) ?: [];

            return $data;
        });
    }

    public static function clearTranslationCache(): void
    {
        foreach (self::AVAILABLE as $locale) {
            Cache::forget("ui.translations.{$locale}");
        }
    }
}
