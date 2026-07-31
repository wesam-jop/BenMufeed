<?php

namespace App\Filament\Resources\Materials\Schemas;

use App\Enums\MaterialUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        $symbol = config('store.currency_symbol', '$');

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المادة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('sku')
                    ->label('رمز المادة (SKU)')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('يُولَّد تلقائياً عند الحفظ')
                    ->visibleOn('edit'),
                Select::make('unit')
                    ->label('الوحدة')
                    ->options(collect(MaterialUnit::cases())->mapWithKeys(fn (MaterialUnit $u) => [$u->value => $u->label()]))
                    ->required()
                    ->native(false)
                    ->default(MaterialUnit::Piece->value),
                TextInput::make('purchase_price')
                    ->label('سعر الشراء الافتراضي')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix($symbol)
                    ->default(0),
                TextInput::make('sale_price')
                    ->label('سعر البيع الافتراضي')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix($symbol)
                    ->default(0),
                TextInput::make('stock_quantity')
                    ->label('الكمية الحالية')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.001)
                    ->default(0)
                    ->helperText('يُحدَّث تلقائياً عند تأكيد فواتير الشراء والمبيع.'),
                TextInput::make('min_stock')
                    ->label('حد التنبيه')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.001)
                    ->helperText('تنبيه عند انخفاض المخزون عن هذه الكمية.'),
                Toggle::make('is_active')
                    ->label('نشطة')
                    ->default(true),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
