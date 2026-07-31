<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use App\Support\PhoneCountries;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_name')
                    ->label('اسم الموقع')
                    ->required()
                    ->maxLength(255),
                Textarea::make('site_description')
                    ->label('وصف الموقع')
                    ->rows(3)
                    ->columnSpanFull(),
                Section::make('واتساب')
                    ->description('يُستخدم في الموقع وروابط wa.me لتأكيد طلبات الطعام.')
                    ->schema([
                        Grid::make()
                            ->columns(['default' => 1, 'lg' => 5])
                            ->schema([
                                Select::make('whatsapp_country')
                                    ->label('الدولة / رمز النداء')
                                    ->options(PhoneCountries::options())
                                    ->default(PhoneCountries::defaultOptionKey())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->dehydrated(false)
                                    ->columnSpan(['default' => 1, 'lg' => 2]),
                                TextInput::make('whatsapp_local_number')
                                    ->label('رقم الجوال')
                                    ->tel()
                                    ->inputMode('tel')
                                    ->placeholder('9xxxxxxxx')
                                    ->required()
                                    ->maxLength(16)
                                    ->dehydrated(false)
                                    ->live(debounce: 400)
                                    ->helperText(fn (Get $get): string => PhoneCountries::preview(
                                        $get('whatsapp_country'),
                                        $get('whatsapp_local_number'),
                                    ))
                                    ->columnSpan(['default' => 1, 'lg' => 3]),
                            ]),
                    ])
                    ->columnSpanFull(),
                TextInput::make('facebook_url')
                    ->label('رابط Facebook')
                    ->url()
                    ->maxLength(255),
                TextInput::make('instagram_url')
                    ->label('رابط Instagram')
                    ->url()
                    ->maxLength(255),
                TextInput::make('tiktok_url')
                    ->label('رابط TikTok')
                    ->url()
                    ->maxLength(255),
                TextInput::make('hero_title')
                    ->label('عنوان الصفحة الرئيسية')
                    ->maxLength(255)
                    ->placeholder('مثال: نكهات تروي القصة'),
                TextInput::make('hero_subtitle')
                    ->label('نص فرعي للصفحة الرئيسية')
                    ->maxLength(255)
                    ->placeholder('مثال: استمتع بتجربة طعام أنيقة'),
                TextInput::make('seo_title')
                    ->label('عنوان SEO للرئيسية')
                    ->maxLength(60)
                    ->helperText('يظهر في Google والمشاركة — حوالي 60 حرفاً.'),
                Textarea::make('seo_description')
                    ->label('وصف SEO للرئيسية')
                    ->maxLength(160)
                    ->rows(3)
                    ->helperText('وصف قصير للصفحة الرئيسية في نتائج البحث.'),
                Textarea::make('seo_keywords')
                    ->label('كلمات مفتاحية عامة للمطعم')
                    ->rows(2)
                    ->helperText('تُدمج تلقائياً في أوصاف الأطباق والأقسام عند الحاجة. افصل بفاصلة.'),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label('شعار الموقع')
                    ->helperText('يظهر في واجهة المطعم، الفوتر، أيقونة المتصفح، ومشاركة الروابط.')
                    ->collection('logo')
                    ->disk(config('media-library.disk_name'))
                    ->visibility('public')
                    ->image()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('hero_banner')
                    ->label('بانر الصفحة الرئيسية')
                    ->collection('hero_banner')
                    ->disk(config('media-library.disk_name'))
                    ->visibility('public')
                    ->image()
                    ->columnSpanFull(),
            ]);
    }
}
