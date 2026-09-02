<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SiteSetting extends Model
{
    protected $fillable = [
        'business_name',
        'tagline',
        'logo',
        'table_qr_logo',
        'phone',
        'email',
        'address',
        'whatsapp',
        'instagram',
        'facebook',
        'tiktok',
        'tax_number',
        'opening_hours',
        'store_opens_at',
        'store_closes_at',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'homepage_sections',
        'show_featured_products',
        'featured_products_limit',
        'trust_items',
        'categories_section_title',
        'categories_section_subtitle',
        'featured_section_title',
        'featured_section_subtitle',
        'contact_preview_title',
        'contact_preview_subtitle',
        'categories_page_title',
        'categories_page_subtitle',
        'categories_page_image',
        'products_page_title',
        'products_page_subtitle',
        'products_page_image',
        'about_title',
        'about_content',
        'about_image',
        'about_features',
        'about_eyebrow',
        'about_why_title',
        'about_why_subtitle',
        'about_cta_title',
        'about_cta_subtitle',
        'contact_title',
        'contact_content',
        'contact_map_embed',
        'contact_form_enabled',
        'contact_eyebrow',
        'map_lat',
        'map_lng',
        'map_title',
        'map_subtitle',
        'map_placements',
        'table_access_code',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'homepage_sections' => 'array',
            'about_features' => 'array',
            'trust_items' => 'array',
            'map_placements' => 'array',
            'show_featured_products' => 'boolean',
            'contact_form_enabled' => 'boolean',
            'featured_products_limit' => 'integer',
            'map_lat' => 'float',
            'map_lng' => 'float',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'business_name' => 'Kasher Cafe',
            'tagline' => 'قهوة مميزة في أجواء رائعة',
            'hero_title' => 'مرحباً بكم في مقهانا',
            'hero_subtitle' => 'استمتع بأفضل أنواع القهوة والمشروبات والحلويات',
            'homepage_sections' => ['hero', 'categories', 'featured', 'about_preview'],
            'show_featured_products' => true,
            'featured_products_limit' => 8,
            'trust_items' => [
                ['title' => 'مكونات طازجة يومياً'],
                ['title' => 'توصيل سريع وموثوق'],
                ['title' => 'جودة مضمونة'],
                ['title' => 'تجربة طلب سلسة'],
            ],
            'categories_section_title' => 'أقسام القائمة',
            'categories_section_subtitle' => 'اكتشف مجموعتنا المتنوعة من المشروبات والحلويات',
            'featured_section_title' => 'الأكثر طلباً',
            'featured_section_subtitle' => 'منتجات مختارة بعناية — جرّبها اليوم',
            'contact_preview_title' => 'هل لديك استفسار؟',
            'contact_preview_subtitle' => 'فريقنا جاهز للرد على أسئلتك ومساعدتك في اختيار الطلب المناسب',
            'categories_page_title' => 'أقسام القائمة',
            'categories_page_subtitle' => 'اختَر القسم الذي يناسبك بهدوء — تجربة بسيطة وأنيقة',
            'products_page_title' => 'جميع المنتجات',
            'products_page_subtitle' => 'ابحث وفلتر حسب القسم والسعر — اطلب ما يناسبك',
            'about_title' => 'عن المقهى',
            'about_content' => 'نحن مقهى متخصص نقدم تجربة فريدة من نوعها.',
            'about_eyebrow' => 'قصتنا',
            'about_why_title' => 'لماذا نحن؟',
            'about_why_subtitle' => 'قيمنا التي نصنع بها كل كوب وكل زيارة',
            'about_cta_title' => 'جاهز لتجربة مقهانا؟',
            'about_cta_subtitle' => 'تصفّح قائمتنا واطلب ما تحب — أو تواصل معنا لأي استفسار',
            'contact_title' => 'تواصل معنا',
            'contact_content' => 'تواصل معنا مباشرة عبر واتساب أو تابعنا على فيسبوك وانستغرام.',
            'contact_eyebrow' => 'نحن هنا لمساعدتك',
            'contact_form_enabled' => false,
            'opening_hours' => 'يومياً: 10:00 ص – 9:00 م',
            'store_opens_at' => '10:00',
            'store_closes_at' => '21:00',
            'map_title' => 'موقعنا على الخريطة',
            'map_subtitle' => 'تفضل بزيارتنا في أقرب وقت',
            'map_placements' => ['contact', 'home'],
            'table_access_code' => '246810',
        ];
    }

    public static function tableAccessCode(): string
    {
        $settings = static::current();

        if (blank($settings->table_access_code)) {
            $settings->forceFill([
                'table_access_code' => static::generateTableAccessCode(),
            ])->save();
            $settings->refresh();
        }

        return (string) $settings->table_access_code;
    }

    public static function regenerateTableAccessCode(): string
    {
        $code = static::generateTableAccessCode();
        static::current()->forceFill(['table_access_code' => $code])->save();

        return $code;
    }

    public static function tableOrderQrUrl(): string
    {
        return url('/table-order?t='.urlencode(static::tableAccessToken()));
    }

    public static function tableAccessToken(): string
    {
        return Crypt::encryptString(static::tableAccessCode());
    }

    public static function resolveAccessCodeFromToken(?string $token): ?string
    {
        if (blank($token)) {
            return null;
        }

        try {
            $code = Crypt::decryptString($token);

            return filled($code) ? (string) $code : null;
        } catch (DecryptException) {
            return null;
        }
    }

    public static function generateTableAccessCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
