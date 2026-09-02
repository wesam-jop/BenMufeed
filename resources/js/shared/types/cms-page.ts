export type CmsSectionType =
    | 'hero'
    | 'trust'
    | 'categories'
    | 'featured_products'
    | 'rich_text'
    | 'cta'
    | 'contact_channels'
    | 'map'
    | 'custom_html';

export type CmsSectionVariant = 'design-1' | 'design-2' | 'design-3';

export type CmsPageTheme = {
    primary: string;
    secondary: string;
    font_family: string;
    base_font_size: number;
};

export type CmsSection = {
    id: string;
    type: CmsSectionType;
    variant: CmsSectionVariant | string;
    enabled: boolean;
    props: Record<string, unknown>;
};

export type CmsPagePayload = {
    id: number;
    title: string;
    path: string;
    locale?: 'ar' | 'en' | string;
    display_path?: string;
    is_system: boolean;
    is_protected?: boolean;
    can_delete?: boolean;
    can_unpublish?: boolean;
    can_edit_path?: boolean;
    is_published: boolean;
    public_url: string;
    group?: string;
    group_label?: string;
    seo_title?: string | null;
    seo_description?: string | null;
    theme: CmsPageTheme;
    sections: CmsSection[];
    sections_count?: number;
    updated_at?: string | null;
};

export type CmsCatalogItem = {
    type: CmsSectionType;
    label: string;
    description: string;
    icon?: string;
    variants: Array<{ id: string; label: string; hint?: string }>;
};

export function createCmsSectionId(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }
    return `sec-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

export function makeCmsSection(
    type: CmsSectionType,
    variant: string = 'design-1',
    props: Record<string, unknown> = {},
): CmsSection {
    const defaults: Partial<Record<CmsSectionType, Record<string, unknown>>> = {
        hero: {
            title: 'عنوان البانر',
            subtitle: 'وصف قصير جذاب',
            eyebrow: '',
            image: '',
            show_cta: true,
            cta_label: 'تصفح القائمة',
            cta_href: '/products',
            secondary_cta_label: '',
            secondary_cta_href: '/cart',
            show_hours: false,
            hours_text: '',
        },
        trust: {
            items: [
                { title: 'جودة مضمونة' },
                { title: 'توصيل سريع' },
                { title: 'مكونات طازجة' },
            ],
        },
        categories: {
            title: 'أقسام القائمة',
            subtitle: 'اكتشف تشكيلتنا',
        },
        featured_products: {
            title: 'الأكثر طلباً',
            subtitle: 'منتجات مختارة',
            limit: 8,
        },
        rich_text: {
            eyebrow: 'قصتنا',
            title: 'عنوان القسم',
            content: 'اكتب المحتوى هنا...',
        },
        cta: {
            title: 'جاهز للطلب؟',
            subtitle: 'تصفّح القائمة الآن',
            cta_label: 'ابدأ الطلب',
            cta_href: '/products',
        },
        contact_channels: {
            title: 'قنوات التواصل',
            subtitle: '',
        },
        map: {
            title: 'موقعنا',
            subtitle: 'نرحب بزيارتك',
        },
        custom_html: {
            text: 'نص حر يظهر في الصفحة',
            align: 'center',
        },
    };

    return {
        id: createCmsSectionId(),
        type,
        variant,
        enabled: true,
        props: { ...defaults[type], ...props },
    };
}

export const CMS_SECTION_PROP_FIELDS: Partial<
    Record<
        CmsSectionType,
        Array<{ key: string; label: string; kind: 'text' | 'textarea' | 'toggle' | 'number' }>
    >
> = {
    hero: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
        { key: 'eyebrow', label: 'سطر علوي', kind: 'text' },
        { key: 'image', label: 'رابط الصورة', kind: 'text' },
        { key: 'cta_label', label: 'نص الزر', kind: 'text' },
        { key: 'cta_href', label: 'رابط الزر', kind: 'text' },
        { key: 'secondary_cta_label', label: 'نص الزر الثانوي', kind: 'text' },
        { key: 'secondary_cta_href', label: 'رابط الزر الثانوي', kind: 'text' },
        { key: 'show_cta', label: 'إظهار الأزرار', kind: 'toggle' },
        { key: 'show_hours', label: 'إظهار أوقات العمل', kind: 'toggle' },
        { key: 'hours_text', label: 'نص أوقات العمل', kind: 'text' },
    ],
    trust: [
        { key: 'title', label: 'عنوان (اختياري)', kind: 'text' },
        { key: 'subtitle', label: 'وصف (اختياري)', kind: 'text' },
    ],
    categories: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
    ],
    featured_products: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
        { key: 'limit', label: 'عدد المنتجات', kind: 'number' },
    ],
    rich_text: [
        { key: 'eyebrow', label: 'سطر علوي', kind: 'text' },
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'content', label: 'المحتوى', kind: 'textarea' },
    ],
    cta: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
        { key: 'cta_label', label: 'نص الزر', kind: 'text' },
        { key: 'cta_href', label: 'رابط الزر', kind: 'text' },
    ],
    contact_channels: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
    ],
    map: [
        { key: 'title', label: 'العنوان', kind: 'text' },
        { key: 'subtitle', label: 'الوصف', kind: 'textarea' },
    ],
    custom_html: [{ key: 'text', label: 'النص', kind: 'textarea' }],
};
