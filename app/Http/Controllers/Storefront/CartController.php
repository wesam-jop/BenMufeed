<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\RendersStorefrontPage;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Support\StorefrontSeo;
use Illuminate\Http\Request;
use Inertia\Response;

class CartController extends Controller
{
    use RendersStorefrontPage;

    public function __construct(
        protected SiteSettingRepositoryInterface $siteSettings,
    ) {}

    public function __invoke(Request $request): Response
    {
        return $this->renderStorefront('Store/Cart', [
            'tableToken' => $request->query('table_token'),
        ], StorefrontSeo::forCart($this->siteSettings->current()));
    }
}
