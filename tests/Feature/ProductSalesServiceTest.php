<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ProductSalesService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductSalesServiceTest extends TestCase
{
    public function test_grand_total_and_top_products_from_countable_orders(): void
    {
        ProductSalesService::forgetCache();

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-sales']);

        $coffee = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'قهوة',
            'slug' => 'coffee-sales',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        $tea = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاي',
            'slug' => 'tea-sales',
            'price' => 5,
            'status' => ProductStatus::Published,
        ]);

        $juice = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'عصير',
            'slug' => 'juice-sales',
            'price' => 8,
            'status' => ProductStatus::Published,
        ]);

        $completedOrder = Order::query()->create([
            'status' => OrderStatus::Completed,
            'source' => OrderSource::Pos,
            'total' => 35,
        ]);

        OrderItem::query()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $coffee->id,
            'product_name' => $coffee->name,
            'quantity' => 2,
            'unit_price' => 10,
            'line_total' => 20,
        ]);

        OrderItem::query()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $tea->id,
            'product_name' => $tea->name,
            'quantity' => 3,
            'unit_price' => 5,
            'line_total' => 15,
        ]);

        $reviewedOrder = Order::query()->create([
            'status' => OrderStatus::Reviewed,
            'source' => OrderSource::Website,
            'total' => 16,
        ]);

        OrderItem::query()->create([
            'order_id' => $reviewedOrder->id,
            'product_id' => $juice->id,
            'product_name' => $juice->name,
            'quantity' => 2,
            'unit_price' => 8,
            'line_total' => 16,
        ]);

        $cancelledOrder = Order::query()->create([
            'status' => OrderStatus::Cancelled,
            'source' => OrderSource::Website,
            'total' => 99,
        ]);

        OrderItem::query()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $coffee->id,
            'product_name' => $coffee->name,
            'quantity' => 9,
            'unit_price' => 10,
            'line_total' => 90,
        ]);

        $service = app(ProductSalesService::class);

        $this->assertSame(51.0, $service->grandTotalRevenue());
        $this->assertSame(7, $service->grandTotalQuantity());

        $top = $service->topProducts(3);

        $this->assertCount(3, $top);
        $this->assertSame('شاي', $top[0]->product_name);
        $this->assertSame(3, $top[0]->total_qty);
        $this->assertSame('قهوة', $top[1]->product_name);
        $this->assertSame('عصير', $top[2]->product_name);

        $cached = app(ProductSalesService::class)->productBreakdown();

        $this->assertInstanceOf(Collection::class, $cached);
        $this->assertSame(51.0, $cached->sum('total_revenue'));
    }

    public function test_product_query_includes_sales_aggregates(): void
    {
        ProductSalesService::forgetCache();

        $category = Category::query()->create(['name' => 'حلويات', 'slug' => 'sweets-sales']);

        $cake = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'كيك',
            'slug' => 'cake-sales',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::Completed,
            'source' => OrderSource::Pos,
            'total' => 40,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $cake->id,
            'product_name' => $cake->name,
            'quantity' => 2,
            'unit_price' => 20,
            'line_total' => 40,
        ]);

        $row = app(ProductSalesService::class)
            ->appendSalesAggregatesToProductsQuery(Product::query())
            ->whereKey($cake->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row->sold_quantity);
        $this->assertSame(40.0, (float) $row->sold_revenue);
    }
}
