<?php

namespace App\Http\Requests\Admin\Catalog\ModifierGroup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModifierGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'min_select' => 'nullable|integer|min:0',
            'max_select' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
        ];
    }
}
