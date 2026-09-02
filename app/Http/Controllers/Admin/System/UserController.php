<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\System\User\StoreUserRequest;
use App\Http\Requests\Admin\System\User\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $baseQuery = $this->usersBaseQuery($request);

        $users = (clone $baseQuery)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $users->through(fn (User $user) => $this->transformSummary($user));

        $allUsers = (clone $baseQuery)->get();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'summary' => $this->buildSummary($allUsers),
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
                'role' => $request->input('role', ''),
            ],
            'filterOptions' => [
                'statuses' => $this->userStatusOptions(),
                'roles' => $this->roleFilterOptions(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('admin/users/create', $this->formOptions($request));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->prepareUserData($request);
        $roles = Arr::pull($data, 'roles', []);

        $user = User::create([
            ...$data,
            'branch_id' => $data['branch_id'] ?? $request->user()->branch_id,
            'is_active' => $request->boolean('is_active', true),
            'is_super_admin' => $request->user()->is_super_admin && $request->boolean('is_super_admin'),
        ]);

        $this->syncRolesIfAllowed($request, $user, $roles);

        return to_route('admin.users.show', $user)
            ->with('toast', ['type' => 'success', 'message' => __('toast.user_created')]);
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'branch:id,name'])
            ->loadCount(['orders', 'invoices', 'cashRegisterSessions']);

        return Inertia::render('admin/users/show', [
            'user' => $this->transformDetail($user),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return Inertia::render('admin/users/edit', [
            'user' => $this->transformDetail($user),
            ...$this->formOptions($request),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $this->prepareUserData($request);
        $roles = Arr::pull($data, 'roles', null);

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        if (($data['pin'] ?? null) === null) {
            unset($data['pin']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->user()->is_super_admin) {
            $data['is_super_admin'] = $request->boolean('is_super_admin');
        } else {
            unset($data['is_super_admin']);
        }

        $user->update($data);

        if ($roles !== null) {
            $this->syncRolesIfAllowed($request, $user, $roles);
        }

        return to_route('admin.users.show', $user)
            ->with('toast', ['type' => 'success', 'message' => __('toast.user_updated')]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()->id) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.cannot_delete_self'),
            ]);
        }

        if ($user->orders()->exists() || $user->invoices()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.user_has_orders'),
            ]);
        }

        $user->delete();

        return to_route('admin.users.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.user_deleted')]);
    }

    private function usersBaseQuery(Request $request): Builder
    {
        $query = $this->forBranch(
            User::query()->with(['roles', 'branch:id,name']),
            $request,
        )->withCount(['orders', 'invoices']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        match ($request->input('status', 'all')) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        if ($request->filled('role')) {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery
                ->where('name', $request->input('role')));
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        return [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'canAssignRoles' => $this->canAssignRoles($request),
            'canManageSuperAdmin' => (bool) $request->user()?->is_super_admin,
        ];
    }

    /** @return array<string, mixed> */
    private function prepareUserData(StoreUserRequest|UpdateUserRequest $request): array
    {
        $data = $request->validated();

        if (! $request->user()?->is_super_admin) {
            unset($data['is_super_admin']);
        }

        return $data;
    }

    private function canAssignRoles(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->is_super_admin || $user->can('users.assign_roles'));
    }

    /** @param  list<string>  $roles */
    private function syncRolesIfAllowed(Request $request, User $user, array $roles): void
    {
        if (! $this->canAssignRoles($request)) {
            return;
        }

        $user->syncRoles($roles);
    }

    /** @param  \Illuminate\Support\Collection<int, User>  $users */
    private function buildSummary($users): array
    {
        return [
            'users_count' => $users->count(),
            'active_count' => $users->where('is_active', true)->count(),
            'super_admin_count' => $users->where('is_super_admin', true)->count(),
            'with_sales_count' => $users->filter(
                fn (User $user) => ($user->invoices_count ?? 0) > 0,
            )->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'branch_id' => $user->branch_id,
            'branch_name' => $user->branch?->name,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'orders_count' => (int) ($user->orders_count ?? 0),
            'invoices_count' => (int) ($user->invoices_count ?? 0),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(User $user): array
    {
        return [
            ...$this->transformSummary($user),
            'cash_register_sessions_count' => (int) ($user->cash_register_sessions_count ?? 0),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function userStatusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'الكل'],
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'inactive', 'label' => 'غير نشط'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function roleFilterOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name) => [
                'value' => $name,
                'label' => $this->roleLabel($name),
            ])
            ->values()
            ->all();
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
}
