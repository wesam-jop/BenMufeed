<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Tests\TestCase;

class ProductCatalogSearchTest extends TestCase
{
    public function test_products_search_api_filters_by_query_and_category(): void
    {
        SiteSetting::current();

        $tools = Category::query()->create(['name' => 'عدد يدوية', 'slug' => 'hand-tools']);
        $power = Category::query()->create(['name' => 'معدات كهربائية', 'slug' => 'power-tools']);

        Product::query()->create([
            'category_id' => $tools->id,
            'name' => 'مطرقة ثقيلة',
            'slug' => 'hammer-heavy',
            'price' => 45,
            'status' => ProductStatus::Published,
        ]);

        Product::query()->create([
            'category_id' => $power->id,
            'name' => 'مثقاب كهربائي',
            'slug' => 'electric-drill',
            'price' => 199,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->getJson('/products/search?q=مثقاب&category=power-tools');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('products.0.slug', 'electric-drill');
    }

    public function test_products_search_supports_oldest_sort(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        $older = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج قديم',
            'slug' => 'old-product',
            'price' => 10,
            'status' => ProductStatus::Published,
            'created_at' => now()->subDays(10),
        ]);

        $newer = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج جديد',
            'slug' => 'new-product',
            'price' => 20,
            'status' => ProductStatus::Published,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/products/search?sort=oldest');

        $response->assertOk();
        $response->assertJsonPath('products.0.slug', $older->slug);
        $response->assertJsonPath('products.1.slug', $newer->slug);
    }

    public function test_products_page_paginates_fifteen_per_page(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        foreach (range(1, 16) as $i) {
            Product::query()->create([
                'category_id' => $category->id,
                'name' => "منتج {$i}",
                'slug' => "product-{$i}",
                'price' => $i,
                'status' => ProductStatus::Published,
            ]);
        }

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/ProductIndex')
            ->has('products', 15)
            ->where('catalogMeta.total', 16)
            ->has('categories')
        );
    }
}
