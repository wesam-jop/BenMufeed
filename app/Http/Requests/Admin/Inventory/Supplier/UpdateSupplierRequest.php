<?php

namespace App\Http\Requests\Admin\Inventory\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_person' => $this->filled('contact_person') ? $this->contact_person : null,
            'phone' => $this->filled('phone') ? $this->phone : null,
            'email' => $this->filled('email') ? $this->email : null,
            'address' => $this->filled('address') ? $this->address : null,
            'tax_number' => $this->filled('tax_number') ? $this->tax_number : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'tax_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }
}
