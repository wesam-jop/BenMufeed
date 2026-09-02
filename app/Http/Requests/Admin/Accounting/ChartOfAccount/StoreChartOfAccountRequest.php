<?php

namespace App\Http\Requests\Admin\Accounting\ChartOfAccount;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:chart_of_accounts,code',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::enum(AccountType::class)],
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.unique' => 'رمز الحساب مستخدم مسبقاً.',
            'code.required' => 'رمز الحساب مطلوب.',
            'name.required' => 'اسم الحساب مطلوب.',
            'type.required' => 'نوع الحساب مطلوب.',
        ];
    }
}
