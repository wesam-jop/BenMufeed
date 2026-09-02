<?php

namespace App\Http\Requests\Admin\Sales\RestaurantTable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
            'zone' => 'nullable|string|max:100',
            'status' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
