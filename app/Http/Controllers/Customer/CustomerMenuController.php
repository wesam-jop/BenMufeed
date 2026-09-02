<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerMenuController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = $request->query('branch_id');

        $categories = ProductCategory::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => fn ($q) => $q
                ->where('is_active', true)
                ->with(['variants', 'taxRate'])
                ->orderBy('name'),
            ])
            ->get();

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->with(['category', 'variants', 'taxRate'])
            ->orderBy('name')
            ->get();

        return Inertia::render('customer/menu', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
