<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerFavoriteController extends Controller
{
    public function index(): Response
    {
        $favorites = auth('customer')->user()
            ->favorites()
            ->with(['category', 'variants'])
            ->get();

        return Inertia::render('customer/favorites/index', [
            'favorites' => $favorites,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $customer = auth('customer')->user();

        if (! $customer->favorites()->where('product_id', $validated['product_id'])->exists()) {
            $customer->favorites()->attach($validated['product_id']);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.favorite_added'),
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        auth('customer')->user()->favorites()->detach($product->id);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.favorite_removed'),
        ]);
    }
}
