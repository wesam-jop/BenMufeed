<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreCurrencyNoticeWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -100;

    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $symbol = config('store.currency_symbol', '$');
        $code = config('store.currency_code', 'USD');

        return [
            Stat::make('عملة القائمة', "{$symbol} {$code}")
                ->description(config('store.currency_notice'))
                ->descriptionIcon(Heroicon::OutlinedInformationCircle)
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('warning'),
        ];
    }
}
