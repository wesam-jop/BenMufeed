<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesInvoice;
use App\Support\RestaurantMetricsCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DailySalesService
{
    /**
     * @return array<string, mixed>
     */
    public function report(string $date): array
    {
        $ttl = $date === now()->toDateString() ? 60 : 600;

        return Cache::remember(RestaurantMetricsCache::dailyReportKey($date), $ttl, fn (): array => $this->buildReport($date));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildReport(string $date): array
    {
        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $summary = app(SalesAggregateService::class)->buildSummary($date);

        return [
            'date' => $date,
            'summary' => $summary,
            'products' => $this->productBreakdown($from, $to),
            'orders' => $this->completedPosOrders($from, $to),
            'invoices' => $this->confirmedSalesInvoices($from, $to),
            'open_tables' => $this->openTableOrders(),
        ];
    }

    /**
     * @return Collection<int, Order>
     */
    protected function completedPosOrders(Carbon $from, Carbon $to): Collection
    {
        $query = Order::query()
            ->with(['items', 'cashier'])
            ->withCount('items')
            ->where('source', OrderSource::Pos)
            ->where('status', OrderStatus::Completed);

        $this->applyClosedDateRange($query, $from, $to);

        return $query
            ->orderByRaw('COALESCE(closed_at, created_at) DESC')
            ->get();
    }

    /**
     * @return Collection<int, Order>
     */
    protected function openTableOrders(): Collection
    {
        return Order::query()
            ->withCount('items')
            ->where('source', OrderSource::Pos)
            ->where('service_type', PosServiceType::Table)
            ->where('status', OrderStatus::Open)
            ->orderBy('table_number')
            ->get();
    }

    /**
     * @return Collection<int, SalesInvoice>
     */
    protected function confirmedSalesInvoices(Carbon $from, Carbon $to): Collection
    {
        return SalesInvoice::query()
            ->with('customer')
            ->where('status', InvoiceStatus::Confirmed)
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->whereDate('invoice_date', '<=', $to->toDateString())
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, object{product_id: ?int, product_name: string, total_qty: int, total_revenue: float}>
     */
    protected function productBreakdown(Carbon $from, Carbon $to): Collection
    {
        $query = OrderItem::query()
            ->selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(line_total) as total_revenue')
            ->whereHas('order', function ($orderQuery) use ($from, $to): void {
                $orderQuery
                    ->where('source', OrderSource::Pos)
                    ->where('status', OrderStatus::Completed);

                $this->applyClosedDateRange($orderQuery, $from, $to);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty');

        return $query->get()->map(fn ($row) => (object) [
            'product_id' => $row->product_id,
            'product_name' => $row->product_name,
            'total_qty' => (int) $row->total_qty,
            'total_revenue' => round((float) $row->total_revenue, 2),
        ]);
    }

    protected function applyClosedDateRange($query, Carbon $from, Carbon $to): void
    {
        $query->where(function ($inner) use ($from, $to): void {
            $inner->where(function ($closed) use ($from, $to): void {
                $closed->whereNotNull('closed_at')
                    ->whereDate('closed_at', '>=', $from->toDateString())
                    ->whereDate('closed_at', '<=', $to->toDateString());
            })->orWhere(function ($legacy) use ($from, $to): void {
                $legacy->whereNull('closed_at')
                    ->whereDate('created_at', '>=', $from->toDateString())
                    ->whereDate('created_at', '<=', $to->toDateString());
            });
        });
    }
}
