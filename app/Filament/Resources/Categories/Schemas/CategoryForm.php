<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الفئة')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                Textarea::make('description')
                    ->label('وصف الفئة')
                    ->helperText('الرابط (Slug) وعنوان SEO والوصف والكلمات المفتاحية تُولَّد تلقائياً من الاسم والوصف.')
                    ->rows(4)
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('image')
                    ->label('صورة التصنيف')
                    ->helperText('تظهر في الصفحة الرئيسية وفي أعلى صفحة التصنيف. يُفضّل نسبة قريبة من 3:2.')
                    ->collection('image')
                    ->disk(config('media-library.disk_name'))
                    ->visibility('public')
                    ->image()
                    ->maxSize(4096)
                    ->columnSpanFull(),
            ]);
    }
}
