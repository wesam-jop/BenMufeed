<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerHomeController extends Controller
{
    public function index(): Response
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $ordersQuery = $customer->orders();

        return Inertia::render('customer/home', [
            'customer' => $customer->only(['id', 'name', 'phone', 'email', 'loyalty_points']),
            'stats' => [
                'orders_total' => (clone $ordersQuery)->count(),
                'orders_active' => (clone $ordersQuery)->whereNotIn('status', ['completed', 'cancelled', 'rejected'])->count(),
                'addresses_total' => $customer->addresses()->count(),
                'loyalty_points' => $customer->loyalty_points,
            ],
            'recentOrders' => $customer->orders()
                ->latest()
                ->limit(5)
                ->with(['items.product'])
                ->get(['id', 'order_number', 'status', 'total', 'created_at', 'delivery_address']),
            'defaultAddress' => $customer->addresses()
                ->where('is_default', true)
                ->first(),
        ]);
    }
}
