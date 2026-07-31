<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Filament\Support\InvoiceLineSchema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
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
                        ->helperText('عند التأكيد تُضاف الكميات للمخزون.'),
                ]),
                Grid::make(2)->schema([
                    Select::make('payment_type')
                        ->label('نوع الدفع')
                        ->options(collect(PaymentType::cases())->mapWithKeys(fn (PaymentType $type) => [$type->value => $type->label()]))
                        ->required()
                        ->native(false)
                        ->default(PaymentType::Cash->value)
                        ->live(),
                    Select::make('customer_id')
                        ->label('العميل')
                        ->relationship(
                            'customer',
                            'name',
                            fn ($query) => $query->where('is_active', true)->orderBy('name'),
                        )
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                        ->helperText(fn (Get $get): string => $get('payment_type') === PaymentType::Credit->value
                            ? 'إلزامي عند الدفع دين.'
                            : 'اختياري عند الدفع نقدي.'),
                ]),
                Repeater::make('items')
                    ->label('بنود الفاتورة')
                    ->relationship('items')
                    ->schema([
                        Grid::make(6)->schema(InvoiceLineSchema::purchaseLineFields()),
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
