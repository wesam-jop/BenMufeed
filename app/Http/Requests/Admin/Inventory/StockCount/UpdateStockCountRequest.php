<?php

namespace App\Http\Requests\Admin\Inventory\StockCount;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_count_items,id',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ];
    }
}
