<?php

namespace App\Filament\Resources\Materials\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        $symbol = config('store.currency_symbol', '$');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('المادة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('unit')
                    ->label('الوحدة')
                    ->badge(),
                TextColumn::make('stock_quantity')
                    ->label('المخزون')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->isLowStock() ? 'danger' : 'success'),
                TextColumn::make('stock_value')
                    ->label('قيمة المخزون')
                    ->state(fn ($record) => $record->stockValue())
                    ->suffix(" {$symbol}")
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw("(stock_quantity * purchase_price) {$direction}")),
                TextColumn::make('purchase_price')
                    ->label('شراء')
                    ->suffix(" {$symbol}")
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('بيع')
                    ->suffix(" {$symbol}")
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('نشطة'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
