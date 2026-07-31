<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\PosService;
use App\Services\RestaurantTableService;
use App\Services\StorefrontCustomerAuthService;
use App\Services\StorefrontCustomerBootstrapService;
use App\Services\TableScanSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableResolveController extends Controller
{
    public function __invoke(
        string $token,
        Request $request,
        RestaurantTableService $tables,
        PosService $pos,
        TableScanSessionService $scanSessions,
        StorefrontCustomerAuthService $customerAuth,
        StorefrontCustomerBootstrapService $customerBootstrap,
    ): JsonResponse {
        $table = $tables->findByToken($token);

        if ($table === null) {
            return response()->json([
                'message' => 'رمز الطاولة غير صالح.',
            ], 404);
        }

        $freshScan = $request->query('fresh_scan') === '1'
            || $request->query('table_scan') === 'fresh';
        $scanToken = (string) $request->query('scan_session', '');

        try {
            if ($freshScan) {
                $scanSession = $scanSessions->issueFresh($table);
            } elseif ($scanToken !== '') {
                $scanSession = $scanSessions->validateAndTouch($scanToken, $table);
            } else {
                return response()->json([
                    'message' => 'امسح رمز QR على الطاولة للبدء.',
                    'scan_session_required' => true,
                ], 403);
            }
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first(),
                'scan_session_expired' => true,
            ], 403);
        }

        $openOrder = $pos->findOpenTableOrder($table->number);
        $orderSessionToken = (string) $request->query('session', '');
        $isOccupied = $openOrder !== null;
        $canOrder = ! $isOccupied;
        $isOwner = false;

        if ($isOccupied && filled($openOrder->customer_session_token) && filled($orderSessionToken)) {
            $isOwner = hash_equals((string) $openOrder->customer_session_token, $orderSessionToken);
            $canOrder = $isOwner;
        }

        $existingCustomer = $customerAuth->resolve($request);
        $payload = [
            'id' => $table->getKey(),
            'number' => $table->number,
            'label' => $table->displayName(),
            'token' => $table->qr_token,
            'is_occupied' => $isOccupied,
            'can_order' => $canOrder,
            'is_owner' => $isOwner,
            'order_id' => $openOrder?->getKey(),
            'order_total' => $openOrder ? (float) $openOrder->total : null,
            'scan_session' => $scanSession->session_token,
            'scan_expires_at' => $scanSession->expires_at?->toIso8601String(),
            'scan_idle_minutes' => (int) config('store.table_scan_idle_minutes', 45),
        ];

        if ($freshScan || $existingCustomer === null) {
            $bootstrap = $customerBootstrap->bootstrapTableGuest($table, $existingCustomer);
            $payload['storefront_customer'] = $bootstrap['customer']->toStorefrontArray();

            return response()
                ->json($payload)
                ->withCookie($customerAuth->makeSessionCookie($bootstrap['token']));
        }

        $payload['storefront_customer'] = $existingCustomer->toStorefrontArray();

        return response()->json($payload);
    }
}
