<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Material;
use App\Models\Order;
use App\Services\PosService;
use App\Services\PrintJobService;
use App\Services\WebsiteOrderWorkflowService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class RestaurantDashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'ملخص اليوم';

    protected ?string $description = 'مؤشرات سريعة — تُحدَّث كل دقيقة';

    protected function getStats(): array
    {
        $metrics = Cache::remember('filament.dashboard_stats', 60, fn (): array => $this->metrics());

        return $this->statsFromMetrics($metrics);
    }

    /**
     * أرقام فقط — آمنة للتخزين في الكاش (لا كائنات Filament).
     *
     * @return array{
     *     sales_today: float,
     *     orders_today: int,
     *     pending_website: int,
     *     open_tables: int,
     *     low_stock: int,
     *     failed_prints: int,
     * }
     */
    protected function metrics(): array
    {
        $today = now()->toDateString();

        return [
            'sales_today' => (float) Order::query()
                ->whereDate('created_at', $today)
                ->where('status', OrderStatus::Completed)
                ->sum('total'),
            'orders_today' => Order::query()
                ->whereDate('created_at', $today)
                ->count(),
            'pending_website' => app(WebsiteOrderWorkflowService::class)->pendingWebsiteOrdersCount(),
            'open_tables' => app(PosService::class)->openTablesCount(),
            'low_stock' => Material::lowStockCount(),
            'failed_prints' => app(PrintJobService::class)->failedCount(),
        ];
    }

    /**
     * @param  array{
     *     sales_today: float,
     *     orders_today: int,
     *     pending_website: int,
     *     open_tables: int,
     *     low_stock: int,
     *     failed_prints: int,
     * }  $metrics
     * @return array<int, Stat>
     */
    protected function statsFromMetrics(array $metrics): array
    {
        $currency = config('store.currency_symbol', '$');
        $pendingWebsite = (int) $metrics['pending_website'];
        $openTables = (int) $metrics['open_tables'];
        $lowStock = (int) $metrics['low_stock'];
        $failedPrints = (int) $metrics['failed_prints'];

        return [
            Stat::make('مبيعات اليوم', number_format((float) $metrics['sales_today'], 2).' '.$currency)
                ->description('طلبات مكتملة')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('طلبات اليوم', (string) $metrics['orders_today'])
                ->description('كل المصادر')
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('طلبات موقع بانتظار القبول', (string) $pendingWebsite)
                ->description('سفري + توصيل')
                ->color($pendingWebsite > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-globe-alt')
                ->url($pendingWebsite > 0 ? url('/admin/orders?tableFilters[status][value]=new') : null),
            Stat::make('طاولات مفتوحة', (string) $openTables)
                ->description('في نقطة البيع')
                ->color($openTables > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-table-cells')
                ->url(url('/pos/tables')),
            Stat::make('مواد منخفضة', (string) $lowStock)
                ->description('تحتاج إعادة تموين')
                ->color($lowStock > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-archive-box')
                ->url(url('/admin/materials')),
            Stat::make('طباعات فاشلة', (string) $failedPrints)
                ->description('تحتاج إعادة')
                ->color($failedPrints > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-printer')
                ->url($failedPrints > 0 ? url('/pos/print-log') : null),
        ];
    }
}
