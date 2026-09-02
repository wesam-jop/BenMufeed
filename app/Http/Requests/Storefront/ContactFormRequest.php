<?php

namespace App\Http\Requests\Storefront;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settings = SiteSetting::query()->first();

        return $settings === null || (bool) ($settings->contact_form_enabled ?? true);
    }

    protected function failedAuthorization(): void
    {
        throw ValidationException::withMessages([
            'message' => __('toast.contact_form_disabled'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.max' => 'الاسم طويل جداً.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'message.required' => 'الرسالة مطلوبة.',
            'message.max' => 'الرسالة طويلة جداً (الحد الأقصى 2000 حرف).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phone = trim((string) $this->input('phone', ''));
            $email = trim((string) $this->input('email', ''));

            if ($phone === '' && $email === '') {
                $validator->errors()->add('phone', 'أدخل رقم الهاتف أو البريد الإلكتروني على الأقل.');
                $validator->errors()->add('email', 'أدخل رقم الهاتف أو البريد الإلكتروني على الأقل.');
            }
        });
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        /** @var array<string, mixed> $data */
        return [
            ...$data,
            'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
            'email' => filled($data['email'] ?? null) ? $data['email'] : null,
            'is_read' => false,
        ];
    }
}
