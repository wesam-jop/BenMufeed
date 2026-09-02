<?php

namespace App\Http\Requests\Admin\Inventory\WastageRecord;

use App\Enums\WastageReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWastageRecordRequest extends FormRequest
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
            'reason' => ['required', Rule::enum(WastageReason::class)],
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
