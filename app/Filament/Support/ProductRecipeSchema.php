<?php

namespace App\Filament\Support;

use App\Models\Material;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

final class ProductRecipeSchema
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function lineFields(): array
    {
        $symbol = config('store.currency_symbol', '$');

        return [
            Select::make('material_id')
                ->label('المادة')
                ->relationship(
                    'material',
                    'name',
                    fn ($query) => $query->where('is_active', true)->orderBy('name'),
                )
                ->searchable()
                ->preload()
                ->required()
                ->distinct()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->live()
                ->afterStateUpdated(fn (?int $state, Set $set, Get $get) => self::applyMaterial($state, $set, $get))
                ->afterStateHydrated(fn (?int $state, Set $set, Get $get) => self::applyMaterial($state, $set, $get))
                ->columnSpan(2),
            Hidden::make('material_name'),
            TextInput::make('unit')
                ->label('الوحدة')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('purchase_price')
                ->label('آخر سعر شراء')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->suffix($symbol)
                ->helperText('يُحدَّث تلقائياً من آخر فاتورة شراء.'),
            TextInput::make('quantity')
                ->label('الكمية لوحدة واحدة')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncLineCost($set, $get, $state)),
            TextInput::make('line_cost')
                ->label('تكلفة السطر')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->suffix($symbol),
        ];
    }

    public static function costSummaryPlaceholder(): Placeholder
    {
        return Placeholder::make('recipe_cost_summary')
            ->label('تكلفة الطبق')
            ->content(function (Get $get): HtmlString {
                return new HtmlString(self::formatCostSummary(
                    $get('recipeItems') ?? [],
                    (float) ($get('price') ?? 0),
                ));
            })
            ->columnSpanFull();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function calculateTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            if (empty($item['material_id'])) {
                continue;
            }

            $qty = (float) ($item['quantity'] ?? 0);

            if ($qty <= 0) {
                continue;
            }

            $price = (float) ($item['purchase_price'] ?? 0);

            if ($price <= 0) {
                $material = Material::query()->find($item['material_id']);
                $price = (float) ($material?->purchase_price ?? 0);
            }

            $total += $qty * $price;
        }

        return round($total, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function formatCostSummary(array $items, float $salePrice): string
    {
        $symbol = config('store.currency_symbol', '$');
        $cost = self::calculateTotal($items);

        if ($cost <= 0) {
            return '<p class="text-sm text-gray-500">أضف مواد الوصفة لحساب تكلفة الطبق.</p>';
        }

        $lines = [
            '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">',
            '<p class="mb-2 font-semibold text-amber-950">ملخص التكلفة (لوحدة واحدة)</p>',
            '<p><span class="text-gray-600">تكلفة الوصفة:</span> <strong class="text-lg text-amber-900">'.number_format($cost, 2).' '.$symbol.'</strong></p>',
        ];

        if ($salePrice > 0) {
            $profit = round($salePrice - $cost, 2);
            $margin = round(($profit / $salePrice) * 100, 1);
            $profitClass = $profit >= 0 ? 'text-emerald-700' : 'text-red-700';

            $lines[] = '<p class="mt-1"><span class="text-gray-600">سعر البيع الحالي:</span> <strong>'.number_format($salePrice, 2).' '.$symbol.'</strong></p>';
            $lines[] = '<p class="mt-1"><span class="text-gray-600">الربح التقريبي:</span> <strong class="'.$profitClass.'">'.number_format($profit, 2).' '.$symbol.' ('.$margin.'%)</strong></p>';
        } else {
            $lines[] = '<p class="mt-2 text-xs text-gray-600">حدّد سعر البيع أعلاه لمقارنة الربح المتوقع.</p>';
        }

        $lines[] = '</div>';

        return implode('', $lines);
    }

    private static function applyMaterial(?int $materialId, Set $set, Get $get): void
    {
        if ($materialId === null) {
            $set('unit', null);
            $set('purchase_price', null);
            $set('line_cost', null);
            $set('material_name', null);

            return;
        }

        $material = Material::query()->find($materialId);

        if ($material === null) {
            return;
        }

        $set('material_name', $material->name);
        $set('unit', (string) $material->unit);
        $set('purchase_price', round((float) $material->purchase_price, 2));
        self::syncLineCost($set, $get);
    }

    private static function syncLineCost(Set $set, Get $get, mixed $quantity = null): void
    {
        $qty = (float) ($quantity ?? $get('quantity') ?? 0);
        $price = (float) ($get('purchase_price') ?? 0);

        $set('line_cost', round($qty * $price, 2));
    }
}
