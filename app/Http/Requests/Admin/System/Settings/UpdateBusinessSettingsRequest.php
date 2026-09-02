<?php

namespace App\Http\Requests\Admin\System\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'opening_hours' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'instagram' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:100',
            'receipt_footer' => 'nullable|string|max:500',
        ];
    }
}
