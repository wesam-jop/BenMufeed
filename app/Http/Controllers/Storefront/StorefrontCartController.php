<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\RendersCmsPages;
use App\Services\Storefront\CartService;
use App\Services\Cms\CmsPageBuilderService;
use App\Services\Storefront\GuestProfileService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontCartController extends Controller
{
    use RendersCmsPages;

    public function __construct(
        protected CartService $cart,
        protected StorefrontService $storefront,
        protected GuestProfileService $guestProfile,
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

    public function index(Request $request): Response|RedirectResponse
    {
        $cart = $this->cart->summary();
        $step = max(1, min(3, $request->integer('step', 1)));

        if ($cart['count'] === 0) {
            $step = 1;
        } elseif ($step > 2 && (empty($cart['location_lat']) || empty($cart['location_lng']))) {
            return redirect()->route('storefront.cart', ['step' => 2])
                ->with('toast', ['type' => 'warning', 'message' => __('toast.delivery_map_required')]);
        } elseif ($step > 1 && $cart['count'] === 0) {
            return redirect()->route('storefront.cart', ['step' => 1]);
        }

        return Inertia::render('storefront/cart/index', [
            ...$this->cmsPageProps($this->resolveSystemCmsPage('cart')),
            'cart' => $cart,
            'step' => $step,
            'guestProfile' => $this->guestProfile->get(),
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0.0001', 'max:99'],
        ]);

        $this->cart->add(
            (int) $validated['product_id'],
            (float) ($validated['quantity'] ?? 1),
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.cart_item_added'),
        ]);
    }

    public function updateItem(Request $request, int $productId): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0', 'max:99'],
        ]);

        $this->cart->update($productId, (float) $validated['quantity']);

        return back();
    }

    public function destroyItem(int $productId): RedirectResponse
    {
        $this->cart->remove($productId);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.cart_item_removed'),
        ]);
    }

    public function updateLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_lat' => ['required', 'numeric', 'between:-90,90'],
            'location_lng' => ['required', 'numeric', 'between:-180,180'],
            'location_address' => ['nullable', 'string', 'max:500'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->cart->updateLocation($validated);

        $nextStep = $request->integer('next_step', 3);

        return redirect()->route('storefront.cart', ['step' => $nextStep])
            ->with('toast', [
                'type' => 'success',
                'message' => __('toast.delivery_location_saved'),
            ]);
    }
}
