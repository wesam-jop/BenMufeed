<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\StorefrontCustomerAddressService;
use App\Services\StorefrontCustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontCustomerAccountController extends Controller
{
    public function me(Request $request, StorefrontCustomerAuthService $auth): JsonResponse
    {
        $customer = $auth->resolve($request);

        if ($customer === null) {
            return response()->json(['customer' => null]);
        }

        return response()->json([
            'customer' => $customer->toStorefrontArray(),
        ]);
    }

    public function loginPage(Request $request, StorefrontCustomerAuthService $auth): Response|RedirectResponse
    {
        $customer = $auth->resolve($request);

        if ($customer !== null) {
            return redirect()->route('account');
        }

        return Inertia::render('Store/AccountLogin');
    }

    public function page(Request $request, StorefrontCustomerAuthService $auth, StorefrontCustomerAddressService $addresses): Response|RedirectResponse
    {
        $customer = $auth->resolve($request);

        if ($customer === null) {
            return redirect()->route('account.login');
        }

        $recentOrders = Order::query()
            ->where('storefront_customer_id', $customer->getKey())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'total', 'status', 'service_type', 'created_at']);

        return Inertia::render('Store/Account', [
            'customer' => $customer->toStorefrontArray(),
            'addresses' => $addresses->listFor($customer)->map->toStorefrontArray()->values(),
            'recentOrders' => $recentOrders->map(fn (Order $order) => [
                'id' => $order->getKey(),
                'total' => (float) $order->total,
                'status' => $order->status->value,
                'service_type' => $order->service_type->value,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function updateProfile(Request $request, StorefrontCustomerAuthService $auth): JsonResponse
    {
        $customer = $auth->resolve($request);

        if ($customer === null) {
            return response()->json(['message' => 'لا يوجد حساب.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $customer->forceFill(['name' => trim($validated['name'])])->save();

        return response()->json([
            'customer' => $customer->fresh('defaultTable')->toStorefrontArray(),
        ]);
    }
}
