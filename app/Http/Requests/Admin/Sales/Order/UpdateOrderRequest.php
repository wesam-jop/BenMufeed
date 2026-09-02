<?php

namespace App\Http\Requests\Admin\Sales\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => 'required|string',
            'status' => 'nullable|string',
            'table_session_id' => 'nullable|exists:table_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'delivery_address' => 'nullable|string',
        ];
    }
}
