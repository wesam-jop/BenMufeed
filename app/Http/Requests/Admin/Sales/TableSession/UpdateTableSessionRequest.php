<?php

namespace App\Http\Requests\Admin\Sales\TableSession;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'guest_count' => 'nullable|integer|min:1',
        ];
    }
}
