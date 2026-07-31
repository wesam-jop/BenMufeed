<?php

namespace App\Support;

use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use Filament\Support\Colors\Color;

final class FilamentBranding
{
    /** بني إسبريسو — اللون الرئيسي */
    public const PRIMARY = '#5c3d2e';

    /** ذهبي شامبانيا */
    public const ACCENT = '#c9a87c';

    /** خلفية عاجية */
    public const SURFACE = '#f7f2ea';

    /** نص وحبر */
    public const INK = '#2f1f14';

    /** رمادي دافئ */
    public const MUTED = '#8b7355';

    /**
     * لوحة متناسقة — بدون أزرق/أخضر صارخ يتصادم مع الهوية.
     *
     * @return array<string, array<int, string>>
     */
    public static function panelColors(): array
    {
        return [
            'primary' => Color::hex(self::PRIMARY),
            // لا نستبدل gray — Filament يستخدمه لنص الجداول والحقول؛ استبداله يسبب تباين ضعيف.
            'success' => Color::hex('#2d5a40'),
            'warning' => Color::hex('#8b5a2e'),
            'danger' => Color::hex('#8b2e2e'),
            'info' => Color::hex('#5c4a3a'),
        ];
    }

    public static function brandName(): string
    {
        try {
            $name = app(SiteSettingRepositoryInterface::class)->current()->site_name;

            return filled($name) ? $name : 'بن مفيد';
        } catch (\Throwable) {
            return 'بن مفيد';
        }
    }

    public static function brandLogoUrl(): ?string
    {
        try {
            $url = app(SiteSettingRepositoryInterface::class)->current()->logoUrl();

            return filled($url) ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
