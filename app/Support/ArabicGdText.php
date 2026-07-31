<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

/**
 * Prepares Arabic strings for GD imagettftext (connected glyphs + visual order).
 */
final class ArabicGdText
{
    private static ?Arabic $arabic = null;

    public static function shape(string $text): string
    {
        if (! self::containsArabic($text)) {
            return $text;
        }

        return self::engine()->utf8Glyphs($text, 500);
    }

    private static function engine(): Arabic
    {
        return self::$arabic ??= new Arabic;
    }

    public static function containsArabic(string $text): bool
    {
        return preg_match('/\p{Arabic}/u', $text) === 1;
    }
}
