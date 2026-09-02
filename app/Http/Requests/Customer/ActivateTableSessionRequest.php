<?php

namespace App\Http\Requests\Customer;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ActivateTableSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('access_token') && ! $this->filled('access_code')) {
            $code = SiteSetting::resolveAccessCodeFromToken((string) $this->input('access_token'));

            if ($code !== null) {
                $this->merge(['access_code' => $code]);
            }
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'table_id' => ['required', 'integer', 'exists:restaurant_tables,id'],
            'access_token' => ['nullable', 'string', 'max:2000'],
            'access_code' => ['required', 'string', 'max:20'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('access_token') && ! $this->filled('access_code')) {
                $validator->errors()->add(
                    'access_token',
                    'الباركود غير صالح أو منتهي. صوّر الباركود الموحد الحالي مرة أخرى.',
                );
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'table_id.required' => 'اختر رقم الطاولة.',
            'table_id.exists' => 'الطاولة غير موجودة.',
            'access_code.required' => 'صوّر الباركود لاستخراج رمز التفعيل، أو أدخله يدوياً.',
        ];
    }
}
