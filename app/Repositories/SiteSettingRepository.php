<?php

namespace App\Repositories;

use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SiteSettingRepository implements SiteSettingRepositoryInterface
{
    private const CACHE_KEY = 'site_settings.current';

    public function current(): SiteSetting
    {
        $id = Cache::remember(self::CACHE_KEY, 3600, fn (): int => SiteSetting::query()
            ->firstOrCreate(
                ['id' => 1],
                [
                    'site_name' => config('app.name'),
                    'whatsapp_phone' => '963900000000',
                ]
            )
            ->getKey());

        return SiteSetting::query()
            ->with('media')
            ->findOrFail($id);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
