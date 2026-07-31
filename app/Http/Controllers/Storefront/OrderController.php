<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Services\StorefrontCustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        OrderService $orders,
        StorefrontCustomerAuthService $customerAuth,
    ): RedirectResponse {
        try {
            $validated = $request->validated();
            $storefrontCustomer = $customerAuth->resolve($request);

            $result = $orders->placeCustomerOrder(
                $validated['items'],
                [
                    'service_type' => $validated['service_type'],
                    'customer_name' => $storefrontCustomer?->isVerified()
                        ? $storefrontCustomer->name
                        : ($validated['customer_name'] ?? null),
                    'customer_phone' => $storefrontCustomer?->isVerified()
                        ? $storefrontCustomer->phone
                        : ($validated['customer_phone'] ?? null),
                    'customer_note' => $validated['customer_note'] ?? null,
                    'table_token' => $validated['table_token'] ?? null,
                    'table_session_token' => $validated['table_session_token'] ?? null,
                    'table_scan_session' => $validated['table_scan_session'] ?? null,
                    'delivery_latitude' => isset($validated['delivery_latitude']) ? (float) $validated['delivery_latitude'] : null,
                    'delivery_longitude' => isset($validated['delivery_longitude']) ? (float) $validated['delivery_longitude'] : null,
                    'delivery_location_note' => $validated['delivery_location_note'] ?? null,
                    'storefront_customer_id' => $storefrontCustomer?->getKey(),
                    'saved_address_id' => $validated['saved_address_id'] ?? null,
                    'save_address' => (bool) ($validated['save_address'] ?? false),
                    'address_label' => $validated['address_label'] ?? null,
                ],
            );

            $request->session()->put('checkout', [
                'order_id' => $result['order_id'],
                'total' => $result['total'],
                'service_type' => $result['service_type'],
                'table_number' => $result['table_number'],
                'table_label' => $result['table_label'] ?? null,
                'table_qr_token' => $result['table_qr_token'] ?? null,
                'customer_session_token' => $result['customer_session_token'] ?? null,
            ]);

            return redirect()->route('orders.thank-you');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }
}
