<?php

namespace App\Http\Requests\Admin\Inventory\StockCount;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'اختر المستودع للجرد.',
        ];
    }
}
