<?php

namespace App\Http\Requests\Admin\Catalog\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
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
            'variant_id' => 'nullable|exists:product_variants,id',
            'name' => 'required|string|max:255',
            'yield_quantity' => 'required|numeric|min:0',
            'version' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
