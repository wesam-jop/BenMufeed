<?php

namespace App\Services;

use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Support\ArabicGdText;
use App\Support\BrandFonts;
use App\Support\FilamentBranding;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\PngWriter;
use GdImage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class TableQrCardRenderer
{
    private const WIDTH = 620;

    private const HEIGHT = 900;

    public function __construct(
        private readonly RestaurantTableService $tables,
    ) {}

    public function render(RestaurantTable $table): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required to generate table QR cards.');
        }

        $settings = SiteSetting::current();
        $siteName = $settings->site_name ?: config('app.name', 'بن مفيد');
        $siteUrl = $this->displaySiteUrl();
        $scanUrl = $this->tables->scanUrl($table);
        $label = $table->label !== '' && $table->label !== null
            ? $table->label
            : 'طاولة '.$table->number;

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($canvas === false) {
            throw new RuntimeException('Could not create image canvas.');
        }

        $this->fillCanvas($canvas);
        $this->drawFrame($canvas);

        $y = 48;
        $y = $this->drawLogo($canvas, $settings, $y);
        $y = $this->drawCenteredText($canvas, $siteName, $y, 28, FilamentBranding::INK, BrandFonts::displayMedium());
        $y = $this->drawCenteredText($canvas, $siteUrl, $y + 10, 15, FilamentBranding::MUTED, BrandFonts::bodyRegular());
        $y = $this->drawDivider($canvas, $y + 28);

        $y = $this->drawCenteredText($canvas, $label, $y + 36, 24, FilamentBranding::PRIMARY, BrandFonts::displayMedium());
        $y = $this->drawTableNumber($canvas, $table->number, $y + 20);

        $qrTop = $y + 24;
        $this->drawQrBlock($canvas, $scanUrl, $qrTop);

        $hintY = $qrTop + 340;
        $y = $this->drawCenteredText($canvas, 'امسح الرمز لطلبك من الجوال', $hintY, 19, FilamentBranding::MUTED, BrandFonts::bodyMedium());
        $this->drawCenteredText($canvas, $scanUrl, $y + 28, 12, FilamentBranding::MUTED, BrandFonts::bodyRegular());

        ob_start();
        imagepng($canvas, null, 6);
        imagedestroy($canvas);
        $png = ob_get_clean();

        if ($png === false || $png === '') {
            throw new RuntimeException('Could not encode QR card PNG.');
        }

        return $png;
    }

    /**
     * بطاقة الباركود الموحّد لكل الطاولات (باركود واحد للمطعم).
     */
    public function renderVenue(): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required to generate the venue QR card.');
        }

        $settings = SiteSetting::current();
        $siteName = $settings->site_name ?: config('app.name', 'بن مفيد');
        $siteUrl = $this->displaySiteUrl();
        $scanUrl = $this->tables->venueScanUrl();

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($canvas === false) {
            throw new RuntimeException('Could not create image canvas.');
        }

        $this->fillCanvas($canvas);
        $this->drawFrame($canvas);

        $y = 60;
        $y = $this->drawLogo($canvas, $settings, $y);
        $y = $this->drawCenteredText($canvas, $siteName, $y, 30, FilamentBranding::INK, BrandFonts::displayMedium());
        $y = $this->drawCenteredText($canvas, $siteUrl, $y + 10, 15, FilamentBranding::MUTED, BrandFonts::bodyRegular());
        $y = $this->drawDivider($canvas, $y + 30);

        $y = $this->drawCenteredText($canvas, 'اطلب من طاولتك', $y + 46, 30, FilamentBranding::PRIMARY, BrandFonts::displayMedium());
        $y = $this->drawCenteredText($canvas, 'امسح الرمز ثم اختر رقم طاولتك', $y + 22, 18, FilamentBranding::MUTED, BrandFonts::bodyMedium());

        $qrTop = $y + 40;
        $this->drawQrBlock($canvas, $scanUrl, $qrTop);

        $hintY = $qrTop + 360;
        $this->drawCenteredText($canvas, $scanUrl, $hintY, 12, FilamentBranding::MUTED, BrandFonts::bodyRegular());

        ob_start();
        imagepng($canvas, null, 6);
        imagedestroy($canvas);
        $png = ob_get_clean();

        if ($png === false || $png === '') {
            throw new RuntimeException('Could not encode venue QR card PNG.');
        }

        return $png;
    }

    private function displaySiteUrl(): string
    {
        $url = URL::to('/');

        return rtrim(preg_replace('#^https?://#', '', $url) ?? $url, '/');
    }

    private function fillCanvas(GdImage $canvas): void
    {
        $bg = $this->color($canvas, FilamentBranding::SURFACE);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $bg);
    }

    private function drawFrame(GdImage $canvas): void
    {
        $border = $this->color($canvas, FilamentBranding::PRIMARY);
        $accent = $this->color($canvas, FilamentBranding::ACCENT);
        $inner = $this->color($canvas, '#ffffff');

        imagerectangle($canvas, 12, 12, self::WIDTH - 13, self::HEIGHT - 13, $border);
        imagerectangle($canvas, 18, 18, self::WIDTH - 19, self::HEIGHT - 19, $accent);
        imagefilledrectangle($canvas, 24, 24, self::WIDTH - 25, self::HEIGHT - 25, $inner);
        imagefilledrectangle($canvas, 28, 28, self::WIDTH - 29, self::HEIGHT - 29, $this->color($canvas, FilamentBranding::SURFACE));
    }

    private function drawLogo(GdImage $canvas, SiteSetting $settings, int $y): int
    {
        $media = $settings->getFirstMedia('logo');
        if ($media === null) {
            return $y;
        }

        $path = $media->getPath();
        if ($media->hasGeneratedConversion('logo_webp')) {
            $webpPath = $media->getPath('logo_webp');
            if (is_file($webpPath)) {
                $path = $webpPath;
            }
        }

        if (! is_file($path)) {
            return $y;
        }

        $logo = $this->loadImage($path);
        if ($logo === null) {
            return $y;
        }

        $maxW = 220;
        $maxH = 88;
        $srcW = imagesx($logo);
        $srcH = imagesy($logo);
        $scale = min($maxW / $srcW, $maxH / $srcH, 1);
        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);
        $x = (int) ((self::WIDTH - $dstW) / 2);

        imagecopyresampled($canvas, $logo, $x, $y, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($logo);

        return $y + $dstH + 20;
    }

    private function drawDivider(GdImage $canvas, int $y): int
    {
        $line = $this->color($canvas, FilamentBranding::ACCENT);
        imageline($canvas, 120, $y, self::WIDTH - 120, $y, $line);

        return $y;
    }

    private function drawTableNumber(GdImage $canvas, int $number, int $y): int
    {
        $text = (string) $number;
        $font = BrandFonts::displayBold();
        $size = 72;
        $color = $this->color($canvas, FilamentBranding::ACCENT);
        $bbox = imagettfbbox($size, 0, $font, $text);
        $w = (int) abs($bbox[2] - $bbox[0]);
        $x = (int) ((self::WIDTH - $w) / 2);
        $baseline = $y + $size;
        imagettftext($canvas, $size, 0, $x, $baseline, $color, $font, $text);

        return $baseline + 8;
    }

    private function drawQrBlock(GdImage $canvas, string $url, int $top): void
    {
        $qrSize = 280;
        $padding = 20;
        $box = $qrSize + ($padding * 2);
        $left = (int) ((self::WIDTH - $box) / 2);

        $white = $this->color($canvas, '#ffffff');
        $shadow = $this->color($canvas, '#e8dfd2');
        imagefilledrectangle($canvas, $left + 4, $top + 4, $left + $box + 4, $top + $box + 4, $shadow);
        imagefilledrectangle($canvas, $left, $top, $left + $box, $top + $box, $white);

        $border = $this->color($canvas, FilamentBranding::PRIMARY);
        imagerectangle($canvas, $left, $top, $left + $box, $top + $box, $border);

        $qrBinary = (new Builder(
            writer: new PngWriter,
            data: $url,
            size: $qrSize,
            margin: 6,
            foregroundColor: new Color(47, 31, 20),
            backgroundColor: new Color(255, 255, 255),
        ))->build()->getString();

        $qr = imagecreatefromstring($qrBinary);
        if ($qr === false) {
            throw new RuntimeException('Could not decode QR image.');
        }

        $dstX = $left + $padding;
        $dstY = $top + $padding;
        imagecopy($canvas, $qr, $dstX, $dstY, 0, 0, $qrSize, $qrSize);
        imagedestroy($qr);
    }

    private function drawCenteredText(
        GdImage $canvas,
        string $text,
        int $y,
        int $size,
        string $hex,
        string $fontPath,
    ): int {
        if (ArabicGdText::containsArabic($text)) {
            $text = ArabicGdText::shape($text);
        }
        $color = $this->color($canvas, $hex);
        $bbox = imagettfbbox($size, 0, $fontPath, $text);
        $w = (int) abs($bbox[2] - $bbox[0]);
        $h = (int) abs($bbox[7] - $bbox[1]);
        $x = (int) ((self::WIDTH - $w) / 2);
        $baseline = $y + $h;
        imagettftext($canvas, $size, 0, $x, $baseline, $color, $fontPath, $text);

        return $baseline;
    }

    private function color(GdImage $canvas, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        return imagecolorallocate($canvas, $r, $g, $b);
    }

    private function loadImage(string $path): ?GdImage
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);

        return $image instanceof GdImage ? $image : null;
    }
}
