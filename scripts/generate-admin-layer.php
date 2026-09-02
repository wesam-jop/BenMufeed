<?php

$basePath = dirname(__DIR__);

$policies = [
    'Product' => 'products',
    'ProductCategory' => 'product-categories',
    'ModifierGroup' => 'modifier-groups',
    'Recipe' => 'recipes',
    'Warehouse' => 'warehouses',
    'WastageRecord' => 'wastage',
    'StockTransfer' => 'stock-transfers',
    'StockCount' => 'stock-counts',
    'Supplier' => 'suppliers',
    'PurchaseOrder' => 'purchase-orders',
    'PurchaseInvoice' => 'purchase-invoices',
    'Order' => 'orders',
    'Invoice' => 'invoices',
    'Refund' => 'refunds',
    'RestaurantTable' => 'restaurant-tables',
    'TableSession' => 'table-sessions',
    'CashRegister' => 'cash-registers',
    'CashRegisterSession' => 'cash-register-sessions',
    'Expense' => 'expenses',
    'ProductionOrder' => 'production-orders',
    'ChartOfAccount' => 'chart-of-accounts',
    'JournalEntry' => 'journal-entries',
    'User' => 'users',
    'AuditLog' => 'audit-logs',
];

foreach ($policies as $model => $prefix) {
    $content = <<<PHP
<?php

namespace App\\Policies;

use App\\Models\\{$model};
use App\\Policies\\Concerns\\ChecksPermissions;
use Illuminate\\Database\\Eloquent\\Model;

class {$model}Policy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return '{$prefix}';
    }
}

PHP;
    file_put_contents("{$basePath}/app/Policies/{$model}Policy.php", $content);
}

// StockPolicy (StockMovement)
file_put_contents("{$basePath}/app/Policies/StockPolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function adjust(User $user): bool
    {
        return $this->allows($user, 'adjust');
    }

    protected function allows(User $user, string $action): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can("inventory.{$action}");
    }
}

PHP);

// RolePolicy (Spatie)
file_put_contents("{$basePath}/app/Policies/RolePolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'roles';
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }
}

PHP);

// DashboardPolicy
file_put_contents("{$basePath}/app/Policies/DashboardPolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function view(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('dashboard.view');
    }
}

PHP);

// AccountingReportPolicy
file_put_contents("{$basePath}/app/Policies/AccountingReportPolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;

class AccountingReportPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('accounting-reports.view');
    }

    public function profitLoss(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function balanceSheet(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function trialBalance(User $user): bool
    {
        return $this->viewAny($user);
    }
}

PHP);

// ReportPolicy
file_put_contents("{$basePath}/app/Policies/ReportPolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('reports.view');
    }

    public function sales(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function products(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function inventory(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function customers(User $user): bool
    {
        return $this->viewAny($user);
    }
}

PHP);

// SettingsPolicy
file_put_contents("{$basePath}/app/Policies/SettingsPolicy.php", <<<'PHP'
<?php

namespace App\Policies;

use App\Models\User;

class SettingsPolicy
{
    public function view(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('settings.view');
    }

    public function update(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('settings.update');
    }
}

PHP);

echo "Policies created.\n";
