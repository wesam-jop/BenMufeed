<?php

namespace App\Http\Requests\Admin\Accounting\CashRegisterSession;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashRegisterSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
