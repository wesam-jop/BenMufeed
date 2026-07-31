<?php

namespace App\Support\AdminPageHelp;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\InventoryOverview;
use App\Filament\Pages\PageHelpDocumentation;
use App\Filament\Pages\PosPrintingSettings;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\HomeSliders\Pages\CreateHomeSlider;
use App\Filament\Resources\HomeSliders\Pages\EditHomeSlider;
use App\Filament\Resources\HomeSliders\Pages\ListHomeSliders;
use App\Filament\Resources\Materials\Pages\CreateMaterial;
use App\Filament\Resources\Materials\Pages\EditMaterial;
use App\Filament\Resources\Materials\Pages\ListMaterials;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Filament\Resources\PurchaseInvoices\Pages\ViewPurchaseInvoice;
use App\Filament\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\EditSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\ListSalesInvoices;
use App\Filament\Resources\SalesInvoices\Pages\ViewSalesInvoice;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Filament\Resources\SiteSettings\Pages\ListSiteSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Pages\PageHelpDocumentation as HelpPage;

class Registry
{
    /** @var array<class-string, string> */
    private const PAGE_TOPIC_MAP = [
        AdminDashboard::class => 'dashboard',
        PosPrintingSettings::class => 'pos-printing',
        InventoryOverview::class => 'inventory',
        ListProducts::class => 'products',
        CreateProduct::class => 'products',
        EditProduct::class => 'products',
        ListCategories::class => 'categories',
        CreateCategory::class => 'categories',
        EditCategory::class => 'categories',
        ListOrders::class => 'orders',
        ViewOrder::class => 'orders',
        EditOrder::class => 'orders',
        ListSiteSettings::class => 'site-settings',
        EditSiteSetting::class => 'site-settings',
        ListHomeSliders::class => 'home-sliders',
        CreateHomeSlider::class => 'home-sliders',
        EditHomeSlider::class => 'home-sliders',
        ListRestaurantTables::class => 'restaurant-tables',
        CreateRestaurantTable::class => 'restaurant-tables',
        EditRestaurantTable::class => 'restaurant-tables',
        ListMaterials::class => 'materials',
        CreateMaterial::class => 'materials',
        EditMaterial::class => 'materials',
        ListCustomers::class => 'customers',
        CreateCustomer::class => 'customers',
        EditCustomer::class => 'customers',
        ListEmployees::class => 'employees',
        CreateEmployee::class => 'employees',
        EditEmployee::class => 'employees',
        ListUsers::class => 'users',
        CreateUser::class => 'users',
        EditUser::class => 'users',
        ListPurchaseInvoices::class => 'purchase-invoices',
        CreatePurchaseInvoice::class => 'purchase-invoices',
        EditPurchaseInvoice::class => 'purchase-invoices',
        ViewPurchaseInvoice::class => 'purchase-invoices',
        ListSalesInvoices::class => 'sales-invoices',
        CreateSalesInvoice::class => 'sales-invoices',
        EditSalesInvoice::class => 'sales-invoices',
        ViewSalesInvoice::class => 'sales-invoices',
        HelpPage::class => '',
    ];

    public static function resolveTopic(?object $livewire): ?string
    {
        if ($livewire === null) {
            return null;
        }

        $topic = self::PAGE_TOPIC_MAP[$livewire::class] ?? null;

        return $topic !== '' ? $topic : null;
    }

    /**
     * @return array<string, array{title: string, intro: string, sections: list<array{heading: string, description?: string, fields: list<array{label: string, text: string}>}>, tips?: list<string>}>|null
     */
    public static function get(string $topic): ?array
    {
        $path = __DIR__."/topics/{$topic}.php";

        if (! is_file($path)) {
            return null;
        }

        /** @var array{title: string, intro: string, sections: list<array>, tips?: list<string>} $data */
        $data = require $path;

        return $data;
    }

    public static function url(string $topic): string
    {
        return HelpPage::getUrl(['topic' => $topic]);
    }
}
