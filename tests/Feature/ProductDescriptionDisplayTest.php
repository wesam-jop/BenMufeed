<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\StorefrontPresenter;
use Tests\TestCase;

class ProductDescriptionDisplayTest extends TestCase
{
    public function test_product_detail_sanitizes_and_includes_rich_description(): void
    {
        SiteSetting::current();

        $category = Category::query()->create([
            'name' => 'معدات',
            'slug' => 'gear',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب احترافي',
            'slug' => 'pro-drill-rich',
            'short_description' => 'وصف قصير.',
            'description' => '<h2>المواصفات</h2><ul><li><strong>750 واط</strong></li><li>خفيف الوزن</li></ul><script>alert(1)</script>',
            'price' => 299,
            'status' => ProductStatus::Published,
        ]);

        $detail = StorefrontPresenter::productDetail($product);

        $this->assertStringContainsString('<h2>المواصفات</h2>', (string) $detail['description']);
        $this->assertStringContainsString('<strong>750 واط</strong>', (string) $detail['description']);
        $this->assertStringNotContainsString('<script>', (string) $detail['description']);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/ProductShow')
            ->where('product.description', function (?string $html): bool {
                return is_string($html)
                    && str_contains($html, '<h2>المواصفات</h2>')
                    && str_contains($html, '<strong>750 واط</strong>')
                    && ! str_contains($html, '<script>');
            })
        );
    }
}
