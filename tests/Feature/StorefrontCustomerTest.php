<?php

namespace Tests\Feature;

use App\Enums\StorefrontCustomerAccountType;
use App\Enums\StorefrontCustomerCreatedVia;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontCustomerOtp;
use App\Enums\ProductStatus;
use App\Services\TableScanSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mirsalli.user', 'test-user');
        Http::fake();
        SiteSetting::current();
    }

    public function test_session_cookie_resolves_customer(): void
    {
        $customer = StorefrontCustomer::query()->create([
            'name' => 'اختبار',
            'account_type' => StorefrontCustomerAccountType::TableGuest,
            'created_via' => StorefrontCustomerCreatedVia::TableQr,
            'is_active' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($customer);

        $this
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->get('/api/account/me')
            ->assertOk()
            ->assertJsonPath('customer.uuid', $customer->uuid);
    }

    public function test_table_resolve_bootstraps_table_guest_account_on_first_scan(): void
    {
        $table = RestaurantTable::query()->create([
            'number' => 5,
            'qr_token' => 'tabletokenbootstrap123456789012345678901234',
            'label' => 'طاولة 5',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/tables/resolve/'.$table->qr_token.'?table_scan=fresh');

        $response->assertOk();
        $response->assertJsonPath('storefront_customer.account_type', 'table_guest');
        $response->assertJsonPath('storefront_customer.is_verified', false);
        $response->assertCookie(config('store.storefront_customer_cookie'));

        $this->assertDatabaseHas('storefront_customers', [
            'account_type' => StorefrontCustomerAccountType::TableGuest->value,
            'default_restaurant_table_id' => $table->getKey(),
        ]);
        $this->assertSame(1, StorefrontCustomer::query()->count());
    }

    public function test_table_resolve_reuses_existing_customer_on_fresh_qr_scan(): void
    {
        $table = RestaurantTable::query()->create([
            'number' => 7,
            'qr_token' => 'tabletokenreuse123456789012345678901234567',
            'label' => 'طاولة 7',
            'is_active' => true,
        ]);

        $customer = StorefrontCustomer::query()->create([
            'name' => 'محمد أحمد',
            'phone' => '963912345678',
            'phone_verified_at' => now(),
            'account_type' => StorefrontCustomerAccountType::Verified,
            'created_via' => StorefrontCustomerCreatedVia::DeliveryOtp,
            'is_active' => true,
        ]);

        $sessionToken = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($customer);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $sessionToken)
            ->getJson('/api/tables/resolve/'.$table->qr_token.'?table_scan=fresh');

        $response->assertOk();
        $response->assertJsonPath('storefront_customer.uuid', $customer->uuid);
        $response->assertJsonPath('storefront_customer.is_verified', true);
        $response->assertJsonPath('storefront_customer.name', 'محمد أحمد');
        $response->assertCookie(config('store.storefront_customer_cookie'));

        $customer->refresh();
        $this->assertSame($table->getKey(), $customer->default_restaurant_table_id);
        $this->assertSame(1, StorefrontCustomer::query()->count());
    }

    public function test_table_guest_can_upgrade_to_verified_on_otp(): void
    {
        $guest = StorefrontCustomer::query()->create([
            'name' => 'زبون — طاولة 3',
            'phone' => null,
            'account_type' => StorefrontCustomerAccountType::TableGuest,
            'created_via' => StorefrontCustomerCreatedVia::TableQr,
            'default_restaurant_table_id' => null,
            'is_active' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($guest);

        $this->seedOtp('963912345678', '654321');

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->postJson('/api/account/otp/verify', [
                'customer_name' => 'محمد أحمد',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
                'otp' => '654321',
                'service_type' => 'takeaway',
            ]);

        $response->assertOk();
        $response->assertJsonPath('customer.is_verified', true);
        $response->assertJsonPath('customer.name', 'محمد أحمد');

        $guest->refresh();
        $this->assertSame('verified', $guest->account_type->value);
        $this->assertSame('963912345678', $guest->phone);
        $this->assertNotNull($guest->phone_verified_at);
    }

    public function test_verified_customer_can_add_delivery_address_from_account(): void
    {
        $customer = StorefrontCustomer::query()->create([
            'name' => 'ليلى',
            'phone' => '963922222222',
            'phone_verified_at' => now(),
            'account_type' => StorefrontCustomerAccountType::Verified,
            'created_via' => StorefrontCustomerCreatedVia::DeliveryOtp,
            'is_active' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($customer);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->postJson('/api/account/addresses', [
                'label' => 'البيت',
                'location_note' => 'شارع الحمرا، الطابق الثالث',
                'latitude' => 33.5138,
                'longitude' => 36.2765,
            ]);

        $response->assertOk();
        $response->assertJsonPath('address.label', 'البيت');
        $response->assertJsonPath('addresses.0.label', 'البيت');

        $this->assertDatabaseHas('storefront_customer_addresses', [
            'storefront_customer_id' => $customer->getKey(),
            'label' => 'البيت',
            'location_note' => 'شارع الحمرا، الطابق الثالث',
            'is_default' => true,
        ]);
    }

    public function test_verified_customer_can_checkout_with_saved_address_only(): void
    {
        $category = Category::query()->create(['name' => 'عام', 'slug' => 'saved-checkout']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'فطيرة',
            'slug' => 'pie-saved-checkout',
            'price' => 30,
            'status' => ProductStatus::Published,
        ]);

        $customer = StorefrontCustomer::query()->create([
            'name' => 'نور',
            'phone' => '963933333333',
            'phone_verified_at' => now(),
            'account_type' => StorefrontCustomerAccountType::Verified,
            'created_via' => StorefrontCustomerCreatedVia::DeliveryOtp,
            'is_active' => true,
        ]);

        $address = $customer->addresses()->create([
            'label' => 'البيت',
            'latitude' => 33.5138,
            'longitude' => 36.2765,
            'location_note' => 'قرب الساحة',
            'is_default' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($customer);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->post('/orders', [
                'service_type' => 'delivery',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'customer_name' => 'نور',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '933333333',
                'saved_address_id' => $address->getKey(),
            ]);

        $response->assertRedirect(route('orders.thank-you'));

        $this->assertDatabaseHas('orders', [
            'storefront_customer_id' => $customer->getKey(),
            'saved_address_id' => $address->getKey(),
            'delivery_location_note' => 'قرب الساحة',
        ]);
    }

    public function test_verified_customer_can_save_delivery_address_on_order(): void
    {
        $category = Category::query()->create(['name' => 'عام', 'slug' => 'delivery-address']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاورما',
            'slug' => 'shawarma-address',
            'price' => 50,
            'status' => ProductStatus::Published,
        ]);

        $customer = StorefrontCustomer::query()->create([
            'name' => 'سارة',
            'phone' => '963911111111',
            'phone_verified_at' => now(),
            'account_type' => StorefrontCustomerAccountType::Verified,
            'created_via' => StorefrontCustomerCreatedVia::DeliveryOtp,
            'is_active' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($customer);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->post('/orders', [
                'service_type' => 'delivery',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'customer_name' => 'سارة',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '911111111',
                'delivery_latitude' => 33.5138,
                'delivery_longitude' => 36.2765,
                'delivery_location_note' => 'بناء أبيض، الطابق الثاني',
                'save_address' => true,
                'address_label' => 'البيت',
            ]);

        $response->assertRedirect(route('orders.thank-you'));

        $this->assertDatabaseHas('storefront_customer_addresses', [
            'storefront_customer_id' => $customer->getKey(),
            'label' => 'البيت',
            'location_note' => 'بناء أبيض، الطابق الثاني',
        ]);
    }

    public function test_table_order_links_storefront_customer(): void
    {
        $category = Category::query()->create(['name' => 'عام', 'slug' => 'table-link']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'قهوة',
            'slug' => 'coffee-link',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $table = RestaurantTable::query()->create([
            'number' => 9,
            'qr_token' => 'table-token-link-1234567890123456789012345',
            'label' => 'طاولة 9',
            'is_active' => true,
        ]);

        $guest = StorefrontCustomer::query()->create([
            'name' => 'زبون — طاولة 9',
            'account_type' => StorefrontCustomerAccountType::TableGuest,
            'created_via' => StorefrontCustomerCreatedVia::TableQr,
            'default_restaurant_table_id' => $table->getKey(),
            'is_active' => true,
        ]);

        $token = app(\App\Services\StorefrontCustomerAuthService::class)->issueSession($guest);
        $scan = app(TableScanSessionService::class)->issueFresh($table);

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie(config('store.storefront_customer_cookie'), $token)
            ->post('/orders', [
                'service_type' => 'table',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'table_token' => $table->qr_token,
                'table_scan_session' => $scan->session_token,
            ]);

        $response->assertRedirect(route('orders.thank-you'));

        $this->assertDatabaseHas('orders', [
            'storefront_customer_id' => $guest->getKey(),
            'table_number' => 9,
        ]);
    }

    protected function seedOtp(string $phone, string $code): void
    {
        StorefrontCustomerOtp::query()->create([
            'phone' => $phone,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'sent_at' => now()->subMinute(),
        ]);
    }
}
