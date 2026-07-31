<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $ids = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return response()->json(['items' => []]);
        }

        $products = Product::query()
            ->published()
            ->whereIn('id', $ids)
            ->with('media')
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($ids as $id) {
            $product = $products->get($id);

            if ($product === null) {
                $items[] = [
                    'product_id' => $id,
                    'available' => false,
                ];

                continue;
            }

            $items[] = [
                'product_id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'price' => (float) $product->price,
                'compare_at_price' => $product->compare_at_price !== null
                    ? (float) $product->compare_at_price
                    : null,
                'image_url' => $product->primaryImageUrl(),
                'is_delivery_available' => (bool) $product->is_delivery_available,
                'available' => true,
            ];
        }

        return response()->json(['items' => $items]);
    }
}
