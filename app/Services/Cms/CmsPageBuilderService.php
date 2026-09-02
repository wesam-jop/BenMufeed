<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsPageBuilderService
{
    /** @return list<string> */
    public static function systemPaths(): array
    {
        return CmsPage::systemPaths();
    }

    /** @return list<string> */
    public static function sectionTypes(): array
    {
        return [
            'hero',
            'trust',
            'categories',
            'featured_products',
            'rich_text',
            'cta',
            'contact_channels',
            'map',
            'custom_html',
        ];
    }

    /** @return list<string> */
    public static function variantsFor(string $type): array
    {
        return ['design-1', 'design-2', 'design-3'];
    }

    /**
     * @return list<array{type: string, label: string, description: string, icon: string, variants: list<array{id: string, label: string, hint: string}>}>
     */
    public function catalog(): array
    {
        $variantMeta = [
            'hero' => [
                'design-1' => ['label' => 'بانر كامل', 'hint' => 'خلفية داكنة بعرض الصفحة'],
                'design-2' => ['label' => 'مقسوم', 'hint' => 'نص + صورة جنباً إلى جنب'],
                'design-3' => ['label' => 'مدمج', 'hint' => 'بطاقة ملونة مع زر'],
            ],
            'trust' => [
                'design-1' => ['label' => 'أيقونات', 'hint' => 'شريط أيقونات أفقي'],
                'design-2' => ['label' => 'بطاقات', 'hint' => 'بطاقات بعنوان ووصف'],
                'design-3' => ['label' => 'صف بسيط', 'hint' => 'نصوص متتالية'],
            ],
            'categories' => [
                'design-1' => ['label' => 'شبكة 3', 'hint' => 'ثلاث أعمدة'],
                'design-2' => ['label' => 'شبكة 4', 'hint' => 'أربع أعمدة'],
                'design-3' => ['label' => 'سلايدر', 'hint' => 'تمرير أفقي'],
            ],
            'featured_products' => [
                'design-1' => ['label' => 'شبكة', 'hint' => 'بطاقات منتجات'],
                'design-2' => ['label' => 'قائمة', 'hint' => 'صفوف بسيطة'],
                'design-3' => ['label' => 'مميز كبير', 'hint' => 'منتج بارز + شبكة'],
            ],
            'rich_text' => [
                'design-1' => ['label' => 'عمود واحد', 'hint' => 'نص مركزي'],
                'design-2' => ['label' => 'عمودين', 'hint' => 'عنوان + محتوى'],
                'design-3' => ['label' => 'مقتبس', 'hint' => 'اقتباس بارز'],
            ],
            'cta' => [
                'design-1' => ['label' => 'شريط', 'hint' => 'صف أفقي'],
                'design-2' => ['label' => 'بطاقة', 'hint' => 'بطاقة مركزية'],
                'design-3' => ['label' => 'خلفية ملونة', 'hint' => 'قسم ملون كامل'],
            ],
            'contact_channels' => [
                'design-1' => ['label' => 'بطاقات', 'hint' => 'بطاقات تواصل'],
                'design-2' => ['label' => 'قائمة', 'hint' => 'روابط متتالية'],
                'design-3' => ['label' => 'شبكة', 'hint' => 'ثلاث أعمدة'],
            ],
            'map' => [
                'design-1' => ['label' => 'خريطة كاملة', 'hint' => 'خريطة بعرض القسم'],
                'design-2' => ['label' => 'مع نص', 'hint' => 'نص جانبي + خريطة'],
                'design-3' => ['label' => 'خريطة كاملة', 'hint' => 'نفس التصميم الكامل'],
            ],
            'custom_html' => [
                'design-1' => ['label' => 'نص وسط', 'hint' => 'فقرة بسيطة'],
                'design-2' => ['label' => 'نص وسط', 'hint' => 'فقرة بسيطة'],
                'design-3' => ['label' => 'نص وسط', 'hint' => 'فقرة بسيطة'],
            ],
        ];

        $items = [
            ['type' => 'hero', 'label' => 'بانر رئيسي', 'description' => 'عنوان وصورة وأزرار — بداية الصفحة', 'icon' => 'sparkles'],
            ['type' => 'trust', 'label' => 'شريط الثقة', 'description' => 'نقاط ثقة أو مميزات قصيرة', 'icon' => 'shield'],
            ['type' => 'categories', 'label' => 'الأقسام', 'description' => 'عرض أقسام المنتجات من المتجر', 'icon' => 'layout'],
            ['type' => 'featured_products', 'label' => 'منتجات مميزة', 'description' => 'شبكة أو قائمة منتجات', 'icon' => 'package'],
            ['type' => 'rich_text', 'label' => 'نص ومحتوى', 'description' => 'عنوان ونصوص طويلة', 'icon' => 'text'],
            ['type' => 'cta', 'label' => 'دعوة للإجراء', 'description' => 'زر تحفيزي مع عنوان', 'icon' => 'mouse'],
            ['type' => 'contact_channels', 'label' => 'قنوات التواصل', 'description' => 'واتساب وفيسبوك وانستغرام', 'icon' => 'message'],
            ['type' => 'map', 'label' => 'الخريطة', 'description' => 'موقع المقهى على الخريطة', 'icon' => 'map'],
            ['type' => 'custom_html', 'label' => 'نص حر', 'description' => 'ملاحظة أو فقرة بسيطة', 'icon' => 'pen'],
        ];

        return array_map(function (array $item) use ($variantMeta) {
            $meta = $variantMeta[$item['type']] ?? [];

            return [
                ...$item,
                'variants' => array_map(
                    function (string $id) use ($meta) {
                        $row = $meta[$id] ?? ['label' => $id, 'hint' => ''];

                        return [
                            'id' => $id,
                            'label' => $row['label'],
                            'hint' => $row['hint'],
                        ];
                    },
                    self::variantsFor($item['type']),
                ),
            ];
        }, $items);
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array{id: string, type: string, variant: string, enabled: bool, props: array<string, mixed>}
     */
    public function makeSection(string $type, string $variant = 'design-1', array $props = []): array
    {
        if (! in_array($type, self::sectionTypes(), true)) {
            $type = 'custom_html';
        }
        if (! in_array($variant, self::variantsFor($type), true)) {
            $variant = 'design-1';
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'variant' => $variant,
            'enabled' => true,
            'props' => $props,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{id: string, type: string, variant: string, enabled: bool, props: array<string, mixed>}
     */
    public function normalizeSection(array $section): array
    {
        $type = (string) ($section['type'] ?? 'custom_html');
        if (! in_array($type, self::sectionTypes(), true)) {
            $type = 'custom_html';
        }

        $variant = (string) ($section['variant'] ?? 'design-1');
        if (! in_array($variant, self::variantsFor($type), true)) {
            $variant = 'design-1';
        }

        return [
            'id' => filled($section['id'] ?? null) ? (string) $section['id'] : (string) Str::uuid(),
            'type' => $type,
            'variant' => $variant,
            'enabled' => (bool) ($section['enabled'] ?? true),
            'props' => is_array($section['props'] ?? null) ? $section['props'] : [],
        ];
    }

    protected function mediaUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /** @return list<array{title: string, description?: string}> */
    protected function aboutFeatureItems(SiteSetting $site): array
    {
        $features = collect($site->about_features ?: [])
            ->map(fn ($f) => [
                'title' => (string) ($f['title'] ?? ''),
                'description' => (string) ($f['description'] ?? ''),
            ])
            ->filter(fn ($f) => filled($f['title']))
            ->values()
            ->all();

        if ($features !== []) {
            return $features;
        }

        return [
            ['title' => 'قهوة مختصة', 'description' => 'حبوب مختارة وتحميص بعناية لتجربة مذاق استثنائية'],
            ['title' => 'مكونات طازجة', 'description' => 'نختار أجود المكونات يومياً لضمان الجودة'],
            ['title' => 'خدمة بروح', 'description' => 'نهتم بكل تفصيلة لراحتك ورضاك'],
            ['title' => 'أجواء مميزة', 'description' => 'مكان يجمع الدفء والأناقة في كل زيارة'],
        ];
    }

    /** @return list<array{id: string, type: string, variant: string, enabled: bool, props: array<string, mixed>}> */
    public function defaultSectionsFor(string $path, ?SiteSetting $site = null, string $locale = 'ar'): array
    {
        $site ??= SiteSetting::current();
        $locale = \App\Support\Locale::normalize($locale);

        if ($locale === 'en') {
            return $this->defaultEnglishSectionsFor($path, $site);
        }

        return $this->defaultArabicSectionsFor($path, $site);
    }

    /** @return list<array{id: string, type: string, variant: string, enabled: bool, props: array<string, mixed>}> */
    protected function defaultArabicSectionsFor(string $path, SiteSetting $site): array
    {
        $defaults = SiteSetting::defaults();
        $name = $site->business_name ?: config('app.name');

        return match ($path) {
            'home' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => $site->hero_title ?: $name,
                    'subtitle' => $site->hero_subtitle ?: ($site->tagline ?: $defaults['hero_subtitle'] ?? ''),
                    'image' => $this->mediaUrl($site->hero_image),
                    'show_cta' => true,
                    'cta_label' => 'تصفّح القائمة',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'اطلب الآن',
                    'secondary_cta_href' => '/cart',
                    'show_hours' => true,
                    'hours_text' => $site->opening_hours,
                ]),
                $this->makeSection('trust', 'design-1', [
                    'items' => $site->trust_items ?: $defaults['trust_items'],
                ]),
                $this->makeSection('categories', 'design-2', [
                    'title' => $site->categories_section_title ?: $defaults['categories_section_title'],
                    'subtitle' => $site->categories_section_subtitle ?: $defaults['categories_section_subtitle'],
                ]),
                $this->makeSection('featured_products', 'design-1', [
                    'title' => $site->featured_section_title ?: $defaults['featured_section_title'],
                    'subtitle' => $site->featured_section_subtitle ?: $defaults['featured_section_subtitle'],
                    'limit' => (int) ($site->featured_products_limit ?: 8),
                ]),
                $this->makeSection('rich_text', 'design-2', [
                    'eyebrow' => $site->about_eyebrow ?: $defaults['about_eyebrow'],
                    'title' => $site->about_title ?: "عن {$name}",
                    'content' => $site->about_content ?: ($site->tagline ?: ''),
                ]),
                $this->makeSection('cta', 'design-3', [
                    'title' => $site->contact_preview_title ?: $defaults['contact_preview_title'],
                    'subtitle' => $site->contact_preview_subtitle ?: $defaults['contact_preview_subtitle'],
                    'cta_label' => 'تواصل معنا',
                    'cta_href' => '/contact',
                ]),
                $this->makeSection('map', 'design-1', [
                    'title' => $site->map_title ?: $defaults['map_title'],
                    'subtitle' => $site->map_subtitle ?: $defaults['map_subtitle'],
                ]),
            ],
            'about' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => $site->about_title ?: "عن {$name}",
                    'subtitle' => $site->tagline ?: ($site->about_eyebrow ?: $defaults['about_eyebrow']),
                    'image' => $this->mediaUrl($site->about_image ?: $site->hero_image),
                    'eyebrow' => $site->about_eyebrow ?: $defaults['about_eyebrow'],
                    'show_cta' => true,
                    'cta_label' => 'تصفّح القائمة',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'تواصل معنا',
                    'secondary_cta_href' => '/contact',
                ]),
                $this->makeSection('rich_text', 'design-2', [
                    'eyebrow' => 'من نحن',
                    'title' => $name,
                    'content' => trim((string) ($site->about_content ?: $site->tagline))
                        ?: "{$name} — مكانك المفضل للقهوة والأجواء الراقية.",
                ]),
                $this->makeSection('trust', 'design-2', [
                    'title' => $site->about_why_title ?: $defaults['about_why_title'],
                    'subtitle' => $site->about_why_subtitle ?: $defaults['about_why_subtitle'],
                    'items' => $this->aboutFeatureItems($site),
                ]),
                $this->makeSection('custom_html', 'design-1', [
                    'text' => filled($site->opening_hours)
                        ? "أوقات العمل: {$site->opening_hours}"
                        : '',
                ]),
                $this->makeSection('cta', 'design-2', [
                    'title' => $site->about_cta_title ?: $defaults['about_cta_title'],
                    'subtitle' => $site->about_cta_subtitle ?: $defaults['about_cta_subtitle'],
                    'cta_label' => 'المنتجات',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('map', 'design-2', [
                    'title' => $site->map_title ?: $defaults['map_title'],
                    'subtitle' => $site->map_subtitle ?: $defaults['map_subtitle'],
                ]),
            ],
            'contact' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => $site->contact_title ?: 'تواصل معنا',
                    'subtitle' => $site->contact_content
                        ?: 'تواصل معنا مباشرة عبر واتساب أو تابعنا على فيسبوك وانستغرام.',
                    'eyebrow' => $site->contact_eyebrow ?: $defaults['contact_eyebrow'],
                    'show_cta' => false,
                ]),
                $this->makeSection('contact_channels', 'design-1', [
                    'title' => 'قنوات التواصل',
                    'subtitle' => 'اختر الطريقة الأنسب لك',
                ]),
                $this->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'معلومات الوصول',
                    'title' => 'العنوان وأوقات العمل',
                    'content' => collect([
                        filled($site->address) ? "العنوان: {$site->address}" : null,
                        filled($site->phone) ? "الهاتف: {$site->phone}" : null,
                        filled($site->opening_hours) ? "أوقات العمل: {$site->opening_hours}" : null,
                        filled($site->email) ? "البريد: {$site->email}" : null,
                    ])->filter()->implode("\n"),
                ]),
                $this->makeSection('map', 'design-1', [
                    'title' => $site->map_title ?: $defaults['map_title'],
                    'subtitle' => $site->map_subtitle ?: $defaults['map_subtitle'],
                ]),
                $this->makeSection('cta', 'design-1', [
                    'title' => 'جاهز للطلب؟',
                    'subtitle' => 'تصفّح قائمتنا واطلب بسهولة',
                    'cta_label' => 'عرض المنتجات',
                    'cta_href' => '/products',
                ]),
            ],
            'categories' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => $site->categories_page_title ?: $defaults['categories_page_title'],
                    'subtitle' => $site->categories_page_subtitle ?: $defaults['categories_page_subtitle'],
                    'image' => $this->mediaUrl($site->categories_page_image ?: $site->hero_image),
                    'eyebrow' => $name,
                    'show_cta' => true,
                    'cta_label' => 'جميع المنتجات',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('categories', 'design-1', [
                    'title' => 'استكشف الأقسام',
                    'subtitle' => 'اختر القسم الذي يناسبك',
                ]),
                $this->makeSection('cta', 'design-2', [
                    'title' => 'لم تجد ما تبحث عنه؟',
                    'subtitle' => 'تصفّح كل المنتجات دفعة واحدة',
                    'cta_label' => 'عرض المنتجات',
                    'cta_href' => '/products',
                ]),
            ],
            'products' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => $site->products_page_title ?: $defaults['products_page_title'],
                    'subtitle' => $site->products_page_subtitle ?: $defaults['products_page_subtitle'],
                    'image' => $this->mediaUrl($site->products_page_image ?: $site->hero_image),
                    'eyebrow' => $name,
                    'show_cta' => true,
                    'cta_label' => 'الأقسام',
                    'cta_href' => '/categories',
                ]),
                $this->makeSection('trust', 'design-3', [
                    'items' => $site->trust_items ?: $defaults['trust_items'],
                ]),
                $this->makeSection('cta', 'design-1', [
                    'title' => 'تصفّح حسب القسم',
                    'subtitle' => 'سهّل اختيارك عبر أقسام القائمة',
                    'cta_label' => 'عرض الأقسام',
                    'cta_href' => '/categories',
                ]),
            ],
            'cart' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'سلة الطلبات',
                    'subtitle' => 'راجع طلبك وأكمل بيانات التوصيل بخطوات بسيطة',
                    'eyebrow' => 'الطلب',
                    'show_cta' => true,
                    'cta_label' => 'متابعة التسوق',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('trust', 'design-1', [
                    'items' => [
                        ['title' => 'طلب سريع'],
                        ['title' => 'توصيل موثوق'],
                        ['title' => 'دفع عند الاستلام'],
                        ['title' => 'دعم مباشر'],
                    ],
                ]),
                $this->makeSection('custom_html', 'design-1', [
                    'text' => 'أكمل خطوات السلة بالأسفل: المنتجات ← الموقع ← التأكيد.',
                ]),
            ],
            'checkout-success' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'تم تأكيد طلبك',
                    'subtitle' => 'شكراً لثقتك بنا — سنبدأ بتجهيز طلبك فوراً',
                    'eyebrow' => 'نجاح الطلب',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تصفّح المنتجات',
                    'secondary_cta_href' => '/products',
                ]),
                $this->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'ماذا بعد؟',
                    'title' => 'نتواصل معك قريباً',
                    'content' => "سيتم التواصل معك لتأكيد التوصيل.\nيمكنك متابعة العروض والمنتجات الجديدة من القائمة.",
                ]),
                $this->makeSection('cta', 'design-2', [
                    'title' => 'جرّب شيئاً جديداً؟',
                    'subtitle' => 'اكتشف تشكيلتنا المميزة',
                    'cta_label' => 'المنتجات',
                    'cta_href' => '/products',
                ]),
            ],
            'errors/404' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'الصفحة غير موجودة',
                    'subtitle' => 'عذراً، الرابط الذي فتحته غير متاح أو تم نقله.',
                    'eyebrow' => 'خطأ 404',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تصفّح المنتجات',
                    'secondary_cta_href' => '/products',
                ]),
                $this->makeSection('cta', 'design-1', [
                    'title' => 'هل تبحث عن شيء محدد؟',
                    'subtitle' => 'جرّب الأقسام أو تواصل معنا',
                    'cta_label' => 'الأقسام',
                    'cta_href' => '/categories',
                ]),
            ],
            'errors/403' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'غير مصرّح بالوصول',
                    'subtitle' => 'ليس لديك صلاحية لعرض هذه الصفحة.',
                    'eyebrow' => 'خطأ 403',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                ]),
                $this->makeSection('contact_channels', 'design-2', [
                    'title' => 'تحتاج مساعدة؟',
                    'subtitle' => 'تواصل معنا وسنساعدك',
                ]),
            ],
            'errors/500' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'حدث خطأ غير متوقع',
                    'subtitle' => 'نعمل على إصلاح المشكلة. حاول مجدداً بعد لحظات.',
                    'eyebrow' => 'خطأ 500',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تواصل معنا',
                    'secondary_cta_href' => '/contact',
                ]),
                $this->makeSection('custom_html', 'design-1', [
                    'text' => 'إن استمرّت المشكلة، راسلنا عبر واتساب أو صفحة التواصل.',
                ]),
            ],
            default => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'عنوان الصفحة',
                    'subtitle' => 'وصف قصير للصفحة',
                    'show_cta' => true,
                    'cta_label' => 'تصفّح القائمة',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('rich_text', 'design-1', [
                    'title' => 'محتوى الصفحة',
                    'content' => 'أضف محتوى الصفحة من المحرّر.',
                ]),
                $this->makeSection('cta', 'design-2', [
                    'title' => 'ابدأ الطلب',
                    'subtitle' => 'القائمة جاهزة لك',
                    'cta_label' => 'المنتجات',
                    'cta_href' => '/products',
                ]),
            ],
        };
    }

    /** @return list<array{id: string, type: string, variant: string, enabled: bool, props: array<string, mixed>}> */
    protected function defaultEnglishSectionsFor(string $path, SiteSetting $site): array
    {
        $name = $site->business_name ?: config('app.name');

        return match ($path) {
            'home' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'Welcome to '.$name,
                    'subtitle' => $site->tagline ?: 'Fresh coffee, warm atmosphere, and memorable moments.',
                    'show_cta' => true,
                    'cta_label' => 'Browse menu',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'Order now',
                    'secondary_cta_href' => '/cart',
                ]),
                $this->makeSection('trust', 'design-1', [
                    'items' => [
                        ['title' => 'Fresh ingredients'],
                        ['title' => 'Fast delivery'],
                        ['title' => 'Quality guaranteed'],
                        ['title' => 'Smooth ordering'],
                    ],
                ]),
                $this->makeSection('categories', 'design-2', [
                    'title' => 'Menu categories',
                    'subtitle' => 'Explore our drinks and desserts',
                ]),
                $this->makeSection('featured_products', 'design-1', [
                    'title' => 'Most popular',
                    'subtitle' => 'Hand-picked favorites',
                    'limit' => 8,
                ]),
                $this->makeSection('cta', 'design-3', [
                    'title' => 'Have a question?',
                    'subtitle' => 'We are happy to help',
                    'cta_label' => 'Contact us',
                    'cta_href' => '/contact',
                ]),
            ],
            'about' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'About '.$name,
                    'subtitle' => 'Our story and values',
                    'show_cta' => true,
                    'cta_label' => 'Browse menu',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('rich_text', 'design-2', [
                    'eyebrow' => 'Who we are',
                    'title' => $name,
                    'content' => 'A cafe built around quality coffee, fresh ingredients, and a welcoming experience.',
                ]),
                $this->makeSection('cta', 'design-2', [
                    'title' => 'Ready to try us?',
                    'subtitle' => 'Browse the menu or get in touch',
                    'cta_label' => 'Products',
                    'cta_href' => '/products',
                ]),
            ],
            'contact' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Contact us',
                    'subtitle' => 'Reach us on WhatsApp, Facebook, or Instagram.',
                    'show_cta' => false,
                ]),
                $this->makeSection('contact_channels', 'design-1', [
                    'title' => 'Channels',
                    'subtitle' => 'Choose what works for you',
                ]),
                $this->makeSection('map', 'design-1', [
                    'title' => 'Find us',
                    'subtitle' => 'We look forward to your visit',
                ]),
            ],
            'categories' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'Categories',
                    'subtitle' => 'Pick a category and explore',
                    'show_cta' => true,
                    'cta_label' => 'All products',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('categories', 'design-1', [
                    'title' => 'Explore categories',
                    'subtitle' => 'Choose what you love',
                ]),
            ],
            'products' => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'All products',
                    'subtitle' => 'Search and filter by category or price',
                    'show_cta' => true,
                    'cta_label' => 'Categories',
                    'cta_href' => '/categories',
                ]),
            ],
            'cart' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Your cart',
                    'subtitle' => 'Review your order and complete delivery details',
                    'show_cta' => true,
                    'cta_label' => 'Continue shopping',
                    'cta_href' => '/products',
                ]),
            ],
            'checkout-success' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Order confirmed',
                    'subtitle' => 'Thank you — we are preparing your order now',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                ]),
            ],
            'errors/404' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Page not found',
                    'subtitle' => 'Sorry, this link is unavailable or has moved.',
                    'eyebrow' => 'Error 404',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                ]),
            ],
            'errors/403' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Access denied',
                    'subtitle' => 'You do not have permission to view this page.',
                    'eyebrow' => 'Error 403',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                ]),
            ],
            'errors/500' => [
                $this->makeSection('hero', 'design-3', [
                    'title' => 'Something went wrong',
                    'subtitle' => 'We are working on it. Please try again shortly.',
                    'eyebrow' => 'Error 500',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                ]),
            ],
            default => [
                $this->makeSection('hero', 'design-1', [
                    'title' => 'Page title',
                    'subtitle' => 'Short page description',
                    'show_cta' => true,
                    'cta_label' => 'Browse menu',
                    'cta_href' => '/products',
                ]),
                $this->makeSection('rich_text', 'design-1', [
                    'title' => 'Page content',
                    'content' => 'Add your content from the page editor.',
                ]),
            ],
        };
    }

    /**
     * @return array{title: string, seo_title: ?string, seo_description: ?string}
     */
    protected function systemMeta(string $path, SiteSetting $site, string $locale = 'ar'): array
    {
        $name = $site->business_name ?: config('app.name');
        $locale = \App\Support\Locale::normalize($locale);

        if ($locale === 'en') {
            return match ($path) {
                'home' => ['title' => 'Home', 'seo_title' => $name, 'seo_description' => $site->meta_description],
                'about' => ['title' => 'About', 'seo_title' => 'About '.$name, 'seo_description' => $site->meta_description],
                'contact' => ['title' => 'Contact', 'seo_title' => 'Contact us', 'seo_description' => $site->meta_description],
                'categories' => ['title' => 'Categories', 'seo_title' => 'Categories', 'seo_description' => $site->meta_description],
                'products' => ['title' => 'Products', 'seo_title' => 'Products', 'seo_description' => $site->meta_description],
                'cart' => ['title' => 'Cart', 'seo_title' => 'Cart', 'seo_description' => 'Review your order'],
                'checkout-success' => ['title' => 'Order success', 'seo_title' => 'Order confirmed', 'seo_description' => 'Thank you'],
                'errors/404' => ['title' => '404', 'seo_title' => 'Page not found', 'seo_description' => 'Unavailable'],
                'errors/403' => ['title' => '403', 'seo_title' => 'Forbidden', 'seo_description' => 'No access'],
                'errors/500' => ['title' => '500', 'seo_title' => 'Server error', 'seo_description' => 'Unexpected error'],
                default => ['title' => 'Page', 'seo_title' => null, 'seo_description' => null],
            };
        }

        return match ($path) {
            'home' => [
                'title' => 'الرئيسية',
                'seo_title' => $site->meta_title ?: $name,
                'seo_description' => $site->meta_description,
            ],
            'about' => [
                'title' => 'عن المقهى',
                'seo_title' => $site->about_title ?: "عن {$name}",
                'seo_description' => $site->meta_description,
            ],
            'contact' => [
                'title' => 'التواصل',
                'seo_title' => $site->contact_title ?: 'تواصل معنا',
                'seo_description' => $site->meta_description,
            ],
            'categories' => [
                'title' => 'الأقسام',
                'seo_title' => $site->categories_page_title ?: SiteSetting::defaults()['categories_page_title'],
                'seo_description' => $site->meta_description,
            ],
            'products' => [
                'title' => 'المنتجات',
                'seo_title' => $site->products_page_title ?: SiteSetting::defaults()['products_page_title'],
                'seo_description' => $site->meta_description,
            ],
            'cart' => [
                'title' => 'السلة',
                'seo_title' => 'سلة الطلبات',
                'seo_description' => 'راجع طلبك وأكمل خطوات التوصيل',
            ],
            'checkout-success' => [
                'title' => 'نجاح الطلب',
                'seo_title' => 'تم تأكيد الطلب',
                'seo_description' => 'شكراً لطلبك',
            ],
            'errors/404' => [
                'title' => 'خطأ 404',
                'seo_title' => 'الصفحة غير موجودة',
                'seo_description' => 'الصفحة المطلوبة غير متاحة',
            ],
            'errors/403' => [
                'title' => 'خطأ 403',
                'seo_title' => 'غير مصرّح',
                'seo_description' => 'ليس لديك صلاحية للوصول',
            ],
            'errors/500' => [
                'title' => 'خطأ 500',
                'seo_title' => 'خطأ في الخادم',
                'seo_description' => 'حدث خطأ غير متوقع',
            ],
            default => [
                'title' => 'صفحة',
                'seo_title' => null,
                'seo_description' => null,
            ],
        };
    }

    public function ensureSystemPages(bool $refreshContent = false): void
    {
        $site = SiteSetting::current();

        foreach (\App\Support\Locale::AVAILABLE as $locale) {
            foreach (self::systemPaths() as $path) {
                $meta = $this->systemMeta($path, $site, $locale);
                $payload = [
                    'title' => $meta['title'],
                    'locale' => $locale,
                    'is_system' => true,
                    'is_published' => true,
                    'theme' => CmsPage::defaultTheme(),
                    'seo_title' => $meta['seo_title'],
                    'seo_description' => $meta['seo_description'],
                    'sections' => $this->defaultSectionsFor($path, $site, $locale),
                ];

                $page = CmsPage::query()->firstOrCreate(
                    ['path' => $path, 'locale' => $locale],
                    $payload,
                );

                if ($refreshContent) {
                    $page->update([
                        'title' => $meta['title'],
                        'is_system' => true,
                        'is_published' => true,
                        'seo_title' => $meta['seo_title'],
                        'seo_description' => $meta['seo_description'],
                        'sections' => $payload['sections'],
                        'theme' => $page->theme ?: CmsPage::defaultTheme(),
                    ]);
                } elseif (! $page->is_system) {
                    $page->update(['is_system' => true, 'is_published' => true]);
                }
            }
        }
    }
}
