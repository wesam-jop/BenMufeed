<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPasswordController extends Controller
{
    public function __construct(
        protected \App\Services\Storefront\StorefrontService $storefront,
    ) {}

    public function show(): Response|RedirectResponse
    {
        $customer = auth('customer')->user();

        if ($customer->hasPassword()) {
            return redirect()->route('customer.home');
        }

        $branch = $this->storefront->defaultBranch();

        return Inertia::render('customer/password/setup', [
            'customer' => [
                'name' => $customer->name,
                'phone' => $customer->whatsapp ?: $customer->phone,
            ],
            'business' => $this->storefront->businessInfo($branch),
            'navCategories' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = auth('customer')->user();

        if ($customer->hasPassword()) {
            return redirect()->route('customer.home');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customer->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('customer.home')->with('toast', [
            'type' => 'success',
            'message' => __('auth.password_updated'),
        ]);
    }
}
