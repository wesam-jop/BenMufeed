<?php

namespace App\Http\Requests\Admin\Inventory\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'from_warehouse_id.required' => 'اختر المستودع المصدر.',
            'to_warehouse_id.required' => 'اختر المستودع الوجهة.',
            'to_warehouse_id.different' => 'يجب أن يكون المستودع الوجهة مختلفاً عن المصدر.',
        ];
    }
}
