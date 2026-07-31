<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\SiteSetting;
use App\Support\CategoryMetadataGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryMetadataGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_unique_slug_from_category_name(): void
    {
        $first = Category::query()->create(['name' => 'معدات كهربائية']);

        $second = Category::query()->create(['name' => 'معدات كهربائية']);

        $this->assertNotSame('', $second->slug);
        $this->assertNotEquals($first->slug, $second->slug);
    }

    #[Test]
    public function it_syncs_seo_fields_from_name_and_description(): void
    {
        SiteSetting::current()->update([
            'site_name' => 'متجر العدد',
            'seo_keywords' => 'عدد, معدات',
        ]);

        $category = Category::query()->create([
            'name' => 'معدات كهربائية',
            'description' => 'أدوات ومعدات للورش والبناء.',
        ]);

        $this->assertStringContainsString('معدات كهربائية', $category->seo_title);
        $this->assertStringContainsString('أدوات ومعدات', $category->seo_description);
        $this->assertStringContainsString('معدات كهربائية', (string) $category->seo_focus_keywords);
    }

    #[Test]
    public function slug_stays_stable_when_category_name_changes(): void
    {
        $category = Category::query()->create(['name' => 'اسم أول']);

        $originalSlug = $category->slug;

        $category->update(['name' => 'اسم جديد تماماً']);

        $this->assertSame($originalSlug, $category->fresh()->slug);
        $this->assertStringContainsString('اسم جديد', $category->fresh()->seo_title);
    }
}
