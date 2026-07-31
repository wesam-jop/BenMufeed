<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\StorefrontCache;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'بن مفيد',
                'site_description' => <<<'TEXT'
بن مفيد — مقهى ومطعم يجمع بين القهوة العربية الأصيلة، المشروبات الباردة، الأطباق الشهية، والأراجيل بنكهات مختارة. نرحّب بكم في أجواء مريحة للعائلة والأصدقاء، مع خدمة سفري وتوصيل وطلب من الطاولة عبر QR مباشرة من جوالكم. تابعونا على شبكاتنا لمعرفة العروض، أوقات المباريات، والجدول اليومي.
TEXT,
                'whatsapp_phone' => '963985605918',
                'facebook_url' => 'https://www.facebook.com/share/18GJEjH13m/?mibextid=wwXIfr',
                'instagram_url' => 'https://www.instagram.com/ben_mufeed?igsh=MWJrMWx2MHdjbnE1Mw%3D%3D&utm_source=qr',
                'tiktok_url' => 'https://www.tiktok.com/@benmufeed?_r=1&_t=ZN-974F64FCPZe',
                'hero_title' => 'مرحباً بكم في بن مفيد',
                'hero_subtitle' => 'قهوة، مشروبات، أطباق لذيذة، وأراجيل — سفري، توصيل، أو من طاولتكم',
                'seo_title' => 'بن مفيد | مقهى ومطعم — طلب أونلاين وتوصيل',
                'seo_description' => 'اطلب من بن مفيد: قهوة، مشروبات، أطباق، وأراجيل. سفري، توصيل، أو QR للطاولة. تواصل معنا على واتساب +963 985 605 918.',
                'seo_keywords' => 'بن مفيد, مقهى, مطعم, قهوة, أراجيل, شيشة, توصيل, سفري, طلب أونلاين, مشروبات, سوريا',
            ],
        );

        StorefrontCache::forgetSite();
    }
}
