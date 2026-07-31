<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\StorefrontCustomerAddressService;
use App\Services\StorefrontCustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontCustomerAddressController extends Controller
{
    public function index(Request $request, StorefrontCustomerAuthService $auth, StorefrontCustomerAddressService $addresses): JsonResponse
    {
        $customer = $auth->resolve($request);

        if ($customer === null || ! $customer->isVerified()) {
            return response()->json(['addresses' => []]);
        }

        return response()->json([
            'addresses' => $addresses->listFor($customer)->map->toStorefrontArray()->values(),
        ]);
    }

    public function store(
        Request $request,
        StorefrontCustomerAuthService $auth,
        StorefrontCustomerAddressService $addresses,
    ): JsonResponse {
        $customer = $auth->resolve($request);

        if ($customer === null || ! $customer->isVerified()) {
            return response()->json(['message' => 'يجب التحقق عبر واتساب أولاً لحفظ مواقع التوصيل.'], 401);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'location_note' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $address = $addresses->saveFromCheckout(
            $customer,
            $validated['label'],
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['location_note'],
            makeDefault: $customer->addresses()->count() === 0,
        );

        return response()->json([
            'address' => $address->toStorefrontArray(),
            'addresses' => $addresses->listFor($customer)->map->toStorefrontArray()->values(),
        ]);
    }

    public function destroy(
        int $address,
        Request $request,
        StorefrontCustomerAuthService $auth,
        StorefrontCustomerAddressService $addresses,
    ): JsonResponse {
        $customer = $auth->resolve($request);

        if ($customer === null || ! $customer->isVerified()) {
            return response()->json(['message' => 'غير مصرح.'], 401);
        }

        $addresses->delete($customer, $address);

        return response()->json([
            'addresses' => $addresses->listFor($customer)->map->toStorefrontArray()->values(),
        ]);
    }
}
