<?php

$basePath = dirname(__DIR__);

$resources = [
    'Product' => [
        'category_id' => 'nullable|exists:product_categories,id',
        'type' => 'required|string',
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:100',
        'barcode' => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'image' => 'nullable|string|max:255',
        'unit_id' => 'nullable|exists:units,id',
        'tax_rate_id' => 'nullable|exists:tax_rates,id',
        'sale_price' => 'required|numeric|min:0',
        'cost_price' => 'nullable|numeric|min:0',
        'track_stock' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_active' => 'boolean',
        'min_stock' => 'nullable|numeric|min:0',
        'max_stock' => 'nullable|numeric|min:0',
    ],
    'ProductCategory' => [
        'parent_id' => 'nullable|exists:product_categories,id',
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'image' => 'nullable|string|max:255',
        'sort_order' => 'nullable|integer|min:0',
        'is_active' => 'boolean',
    ],
    'ModifierGroup' => [
        'name' => 'required|string|max:255',
        'min_select' => 'nullable|integer|min:0',
        'max_select' => 'nullable|integer|min:0',
        'is_required' => 'boolean',
    ],
    'Recipe' => [
        'product_id' => 'required|exists:products,id',
        'variant_id' => 'nullable|exists:product_variants,id',
        'name' => 'required|string|max:255',
        'yield_quantity' => 'required|numeric|min:0',
        'version' => 'nullable|integer|min:1',
        'is_active' => 'boolean',
    ],
    'Warehouse' => [
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'is_active' => 'boolean',
    ],
    'WastageRecord' => [
        'warehouse_id' => 'required|exists:warehouses,id',
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|numeric|min:0.0001',
        'reason' => 'required|string',
        'notes' => 'nullable|string',
    ],
    'StockTransfer' => [
        'from_warehouse_id' => 'required|exists:warehouses,id',
        'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
        'notes' => 'nullable|string',
    ],
    'StockCount' => [
        'warehouse_id' => 'required|exists:warehouses,id',
        'notes' => 'nullable|string',
    ],
    'Supplier' => [
        'name' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        'tax_number' => 'nullable|string|max:100',
        'is_active' => 'boolean',
    ],
    'PurchaseOrder' => [
        'supplier_id' => 'required|exists:suppliers,id',
        'expected_date' => 'nullable|date',
    ],
    'PurchaseInvoice' => [
        'supplier_id' => 'required|exists:suppliers,id',
        'purchase_order_id' => 'nullable|exists:purchase_orders,id',
        'invoice_number' => 'required|string|max:100',
        'due_date' => 'nullable|date',
    ],
    'Order' => [
        'type' => 'required|string',
        'status' => 'nullable|string',
        'table_session_id' => 'nullable|exists:table_sessions,id',
        'customer_id' => 'nullable|exists:customers,id',
        'notes' => 'nullable|string',
        'source' => 'nullable|string|max:100',
        'delivery_address' => 'nullable|string',
    ],
    'Invoice' => [
        'order_id' => 'nullable|exists:orders,id',
        'customer_id' => 'nullable|exists:customers,id',
        'status' => 'nullable|string',
    ],
    'Refund' => [
        'invoice_id' => 'required|exists:invoices,id',
        'amount' => 'required|numeric|min:0.01',
        'reason' => 'required|string|max:500',
    ],
    'RestaurantTable' => [
        'name' => 'required|string|max:100',
        'capacity' => 'required|integer|min:1',
        'zone' => 'nullable|string|max:100',
        'status' => 'nullable|string',
        'sort_order' => 'nullable|integer|min:0',
    ],
    'TableSession' => [
        'table_id' => 'required|exists:restaurant_tables,id',
        'guest_count' => 'nullable|integer|min:1',
    ],
    'CashRegister' => [
        'name' => 'required|string|max:255',
        'is_active' => 'boolean',
    ],
    'CashRegisterSession' => [
        'cash_register_id' => 'required|exists:cash_registers,id',
        'opening_balance' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
    ],
    'Expense' => [
        'category' => 'required|string|max:100',
        'amount' => 'required|numeric|min:0.01',
        'account_id' => 'nullable|exists:chart_of_accounts,id',
        'payment_method' => 'required|string',
        'description' => 'nullable|string',
        'expense_date' => 'required|date',
    ],
    'ProductionOrder' => [
        'product_id' => 'required|exists:products,id',
        'warehouse_id' => 'required|exists:warehouses,id',
        'planned_qty' => 'required|numeric|min:0.0001',
    ],
    'ChartOfAccount' => [
        'code' => 'required|string|max:20',
        'name' => 'required|string|max:255',
        'type' => 'required|string',
        'parent_id' => 'nullable|exists:chart_of_accounts,id',
        'is_active' => 'boolean',
    ],
    'JournalEntry' => [
        'date' => 'required|date',
        'description' => 'required|string|max:500',
        'lines' => 'required|array|min:2',
        'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
        'lines.*.debit' => 'required|numeric|min:0',
        'lines.*.credit' => 'required|numeric|min:0',
        'lines.*.description' => 'nullable|string|max:255',
    ],
    'User' => [
        'name' => 'required|string|max:255',
        'username' => 'required|string|max:100',
        'email' => 'nullable|email|max:255',
        'password' => 'required|string|min:8',
        'pin' => 'nullable|string|min:4|max:6',
        'branch_id' => 'nullable|exists:branches,id',
        'is_active' => 'boolean',
        'roles' => 'nullable|array',
        'roles.*' => 'string|exists:roles,name',
    ],
];

