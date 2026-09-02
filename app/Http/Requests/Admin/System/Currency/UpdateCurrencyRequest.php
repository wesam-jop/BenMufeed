<?php

namespace App\Http\Requests\Admin\System\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $currencyId = $this->route('currency')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:10',
                'alpha_num',
                Rule::unique('currencies', 'code')->ignore($currencyId),
            ],
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
}
