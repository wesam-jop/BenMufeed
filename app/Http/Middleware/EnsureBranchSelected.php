<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchSelected
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $sessionBranchId = $request->session()->get('branch_id');

        if ($sessionBranchId !== null && Branch::query()->whereKey($sessionBranchId)->where('is_active', true)->exists()) {
            return $next($request);
        }

        if ($user->branch_id !== null) {
            $request->session()->put('branch_id', $user->branch_id);

            return $next($request);
        }

        if ($user->is_super_admin) {
            $defaultBranchId = Branch::query()->where('is_active', true)->orderBy('id')->value('id');

            if ($defaultBranchId !== null) {
                $request->session()->put('branch_id', $defaultBranchId);

                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('toast.branch_required'),
            ], 403);
        }

        if ($request->routeIs('admin.branch.select', 'admin.branch.store')) {
            return $next($request);
        }

        return redirect()->route('admin.branch.select');
    }
}
