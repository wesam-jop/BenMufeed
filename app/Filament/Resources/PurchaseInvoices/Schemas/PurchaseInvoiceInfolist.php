<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $symbol = config('store.currency_symbol', '$');

        return $schema
            ->components([
                TextEntry::make('invoice_number')->label('رقم الفاتورة'),
                TextEntry::make('payment_type')
                    ->label('نوع الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof PaymentType ? $state : PaymentType::from((string) $state))->label()),
                TextEntry::make('customer.name')->label('العميل')->placeholder('—'),
                TextEntry::make('invoice_date')->label('التاريخ')->date(),
                TextEntry::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof InvoiceStatus ? $state : InvoiceStatus::from((string) $state))->label()),
                TextEntry::make('total')->label('الإجمالي')->suffix(" {$symbol}"),
                RepeatableEntry::make('items')
                    ->label('البنود')
                    ->schema([
                        TextEntry::make('material_name')->label('المادة'),
                        TextEntry::make('quantity')->label('الكمية'),
                        TextEntry::make('unit')->label('الوحدة'),
                        TextEntry::make('unit_price')->label('السعر')->suffix(" {$symbol}"),
                        TextEntry::make('line_total')->label('الإجمالي')->suffix(" {$symbol}"),
                    ])
                    ->columnSpanFull(),
                TextEntry::make('notes')->label('ملاحظات')->placeholder('—')->columnSpanFull(),
            ]);
    }
}