function rulesToPhp(array $rules): string
{
    $lines = [];
    foreach ($rules as $field => $rule) {
        $lines[] = "            '{$field}' => '{$rule}',";
    }

    return implode("\n", $lines);
}

foreach ($resources as $model => $storeRules) {
    $dir = "{$basePath}/app/Http/Requests/Admin/{$model}";
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $storeRulesPhp = rulesToPhp($storeRules);
    $updateRules = $storeRules;
    if (isset($updateRules['password'])) {
        $updateRules['password'] = 'nullable|string|min:8';
    }
    $updateRulesPhp = rulesToPhp($updateRules);

    file_put_contents("{$dir}/Store{$model}Request.php", <<<PHP
<?php

namespace App\Http\Requests\Admin\\{$model};

use Illuminate\Foundation\Http\FormRequest;

class Store{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
{$storeRulesPhp}
        ];
    }
}

PHP);

    file_put_contents("{$dir}/Update{$model}Request.php", <<<PHP
<?php

namespace App\Http\Requests\Admin\\{$model};

use Illuminate\Foundation\Http\FormRequest;

class Update{$model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
{$updateRulesPhp}
        ];
    }
}

PHP);
}

// Stock adjust request
$stockDir = "{$basePath}/app/Http/Requests/Admin/Stock";
if (! is_dir($stockDir)) {
    mkdir($stockDir, 0777, true);
}

file_put_contents("{$stockDir}/AdjustStockRequest.php", <<<'PHP'
<?php

namespace App\Http\Requests\Admin\Stock;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|string',
            'quantity' => 'required|numeric',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:255',
        ];
    }
}

PHP);

// Role requests
$roleDir = "{$basePath}/app/Http/Requests/Admin/Role";
if (! is_dir($roleDir)) {
    mkdir($roleDir, 0777, true);
}

file_put_contents("{$roleDir}/StoreRoleRequest.php", <<<'PHP'
<?php

namespace App\Http\Requests\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}

PHP);

file_put_contents("{$roleDir}/UpdateRoleRequest.php", <<<'PHP'
<?php

namespace App\Http\Requests\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}

PHP);

// Settings request
$settingsDir = "{$basePath}/app/Http/Requests/Admin/Settings";
if (! is_dir($settingsDir)) {
    mkdir($settingsDir, 0777, true);
}

file_put_contents("{$settingsDir}/UpdateBusinessSettingsRequest.php", <<<'PHP'
<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:100',
            'receipt_footer' => 'nullable|string|max:500',
        ];
    }
}

PHP);

echo "Form requests created.\n";
