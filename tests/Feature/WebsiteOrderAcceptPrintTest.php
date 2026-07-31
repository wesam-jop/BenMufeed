<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\WebsiteOrderWorkflowService;
use App\Support\PrintableOrder;
use Tests\TestCase;

class WebsiteOrderAcceptPrintTest extends TestCase
{
    public function test_reviewed_delivery_order_can_print_cashier_receipt(): void
    {
        SiteSetting::current();
        PosSetting::current();

        $order = Order::query()->create([
            'status' => OrderStatus::Reviewed,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Delivery,
            'total' => 42,
            'delivery_fee' => 2,
            'customer_name' => 'ليلى',
            'customer_phone' => '+963912345678',
        ]);

        $this->assertTrue(PrintableOrder::canPrintCashierReceipt($order));

        $admin = User::factory()->create(['is_admin' => true, 'role' => \App\Enums\UserRole::Admin]);
        $this->actingAs($admin)
            ->get(route('print.cashier-receipt', $order))
            ->assertOk();
    }

    public function test_accept_creates_kitchen_ticket(): void
    {
        SiteSetting::current();
        PosSetting::current();

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-print']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاي',
            'slug' => 'tea-print',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Takeaway,
            'total' => 10,
            'customer_name' => 'زبون',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
        ]);

        $admin = User::factory()->create(['is_admin' => true, 'role' => \App\Enums\UserRole::Admin]);
        $acceptance = app(WebsiteOrderWorkflowService::class)->accept($order, $admin);

        $this->assertNotNull($acceptance->kitchenTicket);
        $this->assertSame(OrderStatus::Reviewed, $acceptance->order->status);
    }
}
