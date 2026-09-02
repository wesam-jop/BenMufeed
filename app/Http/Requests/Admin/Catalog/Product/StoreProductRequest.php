<?php

namespace App\Http\Requests\Admin\Catalog\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:4096',
            'sale_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'recipe_items' => 'nullable|array',
            'recipe_items.*.ingredient_product_id' => 'required|exists:products,id',
            'recipe_items.*.quantity' => 'required|numeric|min:0.0001',
            'recipe_items.*.waste_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الطبق مطلوب.',
            'category_id.required' => 'القسم مطلوب.',
            'sale_price.required' => 'سعر البيع مطلوب.',
            'recipe_items.*.ingredient_product_id.required' => 'اختر مادة من المخزون.',
            'recipe_items.*.quantity.required' => 'الكمية مطلوبة.',
        ];
    }
}
