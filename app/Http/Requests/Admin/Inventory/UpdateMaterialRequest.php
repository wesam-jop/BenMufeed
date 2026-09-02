<?php

namespace App\Http\Requests\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unit_id' => $this->unit_id ?: null,
            'sku' => $this->sku ?: null,
            'min_stock' => $this->min_stock !== null && $this->min_stock !== '' ? $this->min_stock : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unit_id' => 'nullable|exists:units,id',
            'cost_price' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
