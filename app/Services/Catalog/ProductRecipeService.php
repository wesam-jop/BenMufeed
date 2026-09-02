<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
class ProductRecipeService
{
    /**
     * @param  array<int, array{ingredient_product_id: int, quantity: float|int|string, waste_percent?: float|int|string|null}>  $items
     */
    public function syncRecipe(Product $product, array $items): float
    {
        Recipe::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->update(['is_active' => false]);

        if ($items === []) {
            return (float) $product->cost_price;
        }

        $recipe = Recipe::create([
            'product_id' => $product->id,
            'variant_id' => null,
            'name' => "وصفة {$product->name}",
            'yield_quantity' => 1,
            'version' => (int) Recipe::query()->where('product_id', $product->id)->max('version') + 1,
            'is_active' => true,
        ]);

        $totalCost = 0.0;

        foreach ($items as $item) {
            $ingredient = Product::query()->findOrFail($item['ingredient_product_id']);
            $quantity = (float) $item['quantity'];
            $wastePercent = (float) ($item['waste_percent'] ?? 0);
            $requiredQty = $quantity * (1 + ($wastePercent / 100));
            $lineCost = $requiredQty * (float) $ingredient->cost_price;
            $totalCost += $lineCost;

            RecipeItem::create([
                'recipe_id' => $recipe->id,
                'ingredient_product_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit_id' => $ingredient->unit_id,
                'waste_percent' => $wastePercent,
            ]);
        }

        return round($totalCost, 4);
    }

    /**
     * @return array<int, array{ingredient_product_id: int, quantity: float, waste_percent: float}>
     */
    public function recipeItemsForProduct(Product $product): array
    {
        $recipe = Recipe::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('is_active', true)
            ->with('items')
            ->first();

        if ($recipe === null) {
            return [];
        }

        return $recipe->items->map(fn (RecipeItem $item) => [
            'ingredient_product_id' => $item->ingredient_product_id,
            'quantity' => (float) $item->quantity,
            'waste_percent' => (float) $item->waste_percent,
        ])->values()->all();
    }
}
