<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductRecipeService
{
    /**
     * @param  array<int, array{product_id: int, quantity: int, product_variant_id?: ?int}>  $lines
     */
    public function assertStockAvailable(array $lines, Collection $products): void
    {
        $required = $this->aggregateRequiredMaterials($lines, $products);

        foreach ($required as $materialId => $qty) {
            $material = Material::query()->find($materialId);

            if ($material === null) {
                continue;
            }

            if ((float) $material->stock_quantity < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "المخزون غير كافٍ للمادة «{$material->name}» (مطلوب: {$qty} {$material->unit}، المتوفر: {$material->stock_quantity} {$material->unit}).",
                ]);
            }
        }
    }

    public function consumeForOrder(Order $order): void
    {
        $order->loadMissing('items');

        $lines = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
        ])->all();

        $this->consumeForLines($lines);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, product_variant_id?: ?int}>  $lines
     */
    public function consumeForLines(array $lines): void
    {
        $requirements = $this->buildRequirements($lines);

        if ($requirements === []) {
            return;
        }

        foreach ($requirements as $materialId => $qty) {
            $material = Material::query()->lockForUpdate()->find($materialId);

            if ($material === null) {
                continue;
            }

            if ((float) $material->stock_quantity < $qty) {
                throw ValidationException::withMessages([
                    'stock' => "المخزون غير كافٍ للمادة «{$material->name}» (مطلوب: {$qty} {$material->unit}، المتوفر: {$material->stock_quantity} {$material->unit}).",
                ]);
            }

            $material->decrement('stock_quantity', $qty);
        }
    }

    public function restoreForOrder(Order $order): void
    {
        $order->loadMissing('items');

        $lines = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
        ])->all();

        $this->restoreForLines($lines);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, product_variant_id?: ?int}>  $lines
     */
    public function restoreForLines(array $lines): void
    {
        $requirements = $this->buildRequirements($lines);

        foreach ($requirements as $materialId => $qty) {
            $material = Material::query()->lockForUpdate()->find($materialId);

            if ($material === null) {
                continue;
            }

            $material->increment('stock_quantity', $qty);
        }
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, product_variant_id?: ?int}>  $lines
     * @return array<int, float>
     */
    protected function aggregateRequiredMaterials(array $lines, Collection $products): array
    {
        return $this->buildRequirements($lines);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, product_variant_id?: ?int}>  $lines
     * @return array<int, float>
     */
    protected function buildRequirements(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $productIds = collect($lines)->pluck('product_id')->unique()->values()->all();
        $variantIds = collect($lines)->pluck('product_variant_id')->filter()->unique()->values()->all();

        $products = Product::query()
            ->with('recipeItems')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $variants = $variantIds === []
            ? collect()
            : ProductVariant::query()
                ->with('recipeItems')
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id');

        $required = [];

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);

            if ($product === null) {
                continue;
            }

            $orderQty = max(1, (int) $line['quantity']);
            $variantId = $line['product_variant_id'] ?? null;
            $variant = $variantId ? $variants->get($variantId) : null;

            $recipeItems = $this->recipeItemsForLine($product, $variant);

            foreach ($recipeItems as $recipeItem) {
                $materialId = $recipeItem->material_id;
                $qty = (float) $recipeItem->quantity * $orderQty;
                $required[$materialId] = ($required[$materialId] ?? 0) + $qty;
            }
        }

        return $required;
    }

    /**
     * @return iterable<int, \App\Models\ProductRecipeItem|\App\Models\ProductVariantRecipeItem>
     */
    protected function recipeItemsForLine(Product $product, ?ProductVariant $variant): iterable
    {
        if ($variant !== null) {
            $variant->loadMissing('recipeItems');

            if ($variant->recipeItems->isNotEmpty()) {
                return $variant->recipeItems;
            }
        }

        $product->loadMissing('recipeItems');

        return $product->recipeItems;
    }
}
