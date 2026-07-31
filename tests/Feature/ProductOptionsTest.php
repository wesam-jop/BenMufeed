<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Services\ProductOptionsService;
use Tests\Concerns\VerifiesStorefrontCustomer;
use Tests\TestCase;

class ProductOptionsTest extends TestCase
{
    use VerifiesStorefrontCustomer;

    public function test_waffle_variant_and_addon_pricing(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'حلويات', 'slug' => 'desserts']);

        $waffle = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'وافل',
            'slug' => 'waffle-options',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $banana = ProductVariant::query()->create([
            'product_id' => $waffle->id,
            'name' => 'موز',
            'price' => 22,
            'cost' => 8,
            'is_default' => true,
            'is_active' => true,
        ]);

        ProductVariant::query()->create([
            'product_id' => $waffle->id,
            'name' => 'شوكلا',
            'price' => 25,
            'cost' => 10,
            'is_active' => true,
        ]);

        $nuts = ProductAddon::query()->create([
            'product_id' => $waffle->id,
            'name' => 'مكسرات زيادة',
            'price' => 5,
            'cost' => 2,
            'max_quantity' => 2,
            'is_active' => true,
        ]);

        $resolved = app(ProductOptionsService::class)->resolveLine(
            $waffle->fresh(['variants', 'addons']),
            $banana->id,
            [['product_addon_id' => $nuts->id, 'quantity' => 1]],
            2,
        );

        $this->assertSame('27.00', number_format($resolved['unit_price'], 2));
        $this->assertSame('54.00', number_format($resolved['line_total'], 2));
        $this->assertSame('موز + مكسرات زيادة', $resolved['options_label']);
    }

    public function test_website_order_with_variant_and_addon(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'حلويات', 'slug' => 'desserts-order']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'وافل',
            'slug' => 'waffle-order',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'فواكه',
            'price' => 24,
            'cost' => 9,
            'is_default' => true,
            'is_active' => true,
        ]);

        $addon = ProductAddon::query()->create([
            'product_id' => $product->id,
            'name' => 'مكسرات',
            'price' => 4,
            'cost' => 1.5,
            'is_active' => true,
        ]);

        [$cookieName, $cookieValue] = $this->verifiedCustomerCookie('زبون', '912345678');

        $response = $this
            ->withUnencryptedCookie($cookieName, $cookieValue)
            ->post('/orders', [
                'service_type' => 'takeaway',
                'items' => [[
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                    'addons' => [['product_addon_id' => $addon->id, 'quantity' => 1]],
                ]],
                'customer_name' => 'زبون',
                'customer_phone_country' => '963|SY',
                'customer_phone_local' => '912345678',
                'delivery_latitude' => 33.5,
                'delivery_longitude' => 36.2,
                'delivery_location_note' => 'قريب',
            ]);

        $response->assertRedirect(route('orders.thank-you'));

        $this->assertDatabaseHas('orders', ['total' => '28.00']);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_name' => 'فواكه',
            'unit_price' => '28.00',
        ]);
        $this->assertDatabaseHas('order_item_addons', [
            'addon_name' => 'مكسرات',
            'line_total' => '4.00',
        ]);
    }
}
