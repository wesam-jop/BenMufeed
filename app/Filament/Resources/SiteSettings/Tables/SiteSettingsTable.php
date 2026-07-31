<?php

namespace App\Filament\Resources\SiteSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site_name')->label('اسم الموقع')->searchable(),
                TextColumn::make('whatsapp_phone')
                    ->label('واتساب')
                    ->formatStateUsing(function (?string $state): string {
                        $digits = preg_replace('/\D+/', '', (string) $state) ?? '';

                        return $digits !== '' ? '+'.$digits : '—';
                    }),
                TextColumn::make('updated_at')->label('آخر تحديث')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
