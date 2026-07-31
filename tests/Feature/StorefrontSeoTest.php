<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Tests\TestCase;

class StorefrontSeoTest extends TestCase
{
    public function test_robots_txt_lists_sitemap_and_disallows_cart(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Sitemap:');
        $response->assertSee('sitemap.xml');
        $response->assertSee('Disallow: /cart');
    }

    public function test_sitemap_includes_lastmod(): void
    {
        SiteSetting::current();

        $category = Category::query()->create([
            'name' => 'عدد',
            'slug' => 'tools',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('<lastmod>', false);
        $response->assertSee('/categories/tools', false);
    }

    public function test_product_page_has_json_ld_and_product_og_type(): void
    {
        SiteSetting::current()->update(['site_name' => 'متجر تجريبي']);

        $category = Category::query()->create([
            'name' => 'معدات',
            'slug' => 'gear',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب احترافي',
            'slug' => 'pro-drill',
            'short_description' => 'مثقاب للورش.',
            'price' => 299,
            'status' => ProductStatus::Published,
            'seo_focus_keywords' => 'مثقاب, دريل',
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $response->assertSee('property="og:type" content="product"', false);
        $response->assertInertia(fn ($page) => $page
            ->has('structuredData')
            ->where('structuredData', fn ($blocks) => collect($blocks)->contains(
                fn ($block) => ($block['@type'] ?? '') === 'Product'
            ))
        );
    }

    public function test_cart_page_is_noindex(): void
    {
        SiteSetting::current();

        $response = $this->get('/cart');

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex,follow"', false);
    }

    public function test_search_results_are_noindex(): void
    {
        SiteSetting::current();

        $response = $this->get('/products?q=مثقاب');

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex,follow"', false);
    }
}
