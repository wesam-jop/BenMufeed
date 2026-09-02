<?php

namespace App\Http\Requests\Admin\Inventory\ProductionOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'planned_qty' => 'required|numeric|min:0.0001',
        ];
    }
}
