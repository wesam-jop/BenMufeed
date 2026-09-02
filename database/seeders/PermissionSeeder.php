<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'dashboard.view',
        'dashboard.view_financial',
        'dashboard.view_operational',
        'products.view',
        'products.create',
        'products.update',
        'products.delete',
        'products.update_price',
        'products.update_cost',
        'products.manage_images',
        'products.manage_categories',
        'products.manage_modifiers',
        'inventory.view',
        'inventory.adjust',
        'inventory.count',
        'inventory.waste',
        'inventory.transfer',
        'inventory.approve_adjustment',
        'inventory.view_cost',
        'purchasing.view',
        'purchasing.create_po',
        'purchasing.receive',
        'purchasing.create_invoice',
        'purchasing.pay_supplier',
        'purchasing.manage_suppliers',
        'sales.view',
        'sales.create',
        'sales.update',
        'sales.cancel',
        'sales.refund',
        'sales.discount',
        'sales.override_price',
        'sales.print_invoice',
        'pos.access',
        'pos.open_session',
        'pos.close_session',
        'pos.cash_in',
        'pos.cash_out',
        'tables.view',
        'tables.manage',
        'tables.merge',
        'tables.split',
        'tables.transfer',
        'restaurant-tables.view',
        'restaurant-tables.create',
        'restaurant-tables.update',
        'restaurant-tables.delete',
        'table-sessions.view',
        'table-sessions.create',
        'table-sessions.update',
        'table-sessions.delete',
        'kitchen.view',
        'kitchen.update_status',
        'orders.view',
        'orders.create',
        'orders.update',
        'orders.delete',
        'orders.accept_delivery',
        'orders.reject_delivery',
        'orders.accept_table',
        'orders.reject_table',
        'orders.update_status',
        'invoices.view',
        'invoices.create',
        'invoices.update',
        'invoices.delete',
        'manufacturing.view',
        'manufacturing.create',
        'manufacturing.complete',
        'manufacturing.cancel',
        'accounting.view_accounts',
        'accounting.create_entry',
        'accounting.reverse_entry',
        'accounting.close_period',
        'accounting.reopen_period',
        'accounting.view_profit',
        'accounting.view_balance_sheet',
        'expenses.view',
        'expenses.create',
        'expenses.update',
        'expenses.delete',
        'reports.sales',
        'reports.products',
        'reports.inventory',
        'reports.financial',
        'reports.customers',
        'reports.employees',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.assign_roles',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.assign_permissions',
        'settings.business',
        'settings.branches',
        'settings.pos',
        'settings.taxes',
        'settings.delivery',
        'settings.system',
        'settings.view',
        'settings.update',
        'audit.view',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }
    }
}
