<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\ProductMetadataGenerator;
use App\Support\SeoText;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductMetadataGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_unique_slug_from_product_name(): void
    {
        $category = Category::query()->create(['name' => 'عدد', 'slug' => 'tools']);

        $first = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب احترافي',
            'price' => 100,
        ]);

        $second = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب احترافي',
            'price' => 120,
        ]);

        $this->assertNotSame('', $second->slug);
        $this->assertNotEquals($first->slug, $second->slug);
    }

    #[Test]
    public function it_syncs_seo_fields_from_name_and_short_description(): void
    {
        SiteSetting::current()->update([
            'site_name' => 'متجر العدد',
            'seo_keywords' => 'عدد, معدات',
        ]);

        $category = Category::query()->create(['name' => 'معدات كهربائية', 'slug' => 'power']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'مثقاب 750 واط',
            'short_description' => 'مثقاب قوي للورش الصناعية.',
            'price' => 299,
        ]);

        $this->assertStringContainsString('مثقاب 750 واط', $product->seo_title);
        $this->assertStringContainsString('مثقاب قوي', $product->seo_description);
        $this->assertStringContainsString('مثقاب 750 واط', (string) $product->seo_focus_keywords);
    }

    #[Test]
    public function it_uses_plain_text_from_html_description_for_seo(): void
    {
        SiteSetting::current();

        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'description' => '<p><strong>وصف غني</strong> مع <em>تنسيق</em></p>',
            'price' => 50,
        ]);

        $this->assertSame('وصف غني مع تنسيق', SeoText::plainText($product->description));
        $this->assertStringContainsString('وصف غني', $product->seo_description);
    }

    #[Test]
    public function slug_stays_stable_when_product_name_changes(): void
    {
        $category = Category::query()->create(['name' => 'عام', 'slug' => 'general']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'اسم أول',
            'price' => 10,
        ]);

        $originalSlug = $product->slug;

        $product->update(['name' => 'اسم جديد تماماً']);

        $this->assertSame($originalSlug, $product->fresh()->slug);
        $this->assertStringContainsString('اسم جديد', $product->fresh()->seo_title);
    }
}
