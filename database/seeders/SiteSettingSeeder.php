<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::current()->update([
            'business_name' => 'بن مفيد',
            'tagline' => 'قهوة، مشروبات، أطباق لذيذة، وأراجيل — في أجواء مريحة للعائلة والأصدقاء',
            'phone' => '+963985605918',
            'whatsapp' => '963985605918',
            'email' => 'info@benmufeed.com',
            'address' => 'دمشق، سوريا',
            'facebook' => 'https://www.facebook.com/share/18GJEjH13m/?mibextid=wwXIfr',
            'instagram' => 'https://www.instagram.com/ben_mufeed?igsh=MWJrMWx2MHdjbnE1Mw%3D%3D&utm_source=qr',
            'tiktok' => 'https://www.tiktok.com/@benmufeed?_r=1&_t=ZN-974F64FCPZe',
            'opening_hours' => 'يومياً: 10:00 ص – 12:00 م',
            'store_opens_at' => '10:00',
            'store_closes_at' => '00:00',
            'hero_title' => 'مرحباً بكم في بن مفيد',
            'hero_subtitle' => 'قهوة عربية أصيلة، مشروبات باردة، أطباق شهية، وأراجيل بنكهات مختارة — سفري، توصيل، أو من طاولتكم عبر QR',
            'homepage_sections' => ['hero', 'trust', 'categories', 'featured', 'about_preview', 'contact_preview', 'map'],
            'show_featured_products' => true,
            'featured_products_limit' => 8,
            'trust_items' => [
                ['title' => 'قهوة مختصة يومياً'],
                ['title' => 'توصيل وسفري'],
                ['title' => 'طلب من الطاولة QR'],
                ['title' => 'أراجيل بنكهات مختارة'],
            ],
            'categories_section_title' => 'أقسام القائمة',
            'categories_section_subtitle' => 'قهوة ساخنة، مشروبات باردة، عصائر، حلويات، تسالي، وأراجيل',
            'featured_section_title' => 'الأكثر طلباً',
            'featured_section_subtitle' => 'تشكيلة مختارة من مشروباتنا وأطباقنا المميزة',
            'contact_preview_title' => 'هل لديك استفسار؟',
            'contact_preview_subtitle' => 'تواصل معنا على واتساب أو تابعنا على شبكاتنا — فريق بن مفيد جاهز لخدمتك',
            'categories_page_title' => 'أقسام القائمة',
            'categories_page_subtitle' => 'اختر القسم الذي يناسبك واستمتع بتجربة طلب سلسة',
            'products_page_title' => 'قائمة بن مفيد',
            'products_page_subtitle' => 'تصفّح جميع المشروبات والأطباق — ابحث وفلتر حسب القسم',
            'about_title' => 'عن بن مفيد',
            'about_eyebrow' => 'قصتنا',
            'about_content' => <<<'TEXT'
بن مفيد — مقهى ومطعم يجمع بين القهوة العربية الأصيلة، المشروبات الباردة، الأطباق الشهية، والأراجيل بنكهات مختارة.

نرحّب بكم في أجواء مريحة للعائلة والأصدقاء، مع خدمة سفري وتوصيل وطلب من الطاولة عبر QR مباشرة من جوالكم. نحرص على اختيار أجود المكونات وتحضير كل مشروب وطبق بعناية فائقة.
TEXT,
            'about_features' => [
                ['title' => 'قهوة مختصة', 'description' => 'حبوب أرابيكا وروبوستا مختارة — إسبريسو، V60، لاتيه، وفرابيه'],
                ['title' => 'مشروبات وعصائر', 'description' => 'عصائر طبيعية، موهيتو، آيس تي، وميلك شيك بنكهات متنوعة'],
                ['title' => 'أجواء مميزة', 'description' => 'مساحة مريحة للعائلة والأصدقاء مع أراجيل بنكهات راقية'],
                ['title' => 'خدمة متعددة', 'description' => 'سفري، توصيل، وطلب من الطاولة عبر QR — بكل سهولة'],
            ],
            'about_why_title' => 'لماذا بن مفيد؟',
            'about_why_subtitle' => 'قيمنا التي نصنع بها كل كوب وكل زيارة',
            'about_cta_title' => 'جاهز لتجربة بن مفيد؟',
            'about_cta_subtitle' => 'تصفّح قائمتنا واطلب ما تحب — أو تواصل معنا لأي استفسار',
            'contact_title' => 'تواصل معنا',
            'contact_eyebrow' => 'نحن هنا لمساعدتك',
            'contact_content' => 'تواصل معنا مباشرة عبر واتساب أو تابعنا على فيسبوك وانستغرام وتiktok. نسعد باستقبال استفساراتكم واقتراحاتكم.',
            'contact_form_enabled' => false,
            'map_lat' => 33.5138,
            'map_lng' => 36.2765,
            'map_title' => 'موقع بن مفيد',
            'map_subtitle' => 'تفضل بزيارتنا — نرحّب بكم في أي وقت',
            'map_placements' => ['home', 'contact', 'about'],
            'meta_title' => 'بن مفيد | مقهى ومطعم — طلب أونلاين وتوصيل',
            'meta_description' => 'اطلب من بن مفيد: قهوة، مشروبات، أطباق، وأراجيل. سفري، توصيل، أو QR للطاولة. تواصل معنا على واتساب +963 985 605 918.',
        ]);
    }
}
