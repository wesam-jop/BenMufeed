<?php

namespace App\Filament\Resources\RestaurantTables\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم الطاولة')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(999)
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->label('اسم/وصف (اختياري)')
                    ->maxLength(120)
                    ->placeholder('مثال: طاولة الشرفة 3'),
                Toggle::make('is_active')
                    ->label('مفعّلة')
                    ->default(true),
                TextInput::make('qr_token')
                    ->label('رمز QR')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }
}
