<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Filament\Support\ProductRecipeSchema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('الفئة')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الطبق')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true),
                Textarea::make('short_description')
                    ->label('وصف قصير')
                    ->helperText('يُستخدم في البطاقات ومحركات البحث. عنوان SEO والرابط والكلمات المفتاحية تُولَّد تلقائياً من الاسم والوصف.')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->label('وصف كامل')
                    ->columnSpanFull()
                    ->extraInputAttributes([
                        'style' => 'min-height: 20rem;',
                    ])
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'code'],
                        [ToolbarButtonGroup::make('عناوين', ['paragraph', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])],
                        [ToolbarButtonGroup::make('محاذاة', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList', 'horizontalRule', 'lead', 'small'],
                        ['table', 'attachFiles'],
                        ['undo', 'redo', 'clearFormatting'],
                    ]),
                TextInput::make('price')
                    ->label('سعر البيع')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(config('store.currency_symbol', '$'))
                    ->live(onBlur: true)
                    ->helperText('قارِن سعر البيع بتكلفة الوصفة في الأسفل.'),
                TextInput::make('compare_at_price')
                    ->label('سعر قبل الخصم')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(config('store.currency_symbol', '$'))
                    ->helperText('اختياري — بالدولار الأمريكي ($).'),
                Toggle::make('is_featured')
                    ->label('طبق مميز')
                    ->default(false),
                Toggle::make('is_delivery_available')
                    ->label('متاح للتوصيل')
                    ->helperText('أوقفه إذا كان هذا الصنف يُباع داخل الصالة أو سفري فقط ولا يمكن طلبه مع التوصيل.')
                    ->default(true),
                Select::make('status')
                    ->label('حالة الطبق')
                    ->options(collect(ProductStatus::cases())->mapWithKeys(fn (ProductStatus $s) => [$s->value => $s->label()]))
                    ->required()
                    ->native(false),
                Section::make('أنواع الطبق')
                    ->description('مثل: فواكه، موز، شوكولا — كل نوع له سعر بيع وتكلفة (اختياري).')
                    ->schema([
                        Repeater::make('variants')
                            ->label('الأنواع')
                            ->relationship('variants')
                            ->schema([
                                Grid::make(6)->schema([
                                    TextInput::make('name')
                                        ->label('اسم النوع')
                                        ->required()
                                        ->maxLength(120)
                                        ->columnSpan(2),
                                    TextInput::make('price')
                                        ->label('سعر البيع')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix(config('store.currency_symbol', '$'))
                                        ->columnSpan(2),
                                    TextInput::make('cost')
                                        ->label('التكلفة')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix(config('store.currency_symbol', '$'))
                                        ->helperText('اتركه فارغاً لاستخدام تكلفة الوصفة')
                                        ->columnSpan(2),
                                    Toggle::make('is_default')
                                        ->label('افتراضي')
                                        ->default(false)
                                        ->columnSpan(2),
                                    TextInput::make('sort_order')
                                        ->label('الترتيب')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(2),
                                    Toggle::make('is_active')
                                        ->label('مفعّل')
                                        ->default(true)
                                        ->columnSpan(2),
                                ]),
                                Repeater::make('recipeItems')
                                    ->label('وصفة هذا النوع')
                                    ->relationship('recipeItems')
                                    ->schema([
                                        Grid::make(6)->schema(ProductRecipeSchema::lineFields()),
                                    ])
                                    ->addActionLabel('إضافة مادة للنوع')
                                    ->reorderable(false)
                                    ->columnSpanFull()
                                    ->helperText('إن وُجدت، تُستخدم بدل وصفة الطبق الأساسية لهذا النوع.'),
                            ])
                            ->addActionLabel('إضافة نوع')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('إضافات الطبق')
                    ->description('مثل: مكسرات زيادة — تُضاف على سعر النوع المختار.')
                    ->schema([
                        Repeater::make('addons')
                            ->label('الإضافات')
                            ->relationship('addons')
                            ->schema([
                                Grid::make(6)->schema([
                                    TextInput::make('name')
                                        ->label('اسم الإضافة')
                                        ->required()
                                        ->maxLength(120)
                                        ->columnSpan(2),
                                    TextInput::make('price')
                                        ->label('سعر الإضافة')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix(config('store.currency_symbol', '$'))
                                        ->columnSpan(2),
                                    TextInput::make('cost')
                                        ->label('التكلفة')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix(config('store.currency_symbol', '$'))
                                        ->columnSpan(2),
                                    TextInput::make('max_quantity')
                                        ->label('الحد الأقصى')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->maxValue(10)
                                        ->columnSpan(2),
                                    TextInput::make('sort_order')
                                        ->label('الترتيب')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(2),
                                    Toggle::make('is_active')
                                        ->label('مفعّل')
                                        ->default(true)
                                        ->columnSpan(2),
                                ]),
                            ])
                            ->addActionLabel('إضافة إضافة')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('وصفة التكلفة')
                    ->description('حدد المواد والكميات لتحضير وحدة واحدة. يُجلب آخر سعر شراء لكل مادة تلقائياً من فواتير الشراء.')
                    ->schema([
                        Repeater::make('recipeItems')
                            ->label('مواد الوصفة')
                            ->relationship('recipeItems')
                            ->schema([
                                Grid::make(6)->schema(ProductRecipeSchema::lineFields()),
                            ])
                            ->addActionLabel('إضافة مادة')
                            ->reorderable(false)
                            ->live()
                            ->columnSpanFull(),
                        ProductRecipeSchema::costSummaryPlaceholder(),
                    ])
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->label('صور الطبق')
                    ->collection('images')
                    ->disk(config('media-library.disk_name'))
                    ->visibility('public')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->maxFiles(12)
                    ->columnSpanFull(),
            ]);
    }
}
