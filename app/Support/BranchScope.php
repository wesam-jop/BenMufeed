<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BranchScope
{
    public static function id(Request $request): ?int
    {
        $branchId = $request->session()->get('branch_id') ?? $request->user()?->branch_id;

        return $branchId ? (int) $branchId : null;
    }

    public static function apply(Builder $query, ?int $branchId, string $column = 'branch_id'): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->where($column, $branchId);
    }

    public static function applyThrough(Builder $query, ?int $branchId, string $relation, string $column = 'branch_id'): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas($relation, fn (Builder $q) => $q->where($column, $branchId));
    }

    public static function applyNullable(Builder $query, ?int $branchId, string $column = 'branch_id'): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where($column, $branchId)
            ->orWhereNull($column));
    }
}
