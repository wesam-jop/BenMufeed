<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Settings;
use App\Http\Requests\Admin\Cms\CmsPage\StoreCmsPageRequest;
use App\Http\Requests\Admin\Cms\CmsPage\UpdateCmsPageBuilderRequest;
use App\Models\CmsPage;
use App\Services\Cms\CmsPageBuilderService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends AdminController
{
    public function __construct(
        private CmsPageBuilderService $builder,
        private StorefrontService $storefront,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('view', Settings::class);
        $this->builder->ensureSystemPages();

        $pages = CmsPage::query()
            ->orderByDesc('is_system')
            ->orderBy('locale')
            ->orderBy('path')
            ->get()
            ->map(fn (CmsPage $page) => $this->transform($page));

        $groupOrder = ['site', 'commerce', 'errors', 'custom'];
        $grouped = collect($groupOrder)->mapWithKeys(function (string $group) use ($pages) {
            return [
                $group => [
                    'key' => $group,
                    'label' => CmsPage::groupLabel($group),
                    'pages' => $pages->where('group', $group)->values(),
                ],
            ];
        })->filter(fn (array $g) => $g['pages']->isNotEmpty())->values();

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
            'groups' => $grouped,
            'locales' => [
                ['value' => 'ar', 'label' => 'العربية'],
                ['value' => 'en', 'label' => 'English'],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('update', Settings::class);
        $this->builder->ensureSystemPages();

        $usedPaths = CmsPage::query()
            ->get(['path', 'locale'])
            ->groupBy('locale')
            ->map(fn ($rows) => $rows->pluck('path')->values()->all())
            ->all();

        return Inertia::render('admin/pages/create', [
            'blockedPaths' => CmsPage::blockedCustomPaths(),
            'usedPathsByLocale' => $usedPaths,
            'locales' => [
                ['value' => 'ar', 'label' => 'العربية'],
                ['value' => 'en', 'label' => 'English'],
            ],
        ]);
    }

    public function store(StoreCmsPageRequest $request): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $data = $request->validated();
        $page = CmsPage::query()->create([
            'title' => $data['title'],
            'path' => $data['path'],
            'locale' => $data['locale'],
            'is_system' => false,
            'is_published' => (bool) ($data['is_published'] ?? true),
            'theme' => CmsPage::defaultTheme(),
            'sections' => [],
        ]);

        return to_route('admin.pages.builder', $page)
            ->with('toast', [
                'type' => 'success',
                'message' => $data['locale'] === 'en'
                    ? 'Page created — add blocks and fill in the texts.'
                    : 'تم إنشاء الصفحة — أضف البلوكات وعبّئ النصوص.',
            ]);
    }

    public function builder(CmsPage $page): Response
    {
        $this->authorize('view', Settings::class);

        $branch = $this->storefront->defaultBranch();
        $branchId = $branch?->id;
        $limit = (int) ($this->storefront->settings()->featured_products_limit ?? 8);

        return Inertia::render('admin/pages/builder', [
            'page' => $this->transform($page, full: true),
            'catalog' => $this->builder->catalog(),
            'fontOptions' => [
                ['value' => 'Cairo', 'label' => 'Cairo'],
                ['value' => 'Tajawal', 'label' => 'Tajawal'],
                ['value' => 'Almarai', 'label' => 'Almarai'],
                ['value' => 'IBM Plex Sans Arabic', 'label' => 'IBM Plex Sans Arabic'],
            ],
            'business' => $this->storefront->businessInfo($branch),
            'categories' => $this->storefront->categoriesQuery($branchId)->get(),
            'featuredProducts' => $this->storefront->featuredProducts($branchId, $limit),
            'blockedPaths' => CmsPage::blockedCustomPaths(),
        ]);
    }

    public function updateBuilder(UpdateCmsPageBuilderRequest $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $data = $request->validated();
        $sections = array_values(array_map(
            fn ($section) => $this->builder->normalizeSection(is_array($section) ? $section : []),
            $data['sections'] ?? [],
        ));

        $payload = [
            'title' => $data['title'],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'theme' => [
                'primary' => $data['theme']['primary'],
                'secondary' => $data['theme']['secondary'],
                'font_family' => $data['theme']['font_family'],
                'base_font_size' => (int) $data['theme']['base_font_size'],
            ],
            'sections' => $sections,
        ];

        if ($page->is_system) {
            $payload['is_published'] = true;
        } else {
            $payload['is_published'] = (bool) $data['is_published'];
            if (isset($data['path'])) {
                $payload['path'] = $data['path'];
            }
        }

        $page->update($payload);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.page_saved'),
        ]);
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        if ($page->is_system || $page->isProtected()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.system_page_protected_delete'),
            ]);
        }

        try {
            $page->delete();
        } catch (\Throwable) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.page_delete_failed'),
            ]);
        }

        return to_route('admin.pages.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.page_deleted')]);
    }

    public function togglePublish(CmsPage $page): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        if ($page->is_system) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.system_page_protected_publish'),
            ]);
        }

        $page->update(['is_published' => ! $page->is_published]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => $page->is_published ? 'تم نشر الصفحة.' : 'تم إيقاف نشر الصفحة.',
        ]);
    }

    /** @return array<string, mixed> */
    private function transform(CmsPage $page, bool $full = false): array
    {
        $group = CmsPage::pageGroup($page->path);

        $base = [
            'id' => $page->id,
            'title' => $page->title,
            'path' => $page->path,
            'locale' => $page->locale ?: 'ar',
            'display_path' => $page->displayPath(),
            'is_system' => (bool) $page->is_system,
            'is_protected' => $page->isProtected(),
            'can_delete' => ! (bool) $page->is_system,
            'can_unpublish' => ! (bool) $page->is_system,
            'can_edit_path' => ! (bool) $page->is_system,
            'is_published' => (bool) $page->is_published,
            'public_url' => $page->publicUrl(),
            'group' => $group,
            'group_label' => CmsPage::groupLabel($group),
            'updated_at' => $page->updated_at?->toIso8601String(),
            'sections_count' => count($page->sections ?? []),
        ];

        if (! $full) {
            return $base;
        }

        return [
            ...$base,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'theme' => $page->resolvedTheme(),
            'sections' => array_values(array_map(
                fn ($section) => $this->builder->normalizeSection(is_array($section) ? $section : []),
                $page->sections ?? [],
            )),
        ];
    }
}
