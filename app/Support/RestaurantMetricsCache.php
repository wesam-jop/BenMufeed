<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class RestaurantMetricsCache
{
    public static function forgetAll(?string $saleDate = null): void
    {
        Cache::forget('filament.dashboard_stats');
        Cache::forget(RestaurantMetricsCache::profitsSummaryKey(null, null));

        if ($saleDate !== null) {
            Cache::forget(self::dailyReportKey($saleDate));
            Cache::forget(self::dailySummaryKey($saleDate));
        }
    }

    public static function dailyReportKey(string $date): string
    {
        return 'daily_sales.report.'.$date;
    }

    public static function dailySummaryKey(string $date): string
    {
        return 'daily_sales.summary.'.$date;
    }

    public static function profitsSummaryKey(?string $from, ?string $to): string
    {
        $fromKey = $from ?? 'all';
        $toKey = $to ?? 'all';

        return 'profits.summary.'.$fromKey.'.'.$toKey;
    }
}
