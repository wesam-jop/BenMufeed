<?php

namespace App\Filament\Pages;

use App\Models\PosSetting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PosPrintingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?string $navigationLabel = 'الطباعات والمطبخ';

    protected static ?string $title = 'الطباعات والمطبخ';

    protected static string|\UnitEnum|null $navigationGroup = 'المطعم';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'pos-printing';

    protected string $view = 'filament.pages.pos-printing-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(PosSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الطباعة التلقائية')
                    ->description('تعيين أسماء الطابعات من صفحة «ربط الطابعات» على جهاز الكاشير/المطبخ.')
                    ->schema([
                        Toggle::make('auto_print_kitchen')
                            ->label('طباعة تلقائية على المطبخ عند وصول طلب')
                            ->default(true),
                        Toggle::make('auto_print_cashier')
                            ->label('طباعة تلقائية للفاتورة عند إغلاق الطاولة أو البيع السفري')
                            ->default(true),
                        Toggle::make('kitchen_alert_sound')
                            ->label('تشغيل صوت تنبيه في POS والمطبخ')
                            ->default(true),
                    ])
                    ->columnSpanFull(),
                Section::make('التوصيل')
                    ->schema([
                        TextInput::make('delivery_fee')
                            ->label('رسوم التوصيل الافتراضية')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(config('store.currency_symbol', '$'))
                            ->default(0)
                            ->helperText('تُضاف تلقائياً لطلبات «توصيل» من الموقع.'),
                    ])
                    ->columnSpanFull(),
                Section::make('تصميم الفاتورة والتذكرة')
                    ->schema([
                        TextInput::make('receipt_business_name')
                            ->label('اسم المنشأة على الفاتورة')
                            ->placeholder('اتركه فارغاً لاستخدام اسم الموقع')
                            ->maxLength(120),
                        Select::make('receipt_paper_width_mm')
                            ->label('عرض الورق (مم)')
                            ->options([
                                58 => '58 مم (حراري صغير)',
                                80 => '80 مم (حراري قياسي)',
                            ])
                            ->default(80)
                            ->native(false),
                        Toggle::make('receipt_show_logo')
                            ->label('إظهار الشعار')
                            ->default(true),
                        Textarea::make('receipt_header')
                            ->label('نص أعلى الفاتورة')
                            ->rows(2)
                            ->placeholder('مثال: بن مفيد — كافيتيريا')
                            ->columnSpanFull(),
                        Textarea::make('receipt_footer')
                            ->label('نص أسفل فاتورة الكاشير')
                            ->rows(2)
                            ->placeholder('مثال: شكراً لزيارتكم — نراكم قريباً')
                            ->columnSpanFull(),
                        Textarea::make('kitchen_ticket_footer')
                            ->label('نص أسفل تذكرة المطبخ')
                            ->rows(2)
                            ->placeholder('مثال: راجع رقم الطاولة قبل التقديم')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PosSetting::current()->update($data);

        Notification::make()
            ->title('تم حفظ إعدادات الطباعة')
            ->success()
            ->send();
    }
}
