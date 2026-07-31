<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Filament\Support\CategoryOptions;
use App\Filament\Support\MoveProductsToCategoryBulkAction;
use App\Services\ProductSalesService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sold_quantity', 'desc')
            ->modifyQueryUsing(fn ($query) => app(ProductSalesService::class)->appendSalesAggregatesToProductsQuery($query))
            ->columns(self::columns())
            ->filters(self::filters())
            ->recordActions(self::recordActions())
            ->toolbarActions(self::toolbarActions())
            ->emptyStateHeading('لا توجد أطباق')
            ->emptyStateDescription('أضف طبقاً جديداً أو غيّر فلاتر البحث.')
            ->emptyStateIcon(Heroicon::OutlinedQueueList);
    }

    /**
     * @return array<int, TextColumn|SelectColumn|IconColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label('اسم الطبق')
                ->searchable()
                ->sortable()
                ->weight('medium'),
            SelectColumn::make('category_id')
                ->label('القسم')
                ->options(fn (): array => CategoryOptions::idNameMap())
                ->searchableOptions()
                ->native(false)
                ->sortable(),
            TextColumn::make('price')
                ->label('السعر')
                ->suffix(' $')
                ->sortable(),
            TextColumn::make('sold_quantity')
                ->label('الكمية المباعة')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->default(0),
            TextColumn::make('sold_revenue')
                ->label('إجمالي المبيعات')
                ->suffix(' $')
                ->sortable()
                ->alignEnd()
                ->formatStateUsing(fn ($state): string => number_format((float) ($state ?? 0), 2)),
            IconColumn::make('is_featured')
                ->label('مميز')
                ->boolean(),
            IconColumn::make('is_delivery_available')
                ->label('متاح للتوصيل')
                ->boolean(),
            TextColumn::make('status')
                ->label('الحالة')
                ->badge()
                ->formatStateUsing(function ($state): string {
                    $enum = $state instanceof ProductStatus ? $state : ProductStatus::from((string) $state);

                    return $enum->label();
                })
                ->sortable(),
            TextColumn::make('updated_at')
                ->label('آخر تحديث')
                ->since()
                ->sortable()
                ->toggleable(),
        ];
    }

    /**
     * @return array<int, SelectFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('category_id')
                ->label('القسم')
                ->options(fn (): array => CategoryOptions::idNameMap())
                ->searchable()
                ->native(false),
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(collect(ProductStatus::cases())->mapWithKeys(
                    fn (ProductStatus $status) => [$status->value => $status->label()],
                )),
        ];
    }

    /**
     * @return array<int, EditAction|DeleteAction>
     */
    public static function recordActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }

    /**
     * @return array<int, BulkActionGroup>
     */
    public static function toolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                MoveProductsToCategoryBulkAction::make(),
                DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]),
        ];
    }
}
