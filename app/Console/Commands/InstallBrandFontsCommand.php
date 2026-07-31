<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class InstallBrandFontsCommand extends Command
{
    protected $signature = 'benmufeed:install-brand-fonts';

    protected $description = 'تنزيل خطوط El Messiri و Tajawal لبطاقات QR (نفس خط الموقع)';

    public function handle(): int
    {
        $dir = resource_path('fonts');
        File::ensureDirectoryExists($dir);

        $this->installElMessiri($dir);

        $tajawal = [
            'Tajawal-Regular.ttf' => 'Tajawal-Regular.ttf',
            'Tajawal-Medium.ttf' => 'Tajawal-Medium.ttf',
            'Tajawal-Bold.ttf' => 'Tajawal-Bold.ttf',
        ];

        foreach ($tajawal as $target => $source) {
            $this->downloadFile(
                'https://github.com/google/fonts/raw/main/ofl/tajawal/'.$source,
                $dir.'/'.$target,
            );
        }

        $this->info('تم تجهيز الخطوط في: '.$dir);

        return self::SUCCESS;
    }

    private function installElMessiri(string $dir): void
    {
        $zipPath = storage_path('app/brand-fonts-el-messiri.zip');
        $response = Http::timeout(120)->get(
            'https://gwfh.mranftl.com/api/fonts/el-messiri?subsets=arabic,latin&download=zip',
        );

        if (! $response->successful()) {
            $this->warn('تعذّر تنزيل El Messiri.');

            return;
        }

        File::put($zipPath, $response->body());
        $extract = storage_path('app/brand-fonts-el-messiri');
        File::deleteDirectory($extract);
        File::ensureDirectoryExists($extract);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            $this->warn('ملف El Messiri غير صالح.');

            return;
        }

        $zip->extractTo($extract);
        $zip->close();
        File::delete($zipPath);

        $map = [
            'el-messiri-v25-arabic_latin-500.ttf' => 'ElMessiri-Medium.ttf',
            'el-messiri-v25-arabic_latin-700.ttf' => 'ElMessiri-Bold.ttf',
        ];

        foreach ($map as $source => $target) {
            $from = $extract.'/'.$source;
            if (is_file($from)) {
                File::copy($from, $dir.'/'.$target);
                $this->line("  ✓ {$target}");
            }
        }

        File::deleteDirectory($extract);
    }

    private function downloadFile(string $url, string $target): void
    {
        if (is_file($target)) {
            $this->line('  ○ '.basename($target).' (موجود)');

            return;
        }

        $response = Http::timeout(60)->get($url);
        if ($response->successful()) {
            File::put($target, $response->body());
            $this->line('  ✓ '.basename($target));
        } else {
            $this->warn('  ✗ '.basename($target));
        }
    }
}
