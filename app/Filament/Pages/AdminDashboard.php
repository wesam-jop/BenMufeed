<?php

namespace App\Filament\Pages;

use App\Support\FilamentBranding;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class AdminDashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    /** يطابق مسار Filament الافتراضي (filament.admin.pages.dashboard). */
    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationLabel = 'الرئيسية';

    protected static ?string $title = 'لوحة التحكم';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.pages.admin-dashboard-hero')
                    ->viewData(fn (): array => [
                        'brandName' => FilamentBranding::brandName(),
                        'userName' => auth()->user()?->name ?? '',
                        'quickLinks' => $this->quickLinks(),
                    ]),
                $this->getWidgetsContentComponent(),
            ]);
    }

    /**
     * @return array<int, array{label: string, description: string, url: string, icon: string, color: 'primary'|'accent'|'warm'|'muted'}>
     */
    public function quickLinks(): array
    {
        return [
            [
                'label' => 'انواع البيع',
                'description' => 'بيع عالطاولة وسفر',
                'url' => url('/pos'),
                'icon' => 'heroicon-o-calculator',
                'color' => 'primary',
            ],
            [
                'label' => 'الطابعات',
                'description' => 'طابعة المطبخ والكاشير',
                'url' => url('/pos/printing'),
                'icon' => 'heroicon-o-printer',
                'color' => 'accent',
            ],
            [
                'label' => 'طلبات الموقع',
                'description' => 'قبول ورفض',
                'url' => url('/admin/orders?tableFilters[status][value]=new'),
                'icon' => 'heroicon-o-globe-alt',
                'color' => 'warm',
            ],
            [
                'label' => 'الاطباق',
                'description' => 'قائمة المنيو',
                'url' => url('/admin/products'),
                'icon' => 'heroicon-o-shopping-bag',
                'color' => 'muted',
            ],
            [
                'label' => 'المبيعات اليوم',
                'description' => 'جرد يومي',
                'url' => url('/invoices/daily-sales'),
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'accent',
            ],
            [
                'label' => 'الطباعات والمطبخ',
                'description' => 'طابعات POS',
                'url' => url('/admin/pos-printing'),
                'icon' => 'heroicon-o-printer',
                'color' => 'muted',
            ],
        ];
    }
}
