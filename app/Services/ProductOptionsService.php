<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ProductOptionsService
{
    /**
     * @param  array<int, array{product_addon_id: int, quantity?: int}>  $addonLines
     * @return array{
     *     product_id: int,
     *     product_variant_id: ?int,
     *     product_name: string,
     *     variant_name: ?string,
     *     options_label: ?string,
     *     quantity: int,
     *     unit_price: float,
     *     unit_cost: float,
     *     line_total: float,
     *     addons: list<array{product_addon_id: ?int, addon_name: string, quantity: int, unit_price: float, unit_cost: float, line_total: float}>
     * }
     */
    public function resolveLine(Product $product, ?int $variantId, array $addonLines, int $quantity = 1): array
    {
        $quantity = max(1, $quantity);

        $product->loadMissing([
            'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'addons' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
        ]);

        $activeVariants = $product->variants;
        $variant = null;

        if ($activeVariants->isNotEmpty()) {
            if ($variantId === null) {
                $variant = $activeVariants->firstWhere('is_default', true) ?? $activeVariants->first();
            } else {
                $variant = $activeVariants->firstWhere('id', $variantId);
            }

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'items' => "اختر نوعاً صالحاً لـ {$product->name}.",
                ]);
            }
        }

        $unitPrice = $variant !== null ? (float) $variant->price : (float) $product->price;
        $unitCost = $variant !== null ? $variant->unitCost() : (float) $product->recipeCost();

        $resolvedAddons = $this->resolveAddons($product, $addonLines);

        foreach ($resolvedAddons as $addonRow) {
            $unitPrice += (float) $addonRow['line_total'];
            $unitCost += (float) $addonRow['unit_cost'] * (int) $addonRow['quantity'];
        }

        $unitPrice = round($unitPrice, 2);
        $unitCost = round($unitCost, 2);
        $lineTotal = round($unitPrice * $quantity, 2);

        $variantName = $variant?->name;
        $optionsLabel = $this->buildOptionsLabel($variantName, $resolvedAddons);

        return [
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant?->getKey(),
            'product_name' => $product->name,
            'variant_name' => $variantName,
            'options_label' => $optionsLabel,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_cost' => $unitCost,
            'line_total' => $lineTotal,
            'addons' => $resolvedAddons,
        ];
    }

    /**
     * @param  array<int, array{product_id: int, product_variant_id?: ?int, quantity: int, addons?: array<int, array{product_addon_id: int, quantity?: int}>}>  $lines
     * @return array{0: Collection<int, Product>, 1: Collection<int, array<string, mixed>>, 2: float}
     */
    public function prepareOrderLines(array $lines): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('السلة فارغة — أضف أطباقاً أولاً.');
        }

        $productIds = collect($lines)->pluck('product_id')->unique()->values()->all();

        $products = Product::query()
            ->published()
            ->with([
                'recipeItems.material',
                'variants' => fn ($q) => $q->where('is_active', true),
                'addons' => fn ($q) => $q->where('is_active', true),
            ])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw new InvalidArgumentException('بعض الأطباق غير متوفرة.');
        }

        $items = collect();
        $total = 0.0;

        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);

            if ($product === null) {
                continue;
            }

            $resolved = $this->resolveLine(
                $product,
                isset($line['product_variant_id']) ? (int) $line['product_variant_id'] : null,
                $line['addons'] ?? [],
                (int) ($line['quantity'] ?? 1),
            );

            $items->push($resolved);
            $total += $resolved['line_total'];
        }

        return [$products, $items, round($total, 2)];
    }

    public function displayName(string $productName, ?string $variantName, ?string $optionsLabel): string
    {
        if (filled($optionsLabel)) {
            return "{$productName} ({$optionsLabel})";
        }

        if (filled($variantName)) {
            return "{$productName} — {$variantName}";
        }

        return $productName;
    }

    /**
     * @param  array<int, array{product_addon_id: int, quantity?: int}>  $addonLines
     * @return list<array{product_addon_id: ?int, addon_name: string, quantity: int, unit_price: float, unit_cost: float, line_total: float}>
     */
    protected function resolveAddons(Product $product, array $addonLines): array
    {
        if ($addonLines === []) {
            return [];
        }

        $byId = $product->addons->keyBy('id');
        $resolved = [];

        foreach ($addonLines as $line) {
            $addonId = (int) ($line['product_addon_id'] ?? 0);
            $qty = max(1, (int) ($line['quantity'] ?? 1));

            /** @var ProductAddon|null $addon */
            $addon = $byId->get($addonId);

            if ($addon === null) {
                throw ValidationException::withMessages([
                    'items' => "إضافة غير صالحة لـ {$product->name}.",
                ]);
            }

            if ($qty > $addon->max_quantity) {
                throw ValidationException::withMessages([
                    'items' => "الحد الأقصى لـ {$addon->name} هو {$addon->max_quantity}.",
                ]);
            }

            $unitPrice = (float) $addon->price;
            $lineTotal = round($unitPrice * $qty, 2);

            $resolved[] = [
                'product_addon_id' => $addon->getKey(),
                'addon_name' => $addon->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost' => $addon->unitCost(),
                'line_total' => $lineTotal,
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<array{addon_name: string, quantity: int}>  $addons
     */
    protected function buildOptionsLabel(?string $variantName, array $addons): ?string
    {
        $parts = [];

        if (filled($variantName)) {
            $parts[] = $variantName;
        }

        foreach ($addons as $addon) {
            $label = $addon['addon_name'];

            if ((int) $addon['quantity'] > 1) {
                $label .= ' ×'.$addon['quantity'];
            }

            $parts[] = $label;
        }

        if ($parts === []) {
            return null;
        }

        return implode(' + ', $parts);
    }
}
