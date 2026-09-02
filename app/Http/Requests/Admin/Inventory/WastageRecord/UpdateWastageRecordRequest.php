<?php

namespace App\Http\Requests\Admin\Inventory\WastageRecord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWastageRecordRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ];
    }
}
