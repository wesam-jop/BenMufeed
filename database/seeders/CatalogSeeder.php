<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\TaxRate;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $taxRate = TaxRate::query()->where('rate', 15)->first();
        $piece = $this->unit('pcs');
        $kg = $this->unit('kg');
        $liter = $this->unit('L');

        if ($branch === null) {
            return;
        }

        $categories = [
            ['name' => 'قهوة ساخنة', 'slug' => 'hot-coffee', 'sort' => 1],
            ['name' => 'مشروبات باردة', 'slug' => 'cold-drinks', 'sort' => 2],
            ['name' => 'حلويات', 'slug' => 'desserts', 'sort' => 3],
            ['name' => 'وجبات خفيفة', 'slug' => 'snacks', 'sort' => 4],
        ];

        $categoryIds = [];

        foreach ($categories as $category) {
            $model = ProductCategory::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort'],
                    'is_active' => true,
                ],
            );

            $categoryIds[$category['slug']] = $model->id;
        }

        $rawProducts = [
            ['sku' => 'RAW-COFFEE', 'name' => 'حبوب قهوة', 'unit' => $kg, 'cost' => 85, 'sale' => 0],
            ['sku' => 'RAW-MILK', 'name' => 'حليب', 'unit' => $liter, 'cost' => 6, 'sale' => 0],
            ['sku' => 'RAW-SUGAR', 'name' => 'سكر', 'unit' => $kg, 'cost' => 4, 'sale' => 0],
            ['sku' => 'RAW-CREAM', 'name' => 'كريمة', 'unit' => $liter, 'cost' => 18, 'sale' => 0],
            ['sku' => 'RAW-CHOCOLATE', 'name' => 'شوكولاتة', 'unit' => $kg, 'cost' => 45, 'sale' => 0],
            ['sku' => 'RAW-FLOUR', 'name' => 'دقيق', 'unit' => $kg, 'cost' => 3.5, 'sale' => 0],
        ];

        $rawIds = [];

        foreach ($rawProducts as $raw) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $raw['sku']],
                [
                    'branch_id' => $branch->id,
                    'category_id' => $categoryIds['snacks'],
                    'type' => ProductType::Raw,
                    'name' => $raw['name'],
                    'unit_id' => $raw['unit']?->id,
                    'sale_price' => $raw['sale'],
                    'cost_price' => $raw['cost'],
                    'track_stock' => true,
                    'is_active' => true,
                ],
            );

            $rawIds[$raw['sku']] = $product->id;
        }

        $menuProducts = [
            ['sku' => 'MENU-ESPRESSO', 'name' => 'إسpresso', 'slug' => 'hot-coffee', 'price' => 12, 'cost' => 3, 'desc' => 'قهوة مركزة قوية'],
            ['sku' => 'MENU-CAPPUCCINO', 'name' => 'كابتشينو', 'slug' => 'hot-coffee', 'price' => 16, 'cost' => 5, 'desc' => 'إسpresso مع حليب رغوي'],
            ['sku' => 'MENU-LATTE', 'name' => 'لاتيه', 'slug' => 'hot-coffee', 'price' => 18, 'cost' => 6, 'desc' => 'قهوة مع حليب ساخن'],
            ['sku' => 'MENU-ICED-LATTE', 'name' => 'آيس لاتيه', 'slug' => 'cold-drinks', 'price' => 20, 'cost' => 7, 'desc' => 'لاتيه بارد منعش'],
            ['sku' => 'MENU-MOCHA', 'name' => 'موكا', 'slug' => 'hot-coffee', 'price' => 19, 'cost' => 7, 'desc' => 'قهوة مع شوكولاتة'],
            ['sku' => 'MENU-CHEESECAKE', 'name' => 'تشيزكيك', 'slug' => 'desserts', 'price' => 22, 'cost' => 8, 'desc' => 'تشيزكيك كريمي'],
            ['sku' => 'MENU-CROISSANT', 'name' => 'كرواسون', 'slug' => 'snacks', 'price' => 10, 'cost' => 3.5, 'desc' => 'كرواسون طازج'],
            ['sku' => 'MENU-SANDWICH', 'name' => 'ساندwich', 'slug' => 'snacks', 'price' => 15, 'cost' => 5, 'desc' => 'ساندwich جبنة وخضار'],
        ];

        $menuIds = [];

        foreach ($menuProducts as $item) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'branch_id' => $branch->id,
                    'category_id' => $categoryIds[$item['slug']],
                    'type' => ProductType::Finished,
                    'name' => $item['name'],
                    'description' => $item['desc'],
                    'unit_id' => $piece?->id,
                    'tax_rate_id' => $taxRate?->id,
                    'sale_price' => $item['price'],
                    'cost_price' => $item['cost'],
                    'track_stock' => false,
                    'is_active' => true,
                ],
            );

            $menuIds[$item['sku']] = $product->id;
        }

        $sizeGroup = ModifierGroup::query()->firstOrCreate(
            ['name' => 'الحجم'],
            ['min_select' => 1, 'max_select' => 1, 'is_required' => true],
        );

        foreach ([['name' => 'صغير', 'price' => 0], ['name' => 'وسط', 'price' => 3], ['name' => 'كبير', 'price' => 5]] as $index => $option) {
            ModifierOption::query()->firstOrCreate(
                ['modifier_group_id' => $sizeGroup->id, 'name' => $option['name']],
                ['price_adjustment' => $option['price'], 'sort_order' => $index + 1],
            );
        }

        $milkGroup = ModifierGroup::query()->firstOrCreate(
            ['name' => 'نوع الحليب'],
            ['min_select' => 0, 'max_select' => 1, 'is_required' => false],
        );

        foreach ([['name' => 'حليب كامل', 'price' => 0], ['name' => 'حليب خالي الدسم', 'price' => 0], ['name' => 'شوفان', 'price' => 4]] as $index => $option) {
            ModifierOption::query()->firstOrCreate(
                ['modifier_group_id' => $milkGroup->id, 'name' => $option['name']],
                ['price_adjustment' => $option['price'], 'sort_order' => $index + 1],
            );
        }

        foreach (['MENU-LATTE', 'MENU-CAPPUCCINO', 'MENU-ICED-LATTE', 'MENU-MOCHA'] as $sku) {
            if (! isset($menuIds[$sku])) {
                continue;
            }

            Product::query()->find($menuIds[$sku])?->modifierGroups()->syncWithoutDetaching([
                $sizeGroup->id,
                $milkGroup->id,
            ]);
        }

        $latteId = $menuIds['MENU-LATTE'] ?? null;

        if ($latteId !== null) {
            $recipe = Recipe::query()->firstOrCreate(
                ['product_id' => $latteId, 'name' => 'وصفة لاتيه'],
                ['yield_quantity' => 1, 'version' => 1, 'is_active' => true],
            );

            $recipeItems = [
                ['sku' => 'RAW-COFFEE', 'qty' => 0.018],
                ['sku' => 'RAW-MILK', 'qty' => 0.25],
                ['sku' => 'RAW-SUGAR', 'qty' => 0.005],
            ];

            foreach ($recipeItems as $item) {
                if (! isset($rawIds[$item['sku']])) {
                    continue;
                }

                RecipeItem::query()->firstOrCreate(
                    [
                        'recipe_id' => $recipe->id,
                        'ingredient_product_id' => $rawIds[$item['sku']],
                    ],
                    [
                        'quantity' => $item['qty'],
                        'unit_id' => $rawIds[$item['sku']] === ($rawIds['RAW-COFFEE'] ?? null) ? $kg?->id : ($item['sku'] === 'RAW-SUGAR' ? $kg?->id : $liter?->id),
                        'waste_percent' => 2,
                    ],
                );
            }
        }

        Product::query()
            ->whereNull('sku')
            ->each(fn (Product $product) => $product->update(['sku' => 'AUTO-'.Str::upper(Str::random(6))]));
    }
}
