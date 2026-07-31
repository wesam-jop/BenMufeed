<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class OrderThankYouController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $checkout = $request->session()->get('checkout');

        if (! is_array($checkout) || empty($checkout['order_id'])) {
            return redirect()->route('cart');
        }

        return $this->renderStorefront(
            'Store/OrderThankYou',
            [
                'checkout' => [
                    'order_id' => $checkout['order_id'],
                    'total' => (float) ($checkout['total'] ?? 0),
                    'service_type' => $checkout['service_type'] ?? 'takeaway',
                    'table_number' => $checkout['table_number'] ?? null,
                    'table_label' => $checkout['table_label'] ?? null,
                    'table_qr_token' => $checkout['table_qr_token'] ?? null,
                    'customer_session_token' => $checkout['customer_session_token'] ?? null,
                ],
            ],
            StorefrontSeo::forCart($this->siteSettings->current()),
        );
    }
}
