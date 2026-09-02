<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    protected function branchId(Request $request): ?int
    {
        return BranchScope::id($request);
    }

    protected function forBranch(Builder $query, Request $request, string $column = 'branch_id'): Builder
    {
        return BranchScope::apply($query, $this->branchId($request), $column);
    }

    protected function forBranchThrough(Builder $query, Request $request, string $relation, string $column = 'branch_id'): Builder
    {
        return BranchScope::applyThrough($query, $this->branchId($request), $relation, $column);
    }

    protected function forBranchNullable(Builder $query, Request $request, string $column = 'branch_id'): Builder
    {
        return BranchScope::applyNullable($query, $this->branchId($request), $column);
    }

    protected function authorizeResourceAction(string $ability, Model|string $model): void
    {
        $this->authorize($ability, $model);
    }
}
