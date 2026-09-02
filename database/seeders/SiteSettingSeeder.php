<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::current()->update([
            'business_name' => 'Kasher Cafe',
            'tagline' => 'قهوة مميزة في أجواء رائعة',
            'hero_title' => 'مرحباً بكم في Kasher Cafe',
            'hero_subtitle' => 'استمتع بأفضل أنواع القهوة والمشروبات والحلويات',
            'about_title' => 'عن المقهى',
            'about_content' => 'نحن مقهى متخصص نقدم تجربة فريدة من نوعها. نحرص على اختيار أجود حبوب القهوة وتحضير كل مشروب بعناية.',
            'about_features' => [
                ['title' => 'قهوة مختصة', 'description' => 'حبوب محمصة بعناية'],
                ['title' => 'أجواء مميزة', 'description' => 'مساحة مريحة للعمل والاجتماعات'],
                ['title' => 'خدمة سريعة', 'description' => 'طلبات جاهزة في دقائق'],
            ],
            'contact_title' => 'تواصل معنا',
            'contact_content' => 'تواصل معنا مباشرة عبر واتساب أو تابعنا على فيسبوك وانستغرام.',
            'opening_hours' => 'يومياً: 10:00 ص – 9:00 م',
            'store_opens_at' => '10:00',
            'store_closes_at' => '21:00',
            'homepage_sections' => ['hero', 'categories', 'featured', 'about_preview', 'contact_preview'],
        ]);
    }
}
