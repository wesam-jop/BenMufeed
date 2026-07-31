<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use App\Services\TableScanSessionService;
use Illuminate\Http\RedirectResponse;

class TableEntryController extends Controller
{
    public function __invoke(string $token, RestaurantTableService $tables): RedirectResponse
    {
        $table = $tables->findByToken($token);

        if ($table === null) {
            $message = RestaurantTable::query()->where('is_active', true)->exists()
                ? 'رمز الطاولة غير صالح — امسح رمز QR محدّث من الطاولة أو اطلب من الموظف إعادة طباعته.'
                : 'لا توجد طاولات مفعّلة في النظام — أضف الطاولات من لوحة الإدارة ثم اطبع رمز QR جديد.';

            return redirect()
                ->route('products.index')
                ->withErrors(['table_token' => $message]);
        }

        return redirect()->route('products.index', [
            'table_token' => $table->qr_token,
            'table_scan' => 'fresh',
        ]);
    }
}
