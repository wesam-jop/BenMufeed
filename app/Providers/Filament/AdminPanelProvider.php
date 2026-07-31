<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Support\AdminPageHelp\Registry;
use App\Support\FilamentBranding;
use App\Http\Middleware\FilamentRedirectSessionToLogin;
use App\Http\Middleware\FilamentRedirectToLogin;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn (): string => FilamentBranding::brandName())
            ->brandLogo(fn (): ?string => FilamentBranding::brandLogoUrl())
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): ?string => FilamentBranding::brandLogoUrl())
            ->colors(FilamentBranding::panelColors())
            ->font('Tajawal', 'https://fonts.bunny.net/css?family=el-messiri:500,600,700|tajawal:400,500,700&display=swap')
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationItems([
                NavigationItem::make('انواع البيع')
                    ->url('/pos')
                    ->icon('heroicon-o-calculator')
                    ->group('المطعم')
                    ->sort(0),
                NavigationItem::make('الطابعات')
                    ->url('/pos/printing')
                    ->icon('heroicon-o-printer')
                    ->group('المطعم')
                    ->sort(4),
                NavigationItem::make('سجل الطباعة')
                    ->url('/pos/print-log')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->group('المطعم')
                    ->sort(7),
                NavigationItem::make('فواتير الشراء')
                    ->url('/invoices/purchases')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->group('المخزون والفواتير')
                    ->sort(4),
                NavigationItem::make('المبيعات')
                    ->url('/invoices/daily-sales')
                    ->icon('heroicon-o-shopping-bag')
                    ->group('المخزون والفواتير')
                    ->sort(5),
                NavigationItem::make('فواتير المبيع')
                    ->url('/invoices/sales')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->group('المخزون والفواتير')
                    ->sort(6),
                NavigationItem::make('الحركات المالية')
                    ->url('/invoices/financial-movements')
                    ->icon('heroicon-o-banknotes')
                    ->group('المخزون والفواتير')
                    ->sort(7),
                NavigationItem::make('الرواتب')
                    ->url('/invoices/payroll')
                    ->icon('heroicon-o-currency-dollar')
                    ->group('المخزون والفواتير')
                    ->sort(8),
                NavigationItem::make('الأرباح')
                    ->url('/invoices/profits')
                    ->icon('heroicon-o-chart-bar')
                    ->group('المخزون والفواتير')
                    ->sort(9),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.brand-styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                function (): string {
                    $topic = Registry::resolveTopic(Livewire::current());

                    if ($topic === null) {
                        return '';
                    }

                    return view('filament.page-help-button', [
                        'helpUrl' => Registry::url($topic),
                    ])->render();
                },
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                FilamentRedirectSessionToLogin::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentRedirectToLogin::class,
            ]);
    }
}
