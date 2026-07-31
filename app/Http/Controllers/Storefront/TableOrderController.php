<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TableScanSession;
use App\Services\OrderService;
use App\Services\PosService;
use App\Services\RestaurantTableService;
use App\Services\StorefrontCustomerAuthService;
use App\Services\StorefrontCustomerBootstrapService;
use App\Services\TableScanSessionService;
use App\Support\StorefrontCache;
use App\Support\StorefrontPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TableOrderController extends Controller
{
    protected function session(Request $request): TableScanSession
    {
        /** @var TableScanSession $session */
        $session = $request->attributes->get('tableScanSession');

        return $session;
    }

    /** صفحة اختيار رقم الطاولة (بعد مسح الباركود). */
    public function index(Request $request, RestaurantTableService $tables, PosService $pos): Response|RedirectResponse
    {
        $session = $this->session($request);

        if ($session->hasTable()) {
            return redirect()->route('table.menu');
        }

        $activeTables = $tables->activeTables();
        $occupied = $pos->occupiedTableNumbers();

        return Inertia::render('Table/Select', [
            'tables' => $activeTables->map(fn ($table) => [
                'number' => $table->number,
                'label' => $table->displayName(),
                'is_occupied' => in_array($table->number, $occupied, true),
            ])->values()->all(),
            'expiresAt' => $session->expires_at?->toIso8601String(),
        ]);
    }

    /** ربط الطاولة المختارة بجلسة المسح. */
    public function select(
        Request $request,
        RestaurantTableService $tables,
        PosService $pos,
        TableScanSessionService $scanSessions,
        StorefrontCustomerAuthService $customerAuth,
        StorefrontCustomerBootstrapService $customerBootstrap,
    ): RedirectResponse {
        $session = $this->session($request);

        $validated = $request->validate([
            'table_number' => ['required', 'integer', 'min:1'],
        ]);

        $table = $tables->activeTables()->firstWhere('number', (int) $validated['table_number']);

        if ($table === null) {
            throw ValidationException::withMessages([
                'table_number' => 'رقم الطاولة غير صالح.',
            ]);
        }

        if ($pos->findOpenTableOrder($table->number) !== null) {
            throw ValidationException::withMessages([
                'table_number' => 'الطاولة مشغولة حالياً — اختر طاولة أخرى أو اطلب من الكاشير.',
            ]);
        }

        $scanSessions->attachTable($session, $table);

        $existingCustomer = $customerAuth->resolve($request);
        $bootstrap = $customerBootstrap->bootstrapTableGuest($table, $existingCustomer);

        return redirect()
            ->route('table.menu')
            ->withCookie($customerAuth->makeSessionCookie($bootstrap['token']));
    }

    /** قائمة الطعام لواجهة الطاولة. */
    public function menu(Request $request): Response|RedirectResponse
    {
        $session = $this->session($request);

        if (! $session->hasTable()) {
            return redirect()->route('table.index');
        }

        $table = $session->restaurantTable;

        $products = Product::query()
            ->where('status', ProductStatus::Published->value)
            ->with(['category', 'activeVariants', 'activeAddons'])
            ->withCount(['activeVariants', 'activeAddons'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Table/Menu', [
            'table' => [
                'number' => $table->number,
                'label' => $table->displayName(),
            ],
            'products' => StorefrontPresenter::tableMenuProducts($products),
            'categories' => StorefrontCache::navigationCategories(),
            'expiresAt' => $session->expires_at?->toIso8601String(),
        ]);
    }

    /** صفحة السلة والتأكيد. */
    public function cart(Request $request): Response|RedirectResponse
    {
        $session = $this->session($request);

        if (! $session->hasTable()) {
            return redirect()->route('table.index');
        }

        $table = $session->restaurantTable;

        return Inertia::render('Table/Cart', [
            'table' => [
                'number' => $table->number,
                'label' => $table->displayName(),
            ],
            'expiresAt' => $session->expires_at?->toIso8601String(),
        ]);
    }

    /** تأكيد الطلب من الطاولة. */
    public function order(
        Request $request,
        OrderService $orders,
        StorefrontCustomerAuthService $customerAuth,
    ): RedirectResponse {
        $session = $this->session($request);

        if (! $session->hasTable()) {
            return redirect()->route('table.index');
        }

        $table = $session->restaurantTable;

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('products', 'id')
                    ->where('status', ProductStatus::Published->value),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.addons' => ['nullable', 'array', 'max:20'],
            'items.*.addons.*.product_addon_id' => ['required', 'integer', 'exists:product_addons,id'],
            'items.*.addons.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'table_session_token' => ['nullable', 'string', 'max:64'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'items.required' => 'طلبك فارغ — أضف أطباقاً من القائمة.',
            'items.min' => 'طلبك فارغ — أضف أطباقاً من القائمة.',
        ]);

        try {
            $storefrontCustomer = $customerAuth->resolve($request);

            $result = $orders->placeCustomerOrder(
                $validated['items'],
                [
                    'service_type' => \App\Enums\PosServiceType::Table->value,
                    'customer_note' => $validated['customer_note'] ?? null,
                    'table_token' => $table->qr_token,
                    'table_session_token' => $validated['table_session_token'] ?? null,
                    'table_scan_session' => $session->session_token,
                    'storefront_customer_id' => $storefrontCustomer?->getKey(),
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

            $cookieName = (string) config('store.table_order_cookie', env('STORE_TABLE_ORDER_COOKIE', 'bm_tos'));
            $lifetime = max(60, (int) config('store.table_scan_max_hours', 4) * 60);

            return redirect()
                ->route('orders.thank-you')
                ->withCookie(cookie(
                    $cookieName,
                    (string) ($result['customer_session_token'] ?? ''),
                    $lifetime,
                    null,
                    null,
                    false,
                    false,
                ));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /** صفحة "امسح الباركود" — تظهر عند غياب/انتهاء جلسة المسح. */
    public function scanRequired(): Response
    {
        return Inertia::render('Table/ScanRequired');
    }
}
