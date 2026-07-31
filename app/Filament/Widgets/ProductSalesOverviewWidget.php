<?php

namespace App\Filament\Widgets;

use App\Services\ProductSalesService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductSalesOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'إحصائيات مبيعات الأطباق';

    protected ?string $description = 'إجمالي المبيعات وأكثر الأطباق طلباً — كاشير، اونلاين، وطاولات مفتوحة';

    protected function getStats(): array
    {
        $service = app(ProductSalesService::class);
        $currency = config('store.currency_symbol', '$');
        $grandTotal = $service->grandTotalRevenue();
        $totalQty = $service->grandTotalQuantity();
        $top = $service->topProducts(3);

        $stats = [
            Stat::make('إجمالي مبيعات الأطباق', number_format($grandTotal, 2).' '.$currency)
                ->description(number_format($totalQty).' وحدة مباعة')
                ->color('success')
                ->icon(Heroicon::OutlinedBanknotes),
        ];

        $rankIcons = [
            Heroicon::OutlinedTrophy,
            Heroicon::OutlinedStar,
            Heroicon::OutlinedFire,
        ];

        foreach ($top as $index => $row) {
            $stats[] = Stat::make(
                ($index + 1).'. '.$row->product_name,
                number_format($row->total_qty).' — '.number_format($row->total_revenue, 2).' '.$currency,
            )
                ->description('كمية — إجمالي مبيعات الطبق')
                ->color('warning')
                ->icon($rankIcons[$index] ?? Heroicon::OutlinedShoppingBag);
        }

        if ($top->isEmpty()) {
            $stats[] = Stat::make('أكثر الأطباق مبيعاً', '—')
                ->description('لا توجد مبيعات مسجّلة بعد')
                ->color('gray')
                ->icon(Heroicon::OutlinedChartBar);
        }

        return $stats;
    }
}
