<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\ProductCatalogQuery;
use App\Support\StorefrontPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __invoke(Request $request, ProductRepositoryInterface $products): JsonResponse
    {
        $catalog = ProductCatalogQuery::fromRequest($request);

        if (! $catalog->hasValidCategory()) {
            return response()->json([
                'products' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 24,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                    'links' => [],
                ],
                'filters' => $catalog->toFilterArray(),
                'meta' => ['total' => 0],
            ]);
        }

        $perPage = (int) config('store.products_per_page', 15);

        $paginator = $products->paginateAllPublished(
            $catalog->q,
            $catalog->sort,
            $catalog->categoryId(),
            $perPage,
            $catalog->page,
        );

        $pagination = $paginator->toArray();
        unset($pagination['data']);

        return response()->json([
            'products' => StorefrontPresenter::productCards($paginator->getCollection()),
            'pagination' => $pagination,
            'filters' => $catalog->toFilterArray(),
            'meta' => [
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
