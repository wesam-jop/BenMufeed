<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Services\TableScanSessionService;
use Tests\Concerns\VerifiesStorefrontCustomer;
use Tests\TestCase;

class GuestOrderTest extends TestCase
{
    use VerifiesStorefrontCustomer;

    public function test_guest_can_place_takeaway_order_and_see_thank_you_page(): void
    {
        SiteSetting::current();
        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب',
            'slug' => 'drill-guest',
            'price' => 100,
            'status' => ProductStatus::Published,
        ]);

        $response = $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'customer_name' => 'أحمد علي',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
                'customer_note' => 'بدون بصل',
            ]);

        $response->assertRedirect(route('orders.thank-you'));
        $response->assertSessionHas('checkout.order_id');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('200.00', (string) $order->total);
        $this->assertSame('أحمد علي', $order->customer_name);
        $this->assertSame('963912345678', $order->customer_phone);
        $this->assertSame('takeaway', $order->service_type->value);

        $thankYou = $this->get('/orders/thank-you');
        $thankYou->assertOk();
        $thankYou->assertInertia(fn ($page) => $page
            ->component('Store/OrderThankYou')
            ->where('checkout.order_id', $order->id)
        );
    }

    public function test_guest_can_place_table_order_with_qr_token(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general-table']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'قهوة',
            'slug' => 'coffee-table',
            'price' => 25,
            'status' => ProductStatus::Published,
        ]);

        $table = RestaurantTable::query()->create([
            'number' => 7,
            'qr_token' => 'test-table-token-123456789012345678901234567890',
            'label' => 'طاولة 7',
            'is_active' => true,
        ]);

        $scan = app(TableScanSessionService::class)->issueFresh($table);

        $response = $this->post('/orders', [
            'service_type' => 'table',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'table_token' => $table->qr_token,
            'table_scan_session' => $scan->session_token,
        ]);

        $response->assertRedirect(route('orders.thank-you'));

        $order = Order::query()->first();
        $this->assertSame($table->id, $order->restaurant_table_id);
        $this->assertSame(7, $order->table_number);
        $this->assertSame('table', $order->service_type->value);
        $this->assertSame(\App\Enums\OrderStatus::Open, $order->status);
        $this->assertNotNull($order->customer_session_token);
    }

    public function test_same_customer_can_append_to_open_table(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general-append']);

        $productA = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'ماء',
            'slug' => 'water-append',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        $productB = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'عصير',
            'slug' => 'juice-append',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $table = RestaurantTable::query()->create([
            'number' => 9,
            'qr_token' => 'append-table-token-123456789012345678901234567',
            'label' => 'طاولة 9',
            'is_active' => true,
        ]);

        $scan = app(TableScanSessionService::class)->issueFresh($table);

        $this->post('/orders', [
            'service_type' => 'table',
            'items' => [['product_id' => $productA->id, 'quantity' => 1]],
            'table_token' => $table->qr_token,
            'table_scan_session' => $scan->session_token,
        ])->assertRedirect(route('orders.thank-you'));

        $order = Order::query()->firstOrFail();
        $session = (string) $order->customer_session_token;
        $scan->refresh();

        $response = $this->from('/cart')->post('/orders', [
            'service_type' => 'table',
            'items' => [['product_id' => $productB->id, 'quantity' => 2]],
            'table_token' => $table->qr_token,
            'table_session_token' => $session,
            'table_scan_session' => $scan->session_token,
        ]);

        $response->assertRedirect(route('orders.thank-you'));
        $this->assertDatabaseCount('orders', 1);
        $order->refresh();
        $this->assertSame('50.00', (string) $order->total);
        $this->assertSame(2, $order->items()->count());
    }

    public function test_guest_cannot_order_on_occupied_table(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general-occupied']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاي',
            'slug' => 'tea-occupied',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        $table = RestaurantTable::query()->create([
            'number' => 3,
            'qr_token' => 'occupied-table-token-1234567890123456789012345678',
            'label' => 'طاولة 3',
            'is_active' => true,
        ]);

        $scanA = app(TableScanSessionService::class)->issueFresh($table);

        $this->post('/orders', [
            'service_type' => 'table',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'table_token' => $table->qr_token,
            'table_scan_session' => $scanA->session_token,
        ])->assertRedirect(route('orders.thank-you'));

        $scanB = app(TableScanSessionService::class)->issueFresh($table);

        $response = $this->from('/cart')->post('/orders', [
            'service_type' => 'table',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'table_token' => $table->qr_token,
            'table_scan_session' => $scanB->session_token,
        ]);

        $response->assertRedirect('/cart');
        $response->assertSessionHasErrors('table_token');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_table_resolve_shows_occupied_status(): void
    {
        SiteSetting::current();

        $table = RestaurantTable::query()->create([
            'number' => 5,
            'qr_token' => 'resolve-occupied-token-123456789012345678901234',
            'is_active' => true,
        ]);

        Order::query()->create([
            'status' => \App\Enums\OrderStatus::Open,
            'source' => \App\Enums\OrderSource::Website,
            'service_type' => \App\Enums\PosServiceType::Table,
            'table_number' => 5,
            'restaurant_table_id' => $table->id,
            'customer_session_token' => 'owner-session-token-123456789012345678901234567',
            'total' => 50,
            'customer_name' => $table->displayName(),
        ]);

        $fresh = $this->getJson('/api/tables/resolve/'.$table->qr_token.'?fresh_scan=1')
            ->assertOk()
            ->assertJson([
                'is_occupied' => true,
                'can_order' => false,
                'is_owner' => false,
            ]);

        $scanSession = $fresh->json('scan_session');

        $this->getJson('/api/tables/resolve/'.$table->qr_token.'?scan_session='.$scanSession.'&session=owner-session-token-123456789012345678901234567')
            ->assertOk()
            ->assertJson([
                'is_occupied' => true,
                'can_order' => true,
                'is_owner' => true,
            ]);
    }

    public function test_takeaway_order_accepts_structured_phone_fields(): void
    {
        SiteSetting::current();
        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('سارة', '988776655');

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general-phone']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'ساندwich',
            'slug' => 'sandwich-phone',
            'price' => 50,
            'status' => ProductStatus::Published,
        ]);

        $response = $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'customer_name' => 'سارة',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '0988776655',
            ]);

        $response->assertRedirect(route('orders.thank-you'));

        $order = Order::query()->firstOrFail();
        $this->assertSame('963988776655', $order->customer_phone);
    }

    public function test_draft_product_is_rejected(): void
    {
        SiteSetting::current();
        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('زائر', '912345678');

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مسودة',
            'slug' => 'draft-only',
            'price' => 50,
            'status' => ProductStatus::Draft,
        ]);

        $response = $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->from('/cart')
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'customer_name' => 'زائر',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
            ]);

        $response->assertRedirect('/cart');
        $response->assertSessionHasErrors('items.0.product_id');
        $this->assertDatabaseCount('orders', 0);
    }
}
