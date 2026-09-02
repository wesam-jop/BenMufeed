<?php

namespace App\Http\Requests\Admin\Accounting\Expense;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->filled('description') ? $this->description : null,
            'account_id' => $this->filled('account_id') ? $this->account_id : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'description' => 'nullable|string|max:1000',
            'expense_date' => 'required|date',
        ];
    }
}
