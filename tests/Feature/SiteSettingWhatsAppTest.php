<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Repositories\SiteSettingRepository;
use App\Services\WhatsAppCheckoutService;
use Tests\Concerns\VerifiesStorefrontCustomer;
use Tests\TestCase;

class SiteSettingWhatsAppTest extends TestCase
{
    use VerifiesStorefrontCustomer;

    public function test_checkout_url_uses_whatsapp_phone_from_site_settings(): void
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963912345678']);
        SiteSettingRepository::forgetCache();

        $url = app(WhatsAppCheckoutService::class)->checkoutUrl('مرحباً');

        $this->assertStringContainsString('https://wa.me/963912345678', $url);
    }

    public function test_storefront_shares_whatsapp_phone_from_site_settings(): void
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963998877665']);
        SiteSettingRepository::forgetCache();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('site.whatsapp_phone', '963998877665')
        );
    }

    public function test_order_whatsapp_url_reflects_updated_site_settings(): void
    {
        SiteSetting::current()->update(['whatsapp_phone' => '963911122233']);
        SiteSettingRepository::forgetCache();

        $category = Category::query()->create(['name' => 'عام']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('زائر', '911122233');

        $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'customer_name' => 'زائر',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '911122233',
            ])->assertRedirect(route('orders.thank-you'));

        $whatsappUrl = app(WhatsAppCheckoutService::class)->checkoutUrl('طلب تجريبي');
        $this->assertStringContainsString('963911122233', $whatsappUrl);
    }
}
