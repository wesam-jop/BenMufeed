<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Support\DeleteCategoryActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query): void {
                $query
                    ->withCount('products')
                    ->withCount(['products as published_products_count' => function ($q): void {
                        $q->published();
                    }]);
            })
            ->defaultSort('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('صورة')
                    ->collection('image')
                    ->conversion('thumb')
                    ->height(48)
                    ->square(false),
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->toggleable(),
                TextColumn::make('products_count')
                    ->label('عدد الأطباق')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('published_products_count')
                    ->label('منشور')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('updated_at')->label('آخر تحديث')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteCategoryActions::makeDeleteBulkAction(),
                ]),
            ]);
    }
}
