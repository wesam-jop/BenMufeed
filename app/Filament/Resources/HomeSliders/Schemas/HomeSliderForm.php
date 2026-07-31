<?php

namespace App\Filament\Resources\HomeSliders\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HomeSliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('العنوان')
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->label('النص الفرعي')
                    ->maxLength(500),
                TextInput::make('button_text')
                    ->label('نص الزر')
                    ->maxLength(64)
                    ->placeholder('مثال: تصفح الآن'),
                TextInput::make('button_url')
                    ->label('رابط الزر')
                    ->maxLength(500)
                    ->placeholder('/products أو https://...')
                    ->helperText('رابط داخلي يبدأ بـ / أو رابط خارجي كامل.'),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                SpatieMediaLibraryFileUpload::make('slide')
                    ->label('صورة الشريحة')
                    ->helperText('يُفضّل 1920×720 أو نسبة قريبة من 16:6. تظهر في أعلى الصفحة الرئيسية.')
                    ->collection('slide')
                    ->disk(config('media-library.disk_name'))
                    ->visibility('public')
                    ->image()
                    ->required()
                    ->maxSize(6144)
                    ->columnSpanFull(),
            ]);
    }
}
