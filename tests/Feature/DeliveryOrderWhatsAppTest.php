<?php

namespace Tests\Feature;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Enums\ProductStatus;
use App\Events\WebsiteOrderPlaced;
use App\Jobs\SendCustomerDeliveryOrderAcceptedWhatsApp;
use App\Jobs\SendCustomerDeliveryOrderPendingWhatsApp;
use App\Jobs\SendStoreNewDeliveryOrderWhatsApp;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DeliveryOrderWhatsAppService;
use App\Services\WebsiteOrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryOrderWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mirsalli.base_url', 'https://mirsalli.test');
        Config::set('services.mirsalli.send_path', 'sendtextmsg.php');
        Config::set('services.mirsalli.user', 'test-user');
    }

    public function test_website_order_placed_dispatches_whatsapp_jobs_for_delivery(): void
    {
        Bus::fake([
            SendStoreNewDeliveryOrderWhatsApp::class,
            SendCustomerDeliveryOrderPendingWhatsApp::class,
        ]);

        WebsiteOrderPlaced::dispatch($this->createDeliveryOrder()->getKey());

        Bus::assertDispatched(SendStoreNewDeliveryOrderWhatsApp::class);
        Bus::assertDispatched(SendCustomerDeliveryOrderPendingWhatsApp::class);
    }

    public function test_accepting_delivery_order_dispatches_customer_whatsapp_job(): void
    {
        Bus::fake([SendCustomerDeliveryOrderAcceptedWhatsApp::class]);

        $order = $this->createDeliveryOrder();
        $admin = User::factory()->create(['is_admin' => true, 'role' => \App\Enums\UserRole::Admin]);

        app(WebsiteOrderWorkflowService::class)->accept($order, $admin);

        Bus::assertDispatched(
            SendCustomerDeliveryOrderAcceptedWhatsApp::class,
            fn (SendCustomerDeliveryOrderAcceptedWhatsApp $job): bool => $job->orderId === $order->getKey(),
        );
    }

    public function test_store_whatsapp_includes_customer_map_and_order_details(): void
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963911122233']);

        Http::fake([
            'mirsalli.test/*' => Http::response(['ok' => true, 'data' => ['message_id' => 'msg-1']], 200),
        ]);

        $order = $this->createDeliveryOrder();

        $this->assertTrue(app(DeliveryOrderWhatsAppService::class)->notifyStoreNewOrder($order));

        Http::assertSent(function ($request) use ($order): bool {
            $message = (string) $request->data()['message'];

            return $request->url() === 'https://mirsalli.test/sendtextmsg.php'
                && $request->data()['phone'] === '+963911122233'
                && str_contains($message, 'طلب توصيل جديد')
                && str_contains($message, '#'.$order->getKey())
                && str_contains($message, 'ليلى')
                && str_contains($message, '+963912345678')
                && str_contains($message, 'wa.me/963912345678')
                && str_contains($message, 'شارع الرئيسي')
                && str_contains($message, 'Google Maps')
                && str_contains($message, 'google.com/maps')
                && str_contains($message, 'موكا')
                && str_contains($message, '40.00');
        });
    }

    public function test_customer_pending_whatsapp_has_no_order_details(): void
    {
        Http::fake([
            'mirsalli.test/*' => Http::response(['ok' => true, 'data' => ['message_id' => 'msg-pending']], 200),
        ]);

        $order = $this->createDeliveryOrder();

        $this->assertTrue(app(DeliveryOrderWhatsAppService::class)->notifyCustomerPending($order));

        Http::assertSent(function ($request) use ($order): bool {
            $message = (string) $request->data()['message'];

            return $request->data()['phone'] === '+963912345678'
                && str_contains($message, 'بانتظار الموافقة')
                && str_contains($message, '#'.$order->getKey())
                && ! str_contains($message, 'موكا')
                && ! str_contains($message, '40.00');
        });
    }

    public function test_customer_whatsapp_sent_on_acceptance_with_details(): void
    {
        Http::fake([
            'mirsalli.test/*' => Http::response(['ok' => true, 'data' => ['message_id' => 'msg-2']], 200),
        ]);

        $order = $this->createDeliveryOrder();

        $this->assertTrue(app(DeliveryOrderWhatsAppService::class)->notifyCustomerAccepted($order));

        Http::assertSent(function ($request): bool {
            $message = (string) $request->data()['message'];

            return $request->data()['phone'] === '+963912345678'
                && str_contains($message, 'قيد التحضير')
                && str_contains($message, 'تفاصيل الطلب')
                && str_contains($message, 'موكا')
                && str_contains($message, '40.00')
                && str_contains($message, 'شارع الرئيسي');
        });
    }

    public function test_store_whatsapp_for_takeaway_includes_customer_phone(): void
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963911122233']);

        Http::fake([
            'mirsalli.test/*' => Http::response(['ok' => true, 'data' => ['message_id' => 'msg-takeaway']], 200),
        ]);

        $order = $this->createTakeawayOrder();

        $this->assertTrue(app(DeliveryOrderWhatsAppService::class)->notifyStoreNewOrder($order));

        Http::assertSent(function ($request): bool {
            $message = (string) $request->data()['message'];

            return $request->data()['phone'] === '+963911122233'
                && str_contains($message, 'طلب سفري جديد')
                && str_contains($message, '+963988776655')
                && str_contains($message, 'wa.me/963988776655')
                && str_contains($message, 'ساندويش')
                && ! str_contains($message, 'Google Maps');
        });
    }

    public function test_website_order_placed_dispatches_store_whatsapp_for_takeaway(): void
    {
        Bus::fake([SendStoreNewDeliveryOrderWhatsApp::class]);

        $order = $this->createTakeawayOrder();
        WebsiteOrderPlaced::dispatch($order->getKey());

        Bus::assertDispatched(SendStoreNewDeliveryOrderWhatsApp::class);
    }

    public function test_whatsapp_skipped_when_mirsalli_not_configured(): void
    {
        Config::set('services.mirsalli.user', '');

        Http::fake();

        $order = $this->createDeliveryOrder();

        $this->assertFalse(app(DeliveryOrderWhatsAppService::class)->notifyStoreNewOrder($order));

        Http::assertNothingSent();
    }

    protected function createDeliveryOrder(): Order
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963911122233']);
        PosSetting::current()->update(['delivery_fee' => 5]);

        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-wa']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'موكا',
            'slug' => 'mocha-wa',
            'price' => 35,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Delivery,
            'total' => 40,
            'delivery_fee' => 5,
            'customer_name' => 'ليلى',
            'customer_phone' => '963912345678',
            'delivery_latitude' => 33.5138,
            'delivery_longitude' => 36.2765,
            'delivery_location_note' => 'شارع الرئيسي',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 35,
            'unit_cost' => 0,
            'line_total' => 35,
        ]);

        return $order->fresh(['items.addons']);
    }

    protected function createTakeawayOrder(): Order
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963911122233']);

        $category = Category::query()->create(['name' => 'سفري', 'slug' => 'takeaway-wa']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'ساندويش',
            'slug' => 'sandwich-wa',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $order = Order::query()->create([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => PosServiceType::Takeaway,
            'total' => 20,
            'customer_name' => 'أحمد',
            'customer_phone' => '963988776655',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 20,
            'unit_cost' => 0,
            'line_total' => 20,
        ]);

        return $order->fresh(['items.addons']);
    }
}
