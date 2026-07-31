<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Filament\Support\InvoiceLineSchema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SalesInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $symbol = config('store.currency_symbol', '$');

        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('invoice_number')
                        ->label('رقم الفاتورة')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('يُولَّد تلقائياً'),
                    DatePicker::make('invoice_date')
                        ->label('تاريخ الفاتورة')
                        ->required()
                        ->default(now())
                        ->native(false),
                    Select::make('status')
                        ->label('الحالة')
                        ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $s) => [$s->value => $s->label()]))
                        ->required()
                        ->native(false)
                        ->default(InvoiceStatus::Confirmed->value)
                        ->helperText('عند التأكيد تُخصم الكميات من المخزون.'),
                ]),
                TextInput::make('customer_name')
                    ->label('اسم العميل')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('بنود الفاتورة')
                    ->relationship('items')
                    ->schema([
                        Grid::make(6)->schema(InvoiceLineSchema::salesLineFields()),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('إضافة مادة')
                    ->reorderable(false)
                    ->columnSpanFull(),
                Grid::make(3)->schema([
                    TextInput::make('subtotal')
                        ->label('المجموع')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->suffix($symbol),
                    TextInput::make('total')
                        ->label('الإجمالي')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->suffix($symbol),
                ]),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
