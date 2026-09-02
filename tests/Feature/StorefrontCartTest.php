<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '-100123',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    protected function addProductToCart(): Product
    {
        $this->seed();

        $product = Product::query()->where('sku', 'MENU-LATTE')->first();
        $this->assertNotNull($product);

        $this->post(route('storefront.cart.items.store'), [
            'product_id' => $product->id,
        ])->assertRedirect();

        return $product;
    }

    protected function saveCartLocation(): void
    {
        $this->patch(route('storefront.cart.location'), [
            'location_lat' => 24.7136,
            'location_lng' => 46.6753,
            'location_address' => 'الرياض',
        ])->assertRedirect(route('storefront.cart', ['step' => 3]));
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $this->addProductToCart();

        $this->assertEquals(1, session('storefront_cart.items.0.quantity'));
    }

    public function test_cart_step_three_requires_location(): void
    {
        $this->addProductToCart();

        $this->get(route('storefront.cart', ['step' => 3]))
            ->assertRedirect(route('storefront.cart', ['step' => 2]));
    }

    public function test_checkout_redirects_to_cart_step_three(): void
    {
        $this->addProductToCart();
        $this->saveCartLocation();

        $this->get(route('storefront.checkout'))
            ->assertRedirect(route('storefront.cart', ['step' => 3]));
    }

    public function test_cart_step_three_loads_with_location(): void
    {
        $this->addProductToCart();
        $this->saveCartLocation();

        $this->get(route('storefront.cart', ['step' => 3]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/cart/index')
                ->where('step', 3)
            );
    }

    public function test_customer_logged_in_after_successful_order(): void
    {
        $this->addProductToCart();
        $this->saveCartLocation();

        $otpResponse = $this->post(route('storefront.checkout.otp'), [
            'name' => 'أحمد',
            'whatsapp' => '0509999999',
        ]);

        $otpResponse->assertRedirect(route('storefront.checkout.verify'));

        $verificationId = session('checkout.verification_id');
        $this->assertNotNull($verificationId);

        $debugCode = session('checkout.debug_code');
        $this->assertNotNull($debugCode);

        $confirmResponse = $this->post(route('storefront.checkout.confirm'), [
            'verification_id' => $verificationId,
            'whatsapp' => '0509999999',
            'otp' => $debugCode,
        ]);

        $confirmResponse->assertRedirect();
        $this->assertAuthenticated('customer');

        $customer = \App\Models\Customer::query()->where('phone', '0509999999')->first();
        $this->assertNotNull($customer);
        $this->assertSame('أحمد', $customer->name);
        $this->assertFalse($customer->hasPassword());
        $this->assertNull(session('storefront_guest_profile'));
    }
}
