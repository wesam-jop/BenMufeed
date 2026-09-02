<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAddressController extends Controller
{
    public function index(): Response
    {
        $addresses = auth('customer')->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CustomerAddress $address) => [
                ...$address->toArray(),
                'formatted' => $address->formatted(),
            ]);

        return Inertia::render('customer/addresses/index', [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAddress($request);

        $customer = auth('customer')->user();

        if ($request->boolean('is_default') || ! $customer->addresses()->exists()) {
            $customer->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $customer->addresses()->create($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.address_saved'),
        ]);
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $validated = $this->validatedAddress($request);

        if ($request->boolean('is_default')) {
            auth('customer')->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $address->update($validated);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.address_updated'),
        ]);
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        $wasDefault = $address->is_default;
        $customer = auth('customer')->user();
        $address->delete();

        if ($wasDefault) {
            $customer->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.address_deleted'),
        ]);
    }

    public function setDefault(CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($address);

        auth('customer')->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.address_default'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:500'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['boolean'],
        ]);
    }

    private function authorizeAddress(CustomerAddress $address): void
    {
        if ($address->customer_id !== auth('customer')->id()) {
            abort(403);
        }
    }
}
