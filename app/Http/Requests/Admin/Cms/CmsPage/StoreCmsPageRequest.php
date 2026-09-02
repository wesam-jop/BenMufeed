<?php

namespace App\Http\Requests\Admin\Cms\CmsPage;

use App\Models\CmsPage;
use App\Support\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCmsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $locale = Locale::normalize((string) $this->input('locale', 'ar'));

        return [
            'title' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', Rule::in(Locale::AVAILABLE)],
            'path' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*$/',
                Rule::unique('cms_pages', 'path')->where(fn ($q) => $q->where('locale', $locale)),
                Rule::notIn(CmsPage::blockedCustomPaths()),
            ],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الصفحة مطلوب.',
            'locale.required' => 'لغة الصفحة مطلوبة.',
            'locale.in' => 'اللغة يجب أن تكون عربية أو إنجليزية.',
            'path.required' => 'مسار الصفحة مطلوب.',
            'path.regex' => 'المسار يجب أن يكون بالإنجليزية فقط (أحرف صغيرة، أرقام، شرطات).',
            'path.unique' => 'هذا المسار مستخدم مسبقاً لنفس اللغة.',
            'path.not_in' => 'هذا المسار محجوز للنظام ولا يمكن استخدامه.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('path')) {
            $this->merge(['path' => CmsPage::normalizePath((string) $this->input('path'))]);
        }

        $this->merge([
            'locale' => Locale::normalize((string) $this->input('locale', 'ar')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $path = (string) $this->input('path', '');
            if ($path === '') {
                return;
            }

            $first = explode('/', $path)[0] ?? $path;
            if (in_array($first, CmsPage::reservedPaths(), true)
                || in_array($path, CmsPage::blockedCustomPaths(), true)
            ) {
                $validator->errors()->add(
                    'path',
                    'لا يمكن إنشاء صفحة بمسار محجوز أو موجود مسبقاً في النظام.',
                );
            }
        });
    }
}
