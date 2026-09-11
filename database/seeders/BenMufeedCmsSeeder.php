<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\SiteSetting;
use App\Services\Cms\CmsPageBuilderService;
use App\Support\Locale;
use Illuminate\Database\Seeder;

class BenMufeedCmsSeeder extends Seeder
{
    public function run(): void
    {
        $builder = app(CmsPageBuilderService::class);
        $site = SiteSetting::current();
        $theme = [
            'primary' => '#78350f',
            'secondary' => '#a16207',
            'font_family' => 'Cairo',
            'base_font_size' => 16,
        ];

        foreach (Locale::AVAILABLE as $locale) {
            foreach (CmsPage::systemPaths() as $path) {
                $meta = $this->pageMeta($path, $locale);
                $sections = $locale === 'en'
                    ? $this->englishSections($path, $builder, $site)
                    : $this->arabicSections($path, $builder, $site);

                CmsPage::query()->updateOrCreate(
                    ['path' => $path, 'locale' => $locale],
                    [
                        'title' => $meta['title'],
                        'is_system' => true,
                        'is_published' => true,
                        'theme' => $theme,
                        'seo_title' => $meta['seo_title'],
                        'seo_description' => $meta['seo_description'],
                        'sections' => $sections,
                    ],
                );
            }
        }
    }

    /**
     * @return array{title: string, seo_title: string, seo_description: string}
     */
    private function pageMeta(string $path, string $locale): array
    {
        if ($locale === 'en') {
            return match ($path) {
                'home' => ['title' => 'Home', 'seo_title' => 'Ben Mufeed | Cafe & Restaurant', 'seo_description' => 'Order coffee, drinks, food & shisha. Dine-in, takeaway, delivery & QR table ordering.'],
                'about' => ['title' => 'About', 'seo_title' => 'About Ben Mufeed', 'seo_description' => 'Our story, values, and what makes Ben Mufeed special.'],
                'contact' => ['title' => 'Contact', 'seo_title' => 'Contact Ben Mufeed', 'seo_description' => 'Reach us on WhatsApp, Facebook, or Instagram.'],
                'categories' => ['title' => 'Categories', 'seo_title' => 'Menu Categories', 'seo_description' => 'Browse our menu by category.'],
                'products' => ['title' => 'Menu', 'seo_title' => 'Full Menu', 'seo_description' => 'All drinks, food, and shisha at Ben Mufeed.'],
                'cart' => ['title' => 'Cart', 'seo_title' => 'Your Cart', 'seo_description' => 'Review your order and complete checkout.'],
                'checkout-success' => ['title' => 'Order Confirmed', 'seo_title' => 'Order Confirmed', 'seo_description' => 'Thank you for your order at Ben Mufeed.'],
                'errors/404' => ['title' => '404', 'seo_title' => 'Page Not Found', 'seo_description' => 'This page is unavailable.'],
                'errors/403' => ['title' => '403', 'seo_title' => 'Access Denied', 'seo_description' => 'You do not have permission.'],
                'errors/500' => ['title' => '500', 'seo_title' => 'Server Error', 'seo_description' => 'Something went wrong.'],
                default => ['title' => 'Page', 'seo_title' => 'Ben Mufeed', 'seo_description' => ''],
            };
        }

        return match ($path) {
            'home' => ['title' => 'الرئيسية', 'seo_title' => 'بن مفيد | مقهى ومطعم — طلب أونلاين وتوصيل', 'seo_description' => 'اطلب من بن مفيد: قهوة، مشروبات، أطباق، وأراجيل. سفري، توصيل، أو QR للطاولة.'],
            'about' => ['title' => 'عن المقهى', 'seo_title' => 'عن بن مفيد', 'seo_description' => 'تعرّف على قصة بن مفيد وقيمنا وما يميزنا.'],
            'contact' => ['title' => 'التواصل', 'seo_title' => 'تواصل مع بن مفيد', 'seo_description' => 'راسلنا على واتساب أو تابعنا على فيسبوك وانستغرام.'],
            'categories' => ['title' => 'الأقسام', 'seo_title' => 'أقسام قائمة بن مفيد', 'seo_description' => 'تصفّح القائمة حسب القسم.'],
            'products' => ['title' => 'القائمة', 'seo_title' => 'قائمة بن مفيد الكاملة', 'seo_description' => 'جميع المشروبات والأطباق والأراجيل.'],
            'cart' => ['title' => 'السلة', 'seo_title' => 'سلة الطلبات', 'seo_description' => 'راجع طلبك وأكمل خطوات التوصيل.'],
            'checkout-success' => ['title' => 'نجاح الطلب', 'seo_title' => 'تم تأكيد الطلب', 'seo_description' => 'شكراً لطلبك من بن مفيد.'],
            'errors/404' => ['title' => 'خطأ 404', 'seo_title' => 'الصفحة غير موجودة', 'seo_description' => 'الصفحة المطلوبة غير متاحة.'],
            'errors/403' => ['title' => 'خطأ 403', 'seo_title' => 'غير مصرّح', 'seo_description' => 'ليس لديك صلاحية للوصول.'],
            'errors/500' => ['title' => 'خطأ 500', 'seo_title' => 'خطأ في الخادم', 'seo_description' => 'حدث خطأ غير متوقع.'],
            default => ['title' => 'صفحة', 'seo_title' => 'بن مفيد', 'seo_description' => ''],
        };
    }

