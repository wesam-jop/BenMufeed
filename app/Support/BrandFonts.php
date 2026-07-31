<?php

namespace App\Support;

/**
 * خطوط الهوية — نفس المستخدم في الواجهة (El Messiri + Tajawal).
 */
final class BrandFonts
{
    public const DISPLAY_MEDIUM = 'ElMessiri-Medium.ttf';

    public const DISPLAY_BOLD = 'ElMessiri-Bold.ttf';

    public const BODY_REGULAR = 'Tajawal-Regular.ttf';

    public const BODY_MEDIUM = 'Tajawal-Medium.ttf';

    public static function displayMedium(): string
    {
        return self::resolve(self::DISPLAY_MEDIUM, self::DISPLAY_BOLD, self::BODY_MEDIUM);
    }

    public const BODY_BOLD = 'Tajawal-Bold.ttf';

    public static function displayBold(): string
    {
        return self::resolve(self::DISPLAY_BOLD, self::DISPLAY_MEDIUM, self::BODY_BOLD);
    }

    public static function bodyRegular(): string
    {
        return self::resolve(self::BODY_REGULAR, self::BODY_MEDIUM);
    }

    public static function bodyMedium(): string
    {
        return self::resolve(self::BODY_MEDIUM, self::BODY_REGULAR);
    }

    private static function resolve(string $preferred, string ...$alternates): string
    {
        foreach ([$preferred, ...$alternates] as $file) {
            $path = resource_path('fonts/'.$file);
            if (is_file($path)) {
                return $path;
            }
        }

        $noto = resource_path('fonts/NotoSansArabic-Regular.ttf');
        if (is_file($noto)) {
            return $noto;
        }

        return base_path('vendor/endroid/qr-code/assets/open_sans.ttf');
    }
}
