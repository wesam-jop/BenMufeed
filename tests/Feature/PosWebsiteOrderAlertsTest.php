<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Livewire\Pos\PosWebsiteOrderAlerts;
use App\Livewire\Pos\PosWebsiteOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PosWebsiteOrderAlertsTest extends TestCase
{
    public function test_cashier_sees_pending_delivery_order_on_website_orders_page(): void
    {
        SiteSetting::current();
        PosSetting::current();

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-pos-alert']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'إسبريسو',
            'slug' => 'esp-pos-alert',
            'price' => 25,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Delivery,
            'total' => 25,
            'customer_name' => 'سامي',
            'customer_phone' => '+963912345678',
            'delivery_latitude' => 33.5,
            'delivery_longitude' => 36.3,
            'delivery_location_note' => 'حي المزة',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 25,
            'line_total' => 25,
        ]);
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        Livewire::actingAs($cashier)
            ->test(PosWebsiteOrders::class)
            ->assertCount('pending', 1)
            ->assertSet('pending.0.id', $order->getKey())
            ->assertSet('pending.0.is_delivery', true)
            ->call('showDetail', $order->getKey())
            ->assertSet('detailOrder.id', $order->getKey())
            ->assertSet('detailOrder.map_url', $order->fresh()->deliveryMapUrl())
            ->call('accept', $order->getKey())
            ->assertSet('flashMessage', "تم قبول الطلب #{$order->getKey()} — طُبع إيصال الكاشير وتذكرة المطبخ.")
            ->assertCount('pending', 0);

        $order->refresh();
        $this->assertSame(OrderStatus::Reviewed, $order->status);
    }

    public function test_delivery_map_url_uses_google_maps(): void
    {
        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Delivery,
            'total' => 10,
            'delivery_latitude' => 33.5138,
            'delivery_longitude' => 36.2765,
        ]);

        $this->assertStringContainsString('google.com/maps', (string) $order->deliveryMapUrl());
    }

    public function test_new_order_triggers_poll_notification_state(): void
    {
        SiteSetting::current();
        PosSetting::current();

        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        $component = Livewire::actingAs($cashier)->test(PosWebsiteOrderAlerts::class);
        $lastSeen = $component->get('lastNotifiedOrderId');

        $category = Category::query()->create(['name' => 'حلويات', 'slug' => 'sweets-pos-alert']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'كيك',
            'slug' => 'cake-pos-alert',
            'price' => 40,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Takeaway,
            'total' => 40,
            'customer_name' => 'نور',
            'customer_phone' => '+963912345678',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 40,
            'line_total' => 40,
        ]);
        $this->assertGreaterThan($lastSeen, $order->getKey());

        $component
            ->call('pollPending')
            ->assertCount('toasts', 1)
            ->assertSet('toasts.0.id', $order->getKey())
            ->assertSet('toasts.0.url', route('pos.website-orders', ['order' => $order->getKey()]));
    }
}
