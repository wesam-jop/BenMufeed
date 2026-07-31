<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductSalesService
{
    /**
     * حالات الطلب التي تُحسب ضمن مبيعات الأطباق.
     *
     * @return array<int, string>
     */
    public function countableOrderStatuses(): array
    {
        return [
            OrderStatus::Completed->value,
            OrderStatus::Reviewed->value,
            OrderStatus::Open->value,
        ];
    }

    /**
     * @param  Builder<\App\Models\Order>  $query
     */
    public function applyCountableOrdersScope(Builder $query): Builder
    {
        return $query->whereIn('status', $this->countableOrderStatuses());
    }

    /**
     * @return Collection<int, object{
     *     product_id: ?int,
     *     product_name: string,
     *     total_qty: int,
     *     total_revenue: float,
     * }>
     */
    public function productBreakdown(): Collection
    {
        $rows = Cache::remember('product_sales.breakdown.v2', 60, function (): array {
            return OrderItem::query()
                ->selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(line_total) as total_revenue')
                ->whereHas('order', fn (Builder $orderQuery) => $this->applyCountableOrdersScope($orderQuery))
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('total_qty')
                ->get()
                ->map(fn ($row): array => [
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name,
                    'total_qty' => (int) $row->total_qty,
                    'total_revenue' => round((float) $row->total_revenue, 2),
                ])
                ->values()
                ->all();
        });

        return collect($rows)->map(fn (array $row): object => (object) $row);
    }

    public function grandTotalRevenue(): float
    {
        return round((float) $this->productBreakdown()->sum('total_revenue'), 2);
    }

    public function grandTotalQuantity(): int
    {
        return (int) $this->productBreakdown()->sum('total_qty');
    }

    /**
     * @return Collection<int, object{
     *     product_id: ?int,
     *     product_name: string,
     *     total_qty: int,
     *     total_revenue: float,
     * }>
     */
    public function topProducts(int $limit = 3): Collection
    {
        return $this->productBreakdown()->take(max(1, $limit))->values();
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function appendSalesAggregatesToProductsQuery(Builder $query): Builder
    {
        $salesOrder = fn (Builder $orderQuery) => $this->applyCountableOrdersScope($orderQuery);

        return $query->addSelect([
            'sold_quantity' => OrderItem::query()
                ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                ->whereColumn('order_items.product_id', 'products.id')
                ->whereHas('order', $salesOrder),
            'sold_revenue' => OrderItem::query()
                ->selectRaw('COALESCE(SUM(order_items.line_total), 0)')
                ->whereColumn('order_items.product_id', 'products.id')
                ->whereHas('order', $salesOrder),
        ]);
    }

    public static function forgetCache(): void
    {
        Cache::forget('product_sales.breakdown');
        Cache::forget('product_sales.breakdown.v2');
    }
}
