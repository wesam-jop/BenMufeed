<?php

namespace App\Http\Requests\Admin\Cms\CmsPage;

use App\Models\CmsPage;
use App\Services\Cms\CmsPageBuilderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCmsPageBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var CmsPage|null $page */
        $page = $this->route('cms_page') ?? $this->route('page');

        if ($page?->is_system) {
            return [
                'title' => ['required', 'string', 'max:255'],
                'is_published' => ['sometimes', 'boolean'],
                'seo_title' => ['nullable', 'string', 'max:255'],
                'seo_description' => ['nullable', 'string', 'max:500'],
                'theme' => ['required', 'array'],
                'theme.primary' => ['required', 'string', 'max:20'],
                'theme.secondary' => ['required', 'string', 'max:20'],
                'theme.font_family' => ['required', 'string', 'max:80'],
                'theme.base_font_size' => ['required', 'integer', 'min:12', 'max:22'],
                'sections' => ['required', 'array', 'max:40'],
                'sections.*.id' => ['required', 'string', 'max:64'],
                'sections.*.type' => ['required', 'string', Rule::in(CmsPageBuilderService::sectionTypes())],
                'sections.*.variant' => ['required', 'string', 'max:40'],
                'sections.*.enabled' => ['required', 'boolean'],
                'sections.*.props' => ['nullable', 'array'],
            ];
        }

        $locale = $page?->locale ?: 'ar';

        return [
            'title' => ['required', 'string', 'max:255'],
            'path' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*$/',
                Rule::unique('cms_pages', 'path')
                    ->where(fn ($q) => $q->where('locale', $locale))
                    ->ignore($page?->id),
                Rule::notIn(CmsPage::blockedCustomPaths()),
            ],
            'is_published' => ['required', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'theme' => ['required', 'array'],
            'theme.primary' => ['required', 'string', 'max:20'],
            'theme.secondary' => ['required', 'string', 'max:20'],
            'theme.font_family' => ['required', 'string', 'max:80'],
            'theme.base_font_size' => ['required', 'integer', 'min:12', 'max:22'],
            'sections' => ['required', 'array', 'max:40'],
            'sections.*.id' => ['required', 'string', 'max:64'],
            'sections.*.type' => ['required', 'string', Rule::in(CmsPageBuilderService::sectionTypes())],
            'sections.*.variant' => ['required', 'string', 'max:40'],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.props' => ['nullable', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'path.unique' => 'هذا المسار مستخدم مسبقاً لصفحة أخرى.',
            'path.not_in' => 'هذا المسار محجوز للنظام أو لمسار موجود في الموقع.',
            'path.regex' => 'المسار يجب أن يكون بالإنجليزية فقط (أحرف صغيرة، أرقام، شرطات).',
            'sections.required' => 'قائمة البلوكات مطلوبة (يمكن أن تكون فارغة ثم تضيف بلوكات).',
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var CmsPage|null $page */
        $page = $this->route('cms_page') ?? $this->route('page');

        if ($page?->is_system) {
            $this->merge([
                'path' => $page->path,
                'is_published' => true,
            ]);

            return;
        }

        if ($this->filled('path')) {
            $this->merge(['path' => CmsPage::normalizePath((string) $this->input('path'))]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var CmsPage|null $page */
            $page = $this->route('cms_page') ?? $this->route('page');
            if ($page?->is_system) {
                return;
            }

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
                    'لا يمكن استخدام مسار محجوز أو موجود مسبقاً في النظام.',
                );
            }
        });
    }
}
