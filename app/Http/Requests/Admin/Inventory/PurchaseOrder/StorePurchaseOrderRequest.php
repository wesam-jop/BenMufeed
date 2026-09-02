<?php

namespace App\Http\Requests\Admin\Inventory\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_date' => 'nullable|date',
        ];
    }
}
