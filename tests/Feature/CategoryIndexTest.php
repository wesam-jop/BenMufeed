<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    public function test_categories_index_paginates_fifteen_per_page(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'قسم تجريبي', 'slug' => 'demo-cat']);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج',
            'slug' => 'demo-product',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        Category::query()->insert(
            collect(range(1, 16))->map(fn (int $i) => [
                'name' => "قسم {$i}",
                'slug' => "cat-{$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        $response = $this->get('/categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/CategoryIndex')
            ->has('categories', 15)
            ->where('listMeta.total', 17)
        );
    }

    public function test_categories_index_page_two_loads_from_server(): void
    {
        SiteSetting::current();

        Category::query()->insert(
            collect(range(1, 16))->map(fn (int $i) => [
                'name' => "قسم {$i}",
                'slug' => "section-{$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );

        $response = $this->get('/categories?page=2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('categories', 1)
            ->where('pagination.current_page', 2)
        );
    }
}
