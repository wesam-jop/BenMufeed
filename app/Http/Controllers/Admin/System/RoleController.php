<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\System\Role\StoreRoleRequest;
use App\Http\Requests\Admin\System\Role\UpdateRoleRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends AdminController
{
    /** @var list<string> */
    private const SYSTEM_ROLES = ['admin', 'manager', 'cashier', 'kitchen'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $baseQuery = $this->rolesBaseQuery($request);

        $roles = (clone $baseQuery)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $roles->through(fn (Role $role) => $this->transformSummary($role));

        $allRoles = (clone $baseQuery)->get();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'summary' => $this->buildSummary($allRoles),
            'filters' => [
                'search' => $request->input('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('admin/roles/create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.show', $role)
            ->with('toast', ['type' => 'success', 'message' => __('toast.role_created')]);
    }

    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        $role->load('permissions')->loadCount('users');

        return Inertia::render('admin/roles/show', [
            'role' => $this->transformDetail($role),
            'permissionGroups' => $this->permissionGroupsForRole($role),
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return Inertia::render('admin/roles/edit', [
            'role' => $this->transformDetail($role),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): Response|RedirectResponse
    {
        $this->authorize('update', $role);

        if ($this->isSystemRole($role) && $request->validated('name') !== $role->name) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.role_system_rename'),
            ]);
        }

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.show', $role)
            ->with('toast', ['type' => 'success', 'message' => __('toast.role_updated')]);
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($this->isSystemRole($role)) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.role_system_delete'),
            ]);
        }

        if ($role->users()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.role_has_users'),
            ]);
        }

        $role->delete();

        return to_route('admin.roles.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.role_deleted')]);
    }

    private function rolesBaseQuery(Request $request): Builder
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount(['permissions', 'users']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    /** @param  Collection<int, Role>  $roles */
    private function buildSummary(Collection $roles): array
    {
        return [
            'roles_count' => $roles->count(),
            'system_roles_count' => $roles->filter(fn (Role $role) => $this->isSystemRole($role))->count(),
            'assigned_users_count' => (int) $roles->sum('users_count'),
            'permissions_count' => Permission::query()->where('guard_name', 'web')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => $this->roleLabel($role->name),
            'permissions_count' => (int) ($role->permissions_count ?? $role->permissions->count()),
            'users_count' => (int) ($role->users_count ?? 0),
            'is_system' => $this->isSystemRole($role),
            'permissions' => $role->permissions->pluck('name')->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(Role $role): array
    {
        return [
            ...$this->transformSummary($role),
            'created_at' => $role->created_at?->toIso8601String(),
            'updated_at' => $role->updated_at?->toIso8601String(),
        ];
    }

    /** @return list<array{group: string, group_label: string, permissions: list<array{name: string, label: string}>}> */
    private function permissionGroups(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['name'])
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0])
            ->map(function (Collection $permissions, string $group) {
                return [
                    'group' => $group,
                    'group_label' => $this->permissionGroupLabel($group),
                    'permissions' => $permissions
                        ->map(fn (Permission $permission) => [
                            'name' => $permission->name,
                            'label' => $this->permissionLabel($permission->name),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('group_label')
            ->values()
            ->all();
    }

    /** @return list<array{group: string, group_label: string, permissions: list<array{name: string, label: string, assigned: bool}>}> */
    private function permissionGroupsForRole(Role $role): array
    {
        $assigned = $role->permissions->pluck('name')->flip();

        return collect($this->permissionGroups())
            ->map(function (array $group) use ($assigned) {
                return [
                    ...$group,
                    'permissions' => collect($group['permissions'])
                        ->map(fn (array $permission) => [
                            ...$permission,
                            'assigned' => $assigned->has($permission['name']),
                        ])
                        ->values()
                        ->all(),
                    'assigned_count' => collect($group['permissions'])
                        ->filter(fn (array $permission) => $assigned->has($permission['name']))
                        ->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLES, true);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'مدير النظام',
            'manager' => 'مدير فرع',
            'cashier' => 'كاشير',
            'kitchen' => 'مطبخ',
            default => $role,
        };
    }

    private function permissionGroupLabel(string $group): string
    {
        return match ($group) {
            'dashboard' => 'لوحة التحكم',
            'products' => 'المنتجات',
            'inventory' => 'المخزون',
            'purchasing' => 'المشتريات',
            'sales' => 'المبيعات',
            'pos' => 'نقطة البيع',
            'tables' => 'الطاولات',
            'restaurant-tables' => 'طاولات المطعم',
            'table-sessions' => 'جلسات الطاولات',
            'kitchen' => 'المطبخ',
            'orders' => 'الطلبات',
            'invoices' => 'الفواتير',
            'manufacturing' => 'التصنيع',
            'accounting' => 'المحاسبة',
            'expenses' => 'المصروفات',
            'reports' => 'التقارير',
            'users' => 'المستخدمون',
            'roles' => 'الأدوار',
            'settings' => 'الإعدادات',
            'audit' => 'سجل التدقيق',
            default => $group,
        };
    }

    private function permissionLabel(string $permission): string
    {
        $parts = explode('.', $permission, 2);
        $action = $parts[1] ?? $permission;

        $actionLabel = match ($action) {
            'view' => 'عرض',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'view_financial' => 'عرض المالي',
            'view_operational' => 'عرض التشغيلي',
            'update_price' => 'تعديل السعر',
            'update_cost' => 'تعديل التكلفة',
            'manage_images' => 'إدارة الصور',
            'manage_categories' => 'إدارة التصنيفات',
            'manage_modifiers' => 'إدارة الإضافات',
            'adjust' => 'تسوية',
            'count' => 'جرد',
            'waste' => 'هدر',
            'transfer' => 'تحويل',
            'approve_adjustment' => 'اعتماد التسوية',
            'view_cost' => 'عرض التكلفة',
            'create_po' => 'إنشاء أمر شراء',
            'receive' => 'استلام',
            'create_invoice' => 'إنشاء فاتورة شراء',
            'pay_supplier' => 'دفع مورد',
            'manage_suppliers' => 'إدارة الموردين',
            'cancel' => 'إلغاء',
            'refund' => 'استرجاع',
            'discount' => 'خصم',
            'override_price' => 'تجاوز السعر',
            'print_invoice' => 'طباعة فاتورة',
            'access' => 'دخول',
            'open_session' => 'فتح جلسة',
            'close_session' => 'إغلاق جلسة',
            'cash_in' => 'إيداع نقدي',
            'cash_out' => 'سحب نقدي',
            'manage' => 'إدارة',
            'merge' => 'دمج',
            'split' => 'تقسيم',
            'update_status' => 'تحديث الحالة',
            'accept_delivery' => 'قبول توصيل',
            'reject_delivery' => 'رفض توصيل',
            'complete' => 'إتمام',
            'view_accounts' => 'عرض الحسابات',
            'create_entry' => 'إنشاء قيد',
            'reverse_entry' => 'عكس قيد',
            'close_period' => 'إغلاق فترة',
            'reopen_period' => 'إعادة فتح فترة',
            'view_profit' => 'عرض الأرباح',
            'view_balance_sheet' => 'عرض الميزانية',
            'sales' => 'مبيعات',
            'products' => 'منتجات',
            'inventory' => 'مخزون',
            'financial' => 'مالي',
            'customers' => 'عملاء',
            'employees' => 'موظفون',
            'assign_roles' => 'تعيين أدوار',
            'assign_permissions' => 'تعيين صلاحيات',
            'business' => 'بيانات المنشأة',
            'branches' => 'الفروع',
            'taxes' => 'الضرائب',
            'delivery' => 'التوصيل',
            'system' => 'النظام',
            default => str_replace('_', ' ', $action),
        };

        return $actionLabel;
    }
}
