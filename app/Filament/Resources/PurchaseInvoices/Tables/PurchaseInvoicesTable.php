<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        $symbol = config('store.currency_symbol', '$');

        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('payment_type')
                    ->label('نوع الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof PaymentType ? $state : PaymentType::from((string) $state))->label())
                    ->color(fn ($state) => ($state instanceof PaymentType ? $state : PaymentType::from((string) $state)) === PaymentType::Credit ? 'warning' : 'success'),
                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof InvoiceStatus ? $state : InvoiceStatus::from((string) $state))->label())
                    ->color(fn ($state) => ($state instanceof InvoiceStatus ? $state : InvoiceStatus::from((string) $state))->color()),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->suffix(" {$symbol}")
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('البنود')
                    ->counts('items'),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn (InvoiceStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('payment_type')
                    ->label('نوع الدفع')
                    ->options(collect(PaymentType::cases())->mapWithKeys(fn (PaymentType $type) => [$type->value => $type->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
