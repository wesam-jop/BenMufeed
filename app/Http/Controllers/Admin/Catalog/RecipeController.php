<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\System\AdminController;

use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Recipe::class);

        $recipes = $this->forBranchThrough(Recipe::query(), $request, 'product')
            ->where('is_active', true)
            ->with([
                'product:id,name,category_id,cost_price,sale_price',
                'product.category:id,name',
                'items.ingredientProduct:id,name,cost_price',
                'items.unit:id,name,abbreviation',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $recipes->through(function (Recipe $recipe) {
            $items = $recipe->items->map(function (RecipeItem $item) {
                $quantity = (float) $item->quantity;
                $wastePercent = (float) $item->waste_percent;
                $requiredQty = $quantity * (1 + ($wastePercent / 100));
                $unitCost = (float) ($item->ingredientProduct?->cost_price ?? 0);

                return [
                    'ingredient_name' => $item->ingredientProduct?->name ?? '—',
                    'quantity' => $quantity,
                    'waste_percent' => $wastePercent,
                    'unit' => $item->unit?->abbreviation,
                    'line_cost' => round($requiredQty * $unitCost, 4),
                ];
            })->values()->all();

            $computedCost = round(collect($items)->sum('line_cost'), 4);

            return [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'version' => $recipe->version,
                'yield_quantity' => (float) $recipe->yield_quantity,
                'items_count' => count($items),
                'computed_cost' => $computedCost,
                'items' => $items,
                'product' => [
                    'id' => $recipe->product->id,
                    'name' => $recipe->product->name,
                    'cost_price' => (float) $recipe->product->cost_price,
                    'sale_price' => (float) $recipe->product->sale_price,
                    'category' => $recipe->product->category?->name,
                ],
            ];
        });

        return Inertia::render('admin/recipes/index', [
            'recipes' => $recipes,
        ]);
    }
}
