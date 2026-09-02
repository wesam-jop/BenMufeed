<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\RendersCmsPages;
use App\Models\OrderOtpVerification;
use App\Services\Storefront\CartService;
use App\Services\Cms\CmsPageBuilderService;
use App\Services\Storefront\GuestCheckoutService;
use App\Services\Storefront\GuestProfileService;
use App\Services\Sales\OrderOtpService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontCheckoutController extends Controller
{
    use RendersCmsPages;

    public function __construct(
        protected CartService $cart,
        protected OrderOtpService $otp,
        protected GuestCheckoutService $checkout,
        protected GuestProfileService $guestProfile,
        protected StorefrontService $storefront,
        protected CmsPageBuilderService $cmsBuilder,
    ) {}

    protected function storefrontService(): StorefrontService
    {
        return $this->storefront;
    }

    protected function cmsBuilderService(): CmsPageBuilderService
    {
        return $this->cmsBuilder;
    }

    protected function sharedProps(): array
    {
        return $this->storefrontSharedProps();
    }

    public function show(Request $request): RedirectResponse
    {
        if ($this->hasPendingCheckout($request)) {
            return redirect()->route('storefront.checkout.verify');
        }

        return redirect()->route('storefront.cart', ['step' => 3]);
    }

    public function verify(Request $request): Response|RedirectResponse
    {
        $checkout = $request->session()->get('checkout');
        $cart = $this->cart->summary();

        if ($cart['count'] === 0) {
            return redirect()->route('storefront.cart');
        }

        if (empty($cart['location_lat']) || empty($cart['location_lng'])) {
            return redirect()->route('storefront.cart', ['step' => 2])
                ->with('toast', ['type' => 'warning', 'message' => __('toast.delivery_location_required')]);
        }

        if (! $this->hasPendingCheckout($request)) {
            return redirect()->route('storefront.cart', ['step' => 3])
                ->with('toast', ['type' => 'warning', 'message' => __('toast.otp_need_order')]);
        }

        $verification = OrderOtpVerification::query()->find($checkout['verification_id']);

        if ($verification === null || $verification->isVerified() || $verification->isExpired()) {
            $request->session()->forget('checkout');

            return redirect()->route('storefront.cart', ['step' => 3])
                ->with('toast', ['type' => 'warning', 'message' => __('toast.otp_expired')]);
        }

        return Inertia::render('storefront/cart/verify', [
            ...$this->sharedProps(),
            'cart' => $cart,
            'verificationId' => (int) $checkout['verification_id'],
            'whatsapp' => $checkout['whatsapp'],
            'customerName' => $checkout['name'] ?? $this->guestProfile->get()['name'] ?? null,
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $profile = $this->guestProfile->get();

        $validated = $request->validate([
            'name' => [$profile ? 'nullable' : 'required', 'string', 'max:255'],
            'whatsapp' => [$profile ? 'nullable' : 'required', 'string', 'max:20'],
        ]);

        $name = $validated['name'] ?? $profile['name'] ?? '';
        $whatsapp = $validated['whatsapp'] ?? $profile['whatsapp'] ?? '';

        if ($name === '' || $whatsapp === '') {
            return back()->withErrors([
                'whatsapp' => 'يرجى إدخال الاسم ورقم الواتساب.',
            ]);
        }

        $cart = $this->cart->summary();

        if (empty($cart['location_lat']) || empty($cart['location_lng'])) {
            return redirect()->route('storefront.cart', ['step' => 2])
                ->with('toast', ['type' => 'warning', 'message' => __('toast.delivery_location_required')]);
        }

        try {
            app(\App\Services\Storefront\OpeningHoursService::class)->ensureOpenForDelivery();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('toast', [
                'type' => 'error',
                'message' => $e->errors()['delivery_hours'][0] ?? __('toast.delivery_closed'),
            ]);
        }

        $result = $this->otp->send($name, $whatsapp);

        $request->session()->put('checkout', [
            'verification_id' => $result['verification_id'],
            'whatsapp' => $whatsapp,
            'name' => $name,
            'otp_sent' => true,
            'debug_code' => config('app.debug') ? ($result['debug_code'] ?? null) : null,
        ]);

        $toastMessage = __('toast.otp_sent');
        if (config('app.debug') && isset($result['debug_code'])) {
            $toastMessage .= ' — '.__('toast.debug_otp', ['code' => $result['debug_code']]);
        }

        return redirect()->route('storefront.checkout.verify')->with([
            'toast' => [
                'type' => 'success',
                'message' => $toastMessage,
            ],
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $profile = $this->guestProfile->get();
        $checkout = $request->session()->get('checkout', []);

        $validated = $request->validate([
            'verification_id' => ['required', 'integer', 'exists:order_otp_verifications,id'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $whatsapp = $validated['whatsapp']
            ?? $checkout['whatsapp']
            ?? $profile['whatsapp']
            ?? '';

        $verification = $this->otp->verify(
            (int) $validated['verification_id'],
            $whatsapp,
            $validated['otp'],
        );

        $order = $this->checkout->placeOrder($verification);

        $request->session()->forget('checkout');

        return redirect()->route('storefront.checkout.success', $order)
            ->with('toast', [
                'type' => 'success',
                'message' => __('toast.order_confirmed'),
            ]);
    }

    public function success(Request $request, \App\Models\Order $order): Response
    {
        $order->load(['items.product']);

        return Inertia::render('storefront/cart/success', [
            ...$this->cmsPageProps($this->resolveSystemCmsPage('checkout-success')),
            'order' => $order,
            'guestProfile' => $this->guestProfile->get(),
        ]);
    }

    protected function hasPendingCheckout(Request $request): bool
    {
        $checkout = $request->session()->get('checkout');

        return is_array($checkout)
            && ! empty($checkout['verification_id'])
            && ! empty($checkout['whatsapp']);
    }
}
