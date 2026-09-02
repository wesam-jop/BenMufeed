<?php

namespace App\Http\Requests\Admin\Sales\TableSession;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:restaurant_tables,id',
            'guest_count' => 'nullable|integer|min:1',
        ];
    }
}
