<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Tests\TestCase;

class StorefrontSocialMetaTest extends TestCase
{
    public function test_home_page_includes_open_graph_tags(): void
    {
        SiteSetting::current()->update([
            'site_name' => 'متجر تجريبي',
            'seo_title' => 'عنوان المتجر',
            'seo_description' => 'وصف المتجر للمشاركة.',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('عنوان المتجر', false);
    }

    public function test_product_page_uses_product_title_and_description_in_meta(): void
    {
        SiteSetting::current()->update(['site_name' => 'متجر تجريبي']);

        $category = Category::query()->create([
            'name' => 'عدد يدوية',
            'slug' => 'hand-tools',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب كهربائي 750 واط',
            'slug' => 'drill-750w',
            'short_description' => 'مثقاب قوي للاستخدام اليومي.',
            'description' => '<p>وصف تفصيلي</p>',
            'price' => 199.99,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('مثقاب كهربائي 750 واط', false);
        $response->assertSee('مثقاب قوي للاستخدام اليومي.', false);
    }

    public function test_category_page_includes_category_name_in_meta(): void
    {
        SiteSetting::current()->update(['site_name' => 'متجر تجريبي']);

        $category = Category::query()->create([
            'name' => 'معدات كهربائية',
            'slug' => 'power-tools',
            'description' => 'دريلات وصواريخ ومناشير.',
        ]);

        $response = $this->get('/categories/'.$category->slug);

        $response->assertOk();
        $response->assertSee('معدات كهربائية', false);
        $response->assertSee('دريلات وصواريخ ومناشير.', false);
        $response->assertSee('property="og:title"', false);
    }
}
