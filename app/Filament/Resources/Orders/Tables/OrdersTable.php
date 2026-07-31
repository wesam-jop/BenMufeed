<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state : OrderStatus::from((string) $state)) {
                        OrderStatus::New => 'warning',
                        OrderStatus::Reviewed => 'info',
                        OrderStatus::Open => 'primary',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof OrderStatus ? $state : OrderStatus::from((string) $state);

                        return $enum->label();
                    }),
                TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => ($state instanceof OrderSource ? $state : OrderSource::from((string) $state))->label()),
                TextColumn::make('service_type')
                    ->label('النوع')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $state ? ($state instanceof \App\Enums\PosServiceType ? $state : \App\Enums\PosServiceType::from((string) $state))->label() : '—')
                    ->placeholder('—'),
                TextColumn::make('customer_name')->label('العميل')->placeholder('—')->searchable(),
                TextColumn::make('table_number')->label('طاولة')->placeholder('—'),
                TextColumn::make('total')->label('الإجمالي')->suffix(' $')->sortable(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('source')
                    ->label('المصدر')
                    ->options(collect(OrderSource::cases())->mapWithKeys(fn (OrderSource $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
