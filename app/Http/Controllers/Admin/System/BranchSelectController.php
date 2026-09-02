<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchSelectController extends AdminController
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/settings/branch-select', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'currentBranchId' => $request->session()->get('branch_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $request->session()->put('branch_id', $validated['branch_id']);

        return redirect()->route('admin.dashboard');
    }
}
