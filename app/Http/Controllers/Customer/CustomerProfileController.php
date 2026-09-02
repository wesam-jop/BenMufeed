<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CustomerProfileController extends Controller
{
    public function show(): Response
    {
        $customer = auth('customer')->user();

        return Inertia::render('customer/profile', [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'has_password' => $customer->hasPassword(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = auth('customer')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $customer->update($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.profile_updated'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = auth('customer')->user();

        $rules = [
            'password' => ['required', 'confirmed', Password::min(6)],
        ];

        if ($customer->hasPassword()) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validated = $request->validate($rules);

        if ($customer->hasPassword() && ! Hash::check($validated['current_password'], $customer->password)) {
            return back()->withErrors([
                'current_password' => 'كلمة المرور الحالية غير صحيحة.',
            ]);
        }

        $customer->update([
            'password' => $validated['password'],
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $customer->wasChanged('password') ? 'تم تحديث كلمة المرور' : 'تم تفعيل كلمة المرور',
        ]);
    }
}
