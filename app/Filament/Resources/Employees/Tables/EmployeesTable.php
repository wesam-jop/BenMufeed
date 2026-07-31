<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        $symbol = config('store.currency_symbol', '$');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('العامل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('monthly_salary')
                    ->label('الراتب الشهري')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' '.$symbol)
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('المتبقي للعامل')
                    ->state(fn (Employee $record): float => $record->balance())
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' '.$symbol)
                    ->color(fn (Employee $record): string => $record->balance() > 0 ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('نشط'),
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
