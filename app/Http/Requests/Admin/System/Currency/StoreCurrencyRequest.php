<?php

namespace App\Http\Requests\Admin\System\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('currencies', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:20'],
            'symbol_position' => ['required', 'in:before,after'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'rate_to_primary' => ['required', 'numeric', 'gt:0'],
            'show_on_receipt' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper((string) $this->input('code'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rate_to_primary.gt' => 'سعر الصرف يجب أن يكون أكبر من صفر.',
            'code.unique' => 'رمز العملة مستخدم مسبقاً.',
        ];
    }
}
