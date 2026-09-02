<?php

namespace App\Http\Requests\Admin\Accounting\ChartOfAccount;

use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var ChartOfAccount $account */
        $account = $this->route('chart_of_account');

        if ($account->is_system) {
            return [
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ];
        }

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'code')->ignore($account->id),
            ],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::enum(AccountType::class)],
            'parent_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
                Rule::notIn([$account->id]),
            ],
            'is_active' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.unique' => 'رمز الحساب مستخدم مسبقاً.',
            'parent_id.not_in' => 'لا يمكن جعل الحساب أباً لنفسه.',
        ];
    }
}
