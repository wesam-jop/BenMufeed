<?php

namespace App\Filament\Support;

use App\Models\Material;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class InvoiceLineSchema
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function purchaseLineFields(): array
    {
        return self::lineFields('purchase_price');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function salesLineFields(): array
    {
        return self::lineFields('sale_price');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function lineFields(string $priceField): array
    {
        $symbol = config('store.currency_symbol', '$');

        return [
            Select::make('material_id')
                ->label('المادة')
                ->relationship('material', 'name', fn ($query) => $query->where('is_active', true))
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (?int $state, Set $set, Get $get) use ($priceField): void {
                    if ($state === null) {
                        return;
                    }

                    $material = Material::query()->find($state);

                    if ($material === null) {
                        return;
                    }

                    $qty = (float) ($get('quantity') ?? 1);
                    $price = (float) $material->{$priceField};

                    $set('material_name', $material->name);
                    $set('unit', $material->unit);
                    $set('unit_price', $price);
                    $set('line_total', round($qty * $price, 2));
                })
                ->columnSpan(2),
            Hidden::make('material_name'),
            TextInput::make('unit')
                ->label('الوحدة')
                ->disabled()
                ->dehydrated(),
            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required()
                ->minValue(0.001)
                ->step(0.001)
                ->default(1)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncLineTotal($set, $get, $state)),
            TextInput::make('unit_price')
                ->label('سعر الوحدة')
                ->numeric()
                ->required()
                ->minValue(0)
                ->step(0.01)
                ->suffix($symbol)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncLineTotal($set, $get, $get('quantity'), $state)),
            TextInput::make('line_total')
                ->label('الإجمالي')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->suffix($symbol),
        ];
    }

    private static function syncLineTotal(Set $set, Get $get, mixed $quantity = null, mixed $unitPrice = null): void
    {
        $qty = (float) ($quantity ?? $get('quantity') ?? 0);
        $price = (float) ($unitPrice ?? $get('unit_price') ?? 0);

        $set('line_total', round($qty * $price, 2));
    }
}
