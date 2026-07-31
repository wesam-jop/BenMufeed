<?php

namespace App\Filament\Resources\HomeSliders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeSlidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->description('الترتيب يُحدَّد تلقائياً عند الإضافة. اسحب الصفوف لإعادة الترتيب.')
            ->columns([
                SpatieMediaLibraryImageColumn::make('slide')
                    ->label('صورة')
                    ->collection('slide')
                    ->conversion('thumb')
                    ->height(48)
                    ->square(false),
                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
