<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const ROLES = [
        'admin' => ['*'],
        'manager' => [
            'dashboard.view',
            'dashboard.view_financial',
            'dashboard.view_operational',
            'products.view', 'products.create', 'products.update', 'products.manage_categories', 'products.manage_modifiers',
            'inventory.view', 'inventory.adjust', 'inventory.count', 'inventory.transfer', 'inventory.waste',
            'purchasing.view', 'purchasing.create_po', 'purchasing.receive', 'purchasing.manage_suppliers',
            'sales.view', 'sales.create', 'sales.refund', 'sales.discount', 'sales.print_invoice',
            'sales.update', 'sales.cancel',
            'pos.access', 'pos.open_session', 'pos.close_session',
            'tables.view', 'tables.manage',
            'kitchen.view',
            'orders.view', 'orders.update', 'orders.delete', 'orders.update_status', 'orders.accept_delivery', 'orders.accept_table',
            'invoices.view', 'invoices.update', 'invoices.delete',
            'manufacturing.view', 'manufacturing.create',
            'accounting.view_accounts', 'accounting.view_profit',
            'expenses.view', 'expenses.create',
            'reports.sales', 'reports.products', 'reports.inventory', 'reports.customers', 'reports.financial',
            'users.view', 'roles.view',
            'settings.business', 'settings.branches', 'settings.pos', 'settings.taxes', 'settings.delivery',
            'settings.view', 'settings.update',
            'audit.view',
        ],
        'cashier' => [
            'pos.access', 'pos.open_session', 'pos.close_session', 'pos.cash_in', 'pos.cash_out',
            'sales.view', 'sales.create', 'sales.update', 'sales.cancel', 'sales.discount', 'sales.print_invoice',
            'tables.view',
            'orders.view', 'orders.update_status', 'orders.accept_delivery', 'orders.reject_delivery', 'orders.accept_table', 'orders.reject_table',
        ],
        'kitchen' => [
            'kitchen.view', 'kitchen.update_status',
            'orders.view', 'orders.update_status',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::query()->where('guard_name', 'web')->pluck('name');

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            if ($permissions === ['*']) {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $role->syncPermissions(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', $permissions)
                    ->pluck('name'),
            );
        }
    }
}
