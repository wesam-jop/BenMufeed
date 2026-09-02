<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Catalog\ModifierGroup\StoreModifierGroupRequest;
use App\Http\Requests\Admin\Catalog\ModifierGroup\UpdateModifierGroupRequest;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModifierGroupController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ModifierGroup::class);

        $modifierGroups = ModifierGroup::query()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/modifier-groups/index', [
            'modifierGroup' => $modifierGroups,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ModifierGroup::class);

        return Inertia::render('admin/modifier-groups/create');
    }

    public function store(StoreModifierGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', ModifierGroup::class);

        ModifierGroup::create($request->validated());

        return to_route('admin.modifier-groups.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.created')]);
    }

    public function show(ModifierGroup $modifierGroup): Response
    {
        $this->authorize('view', $modifierGroup);

        return Inertia::render('admin/modifier-groups/show', [
            'modifierGroup' => $modifierGroup,
        ]);
    }

    public function edit(ModifierGroup $modifierGroup): Response
    {
        $this->authorize('update', $modifierGroup);

        return Inertia::render('admin/modifier-groups/edit', [
            'modifierGroup' => $modifierGroup,
        ]);
    }

    public function update(UpdateModifierGroupRequest $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorize('update', $modifierGroup);

        $modifierGroup->update($request->validated());

        return to_route('admin.modifier-groups.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.updated')]);
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorize('delete', $modifierGroup);

        $modifierGroup->delete();

        return to_route('admin.modifier-groups.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.deleted')]);
    }
}
