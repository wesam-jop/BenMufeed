<?php

namespace Tests\Feature;

use App\Enums\KitchenTicketKind;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\WebsiteOrderWorkflowService;
use Tests\Concerns\VerifiesStorefrontCustomer;
use Tests\TestCase;

class WebsiteOrderWorkflowTest extends TestCase
{
    use VerifiesStorefrontCustomer;

    public function test_website_pickup_order_waits_for_acceptance_before_kitchen(): void
    {
        SiteSetting::current();
        PosSetting::current();
        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('أحمد', '912345678');

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-wf']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'لاتيه',
            'slug' => 'latte-wf',
            'price' => 30,
            'status' => ProductStatus::Published,
        ]);

        $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'customer_name' => 'أحمد',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
            ])->assertRedirect(route('orders.thank-you'));

        $this->assertDatabaseCount('kitchen_tickets', 0);

        $order = Order::query()->firstOrFail();
        $admin = User::factory()->create(['is_admin' => true, 'role' => \App\Enums\UserRole::Admin]);

        $this->actingAs($admin);
        app(WebsiteOrderWorkflowService::class)->accept($order, $admin);

        $order->refresh();
        $this->assertSame(OrderStatus::Reviewed, $order->status);
        $this->assertDatabaseCount('kitchen_tickets', 1);
        $this->assertSame(
            KitchenTicketKind::TakeawayWebsite,
            KitchenTicket::query()->first()->kind,
        );
    }

    public function test_delivery_order_includes_fee(): void
    {
        SiteSetting::current();
        PosSetting::current()->update(['delivery_fee' => 5]);
        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('ليلى', '912345678', 'delivery');

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-del']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'موكا',
            'slug' => 'mocha-del',
            'price' => 35,
            'status' => ProductStatus::Published,
        ]);

        $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'delivery',
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'customer_name' => 'ليلى',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
                'delivery_latitude' => 33.5138,
                'delivery_longitude' => 36.2765,
                'delivery_location_note' => 'شارع الرئيسي',
            ])->assertRedirect(route('orders.thank-you'));

        $order = Order::query()->firstOrFail();
        $this->assertSame(PosServiceType::Delivery, $order->service_type);
        $this->assertSame('40.00', (string) $order->total);
        $this->assertSame('5.00', (string) $order->delivery_fee);
    }
}