    /** @return list<array<string, mixed>> */
    private function arabicSections(string $path, CmsPageBuilderService $b, SiteSetting $site): array
    {
        $name = $site->business_name ?: 'بن مفيد';

        return match ($path) {
            'home' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => $name,
                    'title' => $site->hero_title ?: "مرحباً بكم في {$name}",
                    'subtitle' => $site->hero_subtitle ?: $site->tagline,
                    'show_cta' => true,
                    'cta_label' => 'تصفّح القائمة',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'اطلب الآن',
                    'secondary_cta_href' => '/cart',
                    'show_hours' => true,
                    'hours_text' => $site->opening_hours,
                ]),
                $b->makeSection('trust', 'design-1', [
                    'items' => $site->trust_items ?: [
                        ['title' => 'قهوة مختصة يومياً'],
                        ['title' => 'توصيل وسفري'],
                        ['title' => 'طلب من الطاولة QR'],
                        ['title' => 'أراجيل بنكهات مختارة'],
                    ],
                ]),
                $b->makeSection('categories', 'design-2', [
                    'title' => $site->categories_section_title ?: 'أقسام القائمة',
                    'subtitle' => $site->categories_section_subtitle ?: 'قهوة، مشروبات، عصائر، حلويات، وأراجيل',
                ]),
                $b->makeSection('featured_products', 'design-3', [
                    'title' => $site->featured_section_title ?: 'الأكثر طلباً',
                    'subtitle' => $site->featured_section_subtitle ?: 'تشكيلة مختارة من مشروباتنا وأطباقنا',
                    'limit' => (int) ($site->featured_products_limit ?: 8),
                ]),
                $b->makeSection('rich_text', 'design-2', [
                    'eyebrow' => $site->about_eyebrow ?: 'قصتنا',
                    'title' => $site->about_title ?: "عن {$name}",
                    'content' => $site->about_content ?: $site->tagline,
                ]),
                $b->makeSection('cta', 'design-3', [
                    'title' => $site->contact_preview_title ?: 'هل لديك استفسار؟',
                    'subtitle' => $site->contact_preview_subtitle ?: 'تواصل معنا على واتساب — فريق بن مفيد جاهز لخدمتك',
                    'cta_label' => 'تواصل معنا',
                    'cta_href' => '/contact',
                ]),
                $b->makeSection('map', 'design-1', [
                    'title' => $site->map_title ?: 'موقع بن مفيد',
                    'subtitle' => $site->map_subtitle ?: 'تفضل بزيارتنا — نرحّب بكم في أي وقت',
                ]),
            ],

            'about' => [
                $b->makeSection('hero', 'design-2', [
                    'eyebrow' => $site->about_eyebrow ?: 'قصتنا',
                    'title' => $site->about_title ?: "عن {$name}",
                    'subtitle' => $site->tagline,
                    'show_cta' => true,
                    'cta_label' => 'تصفّح القائمة',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'تواصل معنا',
                    'secondary_cta_href' => '/contact',
                ]),
                $b->makeSection('rich_text', 'design-2', [
                    'eyebrow' => 'من نحن',
                    'title' => $name,
                    'content' => $site->about_content ?: "{$name} — مقهى ومطعم يجمع بين القهوة العربية الأصيلة والأجواء الراقية.",
                ]),
                $b->makeSection('trust', 'design-2', [
                    'title' => $site->about_why_title ?: 'لماذا بن مفيد؟',
                    'subtitle' => $site->about_why_subtitle ?: 'قيمنا التي نصنع بها كل كوب وكل زيارة',
                    'items' => $site->about_features ?: [
                        ['title' => 'قهوة مختصة', 'description' => 'حبوب مختارة — إسبريسو، V60، لاتيه، وفرابيه'],
                        ['title' => 'مشروبات وعصائر', 'description' => 'عصائر طبيعية، موهيتو، آيس تي، وميلك شيك'],
                        ['title' => 'أجواء مميزة', 'description' => 'مساحة مريحة للعائلة والأصدقاء'],
                        ['title' => 'خدمة متعددة', 'description' => 'سفري، توصيل، وطلب من الطاولة عبر QR'],
                    ],
                ]),
                $b->makeSection('rich_text', 'design-3', [
                    'eyebrow' => '',
                    'title' => '',
                    'content' => '« في بن مفيد، كل كوب قصة — وكل زيارة تجربة لا تُنسى. »',
                ]),
                $b->makeSection('custom_html', 'design-1', [
                    'text' => filled($site->opening_hours)
                        ? "⏰ أوقات العمل: {$site->opening_hours}"
                        : '',
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => $site->about_cta_title ?: 'جاهز لتجربة بن مفيد؟',
                    'subtitle' => $site->about_cta_subtitle ?: 'تصفّح قائمتنا واطلب ما تحب',
                    'cta_label' => 'عرض القائمة',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('map', 'design-2', [
                    'title' => $site->map_title ?: 'موقعنا',
                    'subtitle' => $site->map_subtitle ?: 'تفضل بزيارتنا',
                ]),
            ],

            'contact' => [
                $b->makeSection('hero', 'design-3', [
                    'eyebrow' => $site->contact_eyebrow ?: 'نحن هنا لمساعدتك',
                    'title' => $site->contact_title ?: 'تواصل معنا',
                    'subtitle' => $site->contact_content ?: 'راسلنا على واتساب أو تابعنا على شبكاتنا الاجتماعية.',
                    'show_cta' => false,
                ]),
                $b->makeSection('contact_channels', 'design-1', [
                    'title' => 'قنوات التواصل',
                    'subtitle' => 'اختر الطريقة الأنسب لك — نرد بأسرع وقت',
                ]),
                $b->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'معلومات الوصول',
                    'title' => 'العنوان وأوقات العمل',
                    'content' => collect([
                        filled($site->address) ? "📍 العنوان: {$site->address}" : null,
                        filled($site->phone) ? "📞 الهاتف: {$site->phone}" : null,
                        filled($site->opening_hours) ? "⏰ أوقات العمل: {$site->opening_hours}" : null,
                        filled($site->email) ? "✉️ البريد: {$site->email}" : null,
                    ])->filter()->implode("\n"),
                ]),
                $b->makeSection('map', 'design-1', [
                    'title' => $site->map_title ?: 'موقع بن مفيد على الخريطة',
                    'subtitle' => $site->map_subtitle ?: 'نرحّب بزيارتكم',
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'جاهز للطلب؟',
                    'subtitle' => 'تصفّح قائمتنا واطلب بسهولة',
                    'cta_label' => 'عرض القائمة',
                    'cta_href' => '/products',
                ]),
            ],

            'categories' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => $name,
                    'title' => $site->categories_page_title ?: 'أقسام القائمة',
                    'subtitle' => $site->categories_page_subtitle ?: 'اختر القسم الذي يناسبك',
                    'show_cta' => true,
                    'cta_label' => 'جميع المنتجات',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('categories', 'design-1', [
                    'title' => 'استكشف الأقسام',
                    'subtitle' => 'قهوة ساخنة • مشروبات باردة • عصائر • حلويات • أراجيل',
                ]),
                $b->makeSection('trust', 'design-3', [
                    'items' => [
                        ['title' => 'قهوة مختصة'],
                        ['title' => 'عصائر طبيعية'],
                        ['title' => 'حلويات وبوظة'],
                        ['title' => 'أراجيل'],
                    ],
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => 'لم تجد ما تبحث عنه؟',
                    'subtitle' => 'تصفّح كل المنتجات دفعة واحدة',
                    'cta_label' => 'عرض القائمة الكاملة',
                    'cta_href' => '/products',
                ]),
            ],

            'products' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => $name,
                    'title' => $site->products_page_title ?: 'قائمة بن مفيد',
                    'subtitle' => $site->products_page_subtitle ?: 'تصفّح جميع المشروبات والأطباق',
                    'show_cta' => true,
                    'cta_label' => 'الأقسام',
                    'cta_href' => '/categories',
                    'secondary_cta_label' => 'السلة',
                    'secondary_cta_href' => '/cart',
                ]),
                $b->makeSection('trust', 'design-3', [
                    'items' => $site->trust_items ?: [
                        ['title' => 'قهوة مختصة يومياً'],
                        ['title' => 'توصيل وسفري'],
                        ['title' => 'طلب QR من الطاولة'],
                        ['title' => 'جودة مضمونة'],
                    ],
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'تصفّح حسب القسم',
                    'subtitle' => 'سهّل اختيارك عبر أقسام القائمة',
                    'cta_label' => 'عرض الأقسام',
                    'cta_href' => '/categories',
                ]),
            ],

            'cart' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'سلة الطلبات',
                    'subtitle' => 'راجع طلبك وأكمل بيانات التوصيل بخطوات بسيطة',
                    'eyebrow' => 'الطلب',
                    'show_cta' => true,
                    'cta_label' => 'متابعة التسوق',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('trust', 'design-1', [
                    'items' => [
                        ['title' => 'طلب سريع'],
                        ['title' => 'توصيل موثوق'],
                        ['title' => 'دفع عند الاستلام'],
                        ['title' => 'دعم واتساب'],
                    ],
                ]),
                $b->makeSection('custom_html', 'design-1', [
                    'text' => 'أكمل خطوات السلة بالأسفل: المنتجات ← الموقع ← التأكيد.',
                ]),
            ],

            'checkout-success' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'تم تأكيد طلبك',
                    'subtitle' => 'شكراً لثقتك ببن مفيد — سنبدأ بتجهيز طلبك فوراً',
                    'eyebrow' => 'نجاح الطلب ✓',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تصفّح القائمة',
                    'secondary_cta_href' => '/products',
                ]),
                $b->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'ماذا بعد؟',
                    'title' => 'نتواصل معك قريباً',
                    'content' => "سيتم التواصل معك لتأكيد التوصيل.\nيمكنك متابعة العروض والمنتجات الجديدة من قائمة بن مفيد.",
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => 'جرّب شيئاً جديداً؟',
                    'subtitle' => 'اكتشف تشكيلتنا المميزة من القهوة والمشروبات',
                    'cta_label' => 'عرض القائمة',
                    'cta_href' => '/products',
                ]),
            ],

            'errors/404' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'الصفحة غير موجودة',
                    'subtitle' => 'عذراً، الرابط الذي فتحته غير متاح أو تم نقله.',
                    'eyebrow' => 'خطأ 404',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تصفّح القائمة',
                    'secondary_cta_href' => '/products',
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'هل تبحث عن شيء محدد؟',
                    'subtitle' => 'جرّب الأقسام أو تواصل معنا',
                    'cta_label' => 'الأقسام',
                    'cta_href' => '/categories',
                ]),
            ],

            'errors/403' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'غير مصرّح بالوصول',
                    'subtitle' => 'ليس لديك صلاحية لعرض هذه الصفحة.',
                    'eyebrow' => 'خطأ 403',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                ]),
                $b->makeSection('contact_channels', 'design-2', [
                    'title' => 'تحتاج مساعدة؟',
                    'subtitle' => 'تواصل معنا على واتساب وسنساعدك',
                ]),
            ],

            'errors/500' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'حدث خطأ غير متوقع',
                    'subtitle' => 'نعمل على إصلاح المشكلة. حاول مجدداً بعد لحظات.',
                    'eyebrow' => 'خطأ 500',
                    'show_cta' => true,
                    'cta_label' => 'العودة للرئيسية',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'تواصل معنا',
                    'secondary_cta_href' => '/contact',
                ]),
                $b->makeSection('custom_html', 'design-1', [
                    'text' => 'إن استمرّت المشكلة، راسلنا عبر واتساب أو صفحة التواصل.',
                ]),
            ],

            default => $b->defaultSectionsFor($path, $site, 'ar'),
        };
    }

    /** @return list<array<string, mixed>> */
    private function englishSections(string $path, CmsPageBuilderService $b, SiteSetting $site): array
    {
        $name = 'Ben Mufeed';

        return match ($path) {
            'home' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => 'Cafe & Restaurant',
                    'title' => 'Welcome to Ben Mufeed',
                    'subtitle' => 'Authentic Arabic coffee, cold drinks, delicious food & premium shisha — dine-in, takeaway, delivery, or QR table ordering.',
                    'show_cta' => true,
                    'cta_label' => 'Browse menu',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'Order now',
                    'secondary_cta_href' => '/cart',
                    'show_hours' => true,
                    'hours_text' => $site->opening_hours,
                ]),
                $b->makeSection('trust', 'design-1', [
                    'items' => [
                        ['title' => 'Specialty coffee daily'],
                        ['title' => 'Delivery & takeaway'],
                        ['title' => 'QR table ordering'],
                        ['title' => 'Premium shisha'],
                    ],
                ]),
                $b->makeSection('categories', 'design-2', [
                    'title' => 'Menu categories',
                    'subtitle' => 'Coffee, drinks, juices, desserts, snacks & shisha',
                ]),
                $b->makeSection('featured_products', 'design-3', [
                    'title' => 'Most popular',
                    'subtitle' => 'Hand-picked favorites from our menu',
                    'limit' => 8,
                ]),
                $b->makeSection('rich_text', 'design-2', [
                    'eyebrow' => 'Our story',
                    'title' => "About {$name}",
                    'content' => 'Ben Mufeed brings together authentic Arabic coffee, refreshing drinks, delicious food, and premium shisha in a warm, family-friendly atmosphere.',
                ]),
                $b->makeSection('cta', 'design-3', [
                    'title' => 'Have a question?',
                    'subtitle' => 'Reach us on WhatsApp — we are happy to help',
                    'cta_label' => 'Contact us',
                    'cta_href' => '/contact',
                ]),
                $b->makeSection('map', 'design-1', [
                    'title' => 'Find us',
                    'subtitle' => 'We look forward to your visit',
                ]),
            ],

            'about' => [
                $b->makeSection('hero', 'design-2', [
                    'eyebrow' => 'Our story',
                    'title' => "About {$name}",
                    'subtitle' => 'Coffee, atmosphere, and hospitality',
                    'show_cta' => true,
                    'cta_label' => 'Browse menu',
                    'cta_href' => '/products',
                    'secondary_cta_label' => 'Contact us',
                    'secondary_cta_href' => '/contact',
                ]),
                $b->makeSection('rich_text', 'design-2', [
                    'eyebrow' => 'Who we are',
                    'title' => $name,
                    'content' => 'Ben Mufeed is a cafe and restaurant offering specialty coffee, natural juices, desserts, and premium shisha — with dine-in, takeaway, delivery, and QR table ordering.',
                ]),
                $b->makeSection('trust', 'design-2', [
                    'title' => 'Why Ben Mufeed?',
                    'subtitle' => 'What makes every visit special',
                    'items' => [
                        ['title' => 'Specialty coffee', 'description' => 'Espresso, V60, lattes, frappes & more'],
                        ['title' => 'Drinks & juices', 'description' => 'Natural juices, mojitos, iced tea & milkshakes'],
                        ['title' => 'Great atmosphere', 'description' => 'Comfortable space for family and friends'],
                        ['title' => 'Flexible service', 'description' => 'Takeaway, delivery & QR table ordering'],
                    ],
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => 'Ready to visit?',
                    'subtitle' => 'Browse our menu or get in touch',
                    'cta_label' => 'View menu',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('map', 'design-2', [
                    'title' => 'Our location',
                    'subtitle' => 'Damascus, Syria',
                ]),
            ],

            'contact' => [
                $b->makeSection('hero', 'design-3', [
                    'eyebrow' => 'We are here to help',
                    'title' => 'Contact us',
                    'subtitle' => 'Reach us on WhatsApp or follow us on social media.',
                    'show_cta' => false,
                ]),
                $b->makeSection('contact_channels', 'design-1', [
                    'title' => 'Get in touch',
                    'subtitle' => 'Choose the channel that works for you',
                ]),
                $b->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'Visit info',
                    'title' => 'Address & hours',
                    'content' => collect([
                        filled($site->address) ? "Address: {$site->address}" : null,
                        filled($site->phone) ? "Phone: {$site->phone}" : null,
                        filled($site->opening_hours) ? "Hours: {$site->opening_hours}" : null,
                    ])->filter()->implode("\n"),
                ]),
                $b->makeSection('map', 'design-1', [
                    'title' => 'Find us on the map',
                    'subtitle' => 'We welcome your visit',
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'Ready to order?',
                    'subtitle' => 'Browse our full menu',
                    'cta_label' => 'View menu',
                    'cta_href' => '/products',
                ]),
            ],

            'categories' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => $name,
                    'title' => 'Menu categories',
                    'subtitle' => 'Pick a category and explore',
                    'show_cta' => true,
                    'cta_label' => 'All products',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('categories', 'design-1', [
                    'title' => 'Explore categories',
                    'subtitle' => 'Coffee • Drinks • Juices • Desserts • Shisha',
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => 'Browse everything',
                    'subtitle' => 'See the full menu at once',
                    'cta_label' => 'Full menu',
                    'cta_href' => '/products',
                ]),
            ],

            'products' => [
                $b->makeSection('hero', 'design-1', [
                    'eyebrow' => $name,
                    'title' => 'Full menu',
                    'subtitle' => 'Search and filter by category',
                    'show_cta' => true,
                    'cta_label' => 'Categories',
                    'cta_href' => '/categories',
                ]),
                $b->makeSection('trust', 'design-3', [
                    'items' => [
                        ['title' => 'Specialty coffee'],
                        ['title' => 'Delivery & takeaway'],
                        ['title' => 'QR ordering'],
                        ['title' => 'Quality guaranteed'],
                    ],
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'Browse by category',
                    'subtitle' => 'Find what you love faster',
                    'cta_label' => 'View categories',
                    'cta_href' => '/categories',
                ]),
            ],

            'cart' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'Your cart',
                    'subtitle' => 'Review your order and complete delivery details',
                    'eyebrow' => 'Checkout',
                    'show_cta' => true,
                    'cta_label' => 'Continue shopping',
                    'cta_href' => '/products',
                ]),
                $b->makeSection('trust', 'design-1', [
                    'items' => [
                        ['title' => 'Fast ordering'],
                        ['title' => 'Reliable delivery'],
                        ['title' => 'Cash on delivery'],
                        ['title' => 'WhatsApp support'],
                    ],
                ]),
            ],

            'checkout-success' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'Order confirmed',
                    'subtitle' => 'Thank you for choosing Ben Mufeed — we are preparing your order now',
                    'eyebrow' => 'Success ✓',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'Browse menu',
                    'secondary_cta_href' => '/products',
                ]),
                $b->makeSection('rich_text', 'design-1', [
                    'eyebrow' => 'What next?',
                    'title' => 'We will contact you soon',
                    'content' => "We will confirm your delivery details shortly.\nExplore more from our menu anytime.",
                ]),
                $b->makeSection('cta', 'design-2', [
                    'title' => 'Try something new?',
                    'subtitle' => 'Discover our specialty drinks',
                    'cta_label' => 'View menu',
                    'cta_href' => '/products',
                ]),
            ],

            'errors/404' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'Page not found',
                    'subtitle' => 'Sorry, this link is unavailable or has moved.',
                    'eyebrow' => 'Error 404',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'Browse menu',
                    'secondary_cta_href' => '/products',
                ]),
                $b->makeSection('cta', 'design-1', [
                    'title' => 'Looking for something?',
                    'subtitle' => 'Try categories or contact us',
                    'cta_label' => 'Categories',
                    'cta_href' => '/categories',
                ]),
            ],

            'errors/403' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'Access denied',
                    'subtitle' => 'You do not have permission to view this page.',
                    'eyebrow' => 'Error 403',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                ]),
                $b->makeSection('contact_channels', 'design-2', [
                    'title' => 'Need help?',
                    'subtitle' => 'Contact us on WhatsApp',
                ]),
            ],

            'errors/500' => [
                $b->makeSection('hero', 'design-3', [
                    'title' => 'Something went wrong',
                    'subtitle' => 'We are working on it. Please try again shortly.',
                    'eyebrow' => 'Error 500',
                    'show_cta' => true,
                    'cta_label' => 'Back home',
                    'cta_href' => '/',
                    'secondary_cta_label' => 'Contact us',
                    'secondary_cta_href' => '/contact',
                ]),
                $b->makeSection('custom_html', 'design-1', [
                    'text' => 'If the problem persists, reach us on WhatsApp or the contact page.',
                ]),
            ],

            default => $b->defaultSectionsFor($path, $site, 'en'),
        };
    }
}
