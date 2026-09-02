import { Head, Link, useForm } from '@inertiajs/react';
import { Globe, ImageIcon, Layout, MessageSquare, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ImageUploadField from '@/components/admin/image-upload-field';
import PageHeader from '@/components/admin/page-header';
import DeliveryLocationMap from '@/components/domain/DeliveryLocationMap';
import StatCard from '@/components/domain/StatCard';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type AboutFeature = {
    title?: string;
    description?: string;
};

type TrustItem = {
    title?: string;
};

type SiteSettings = {
    id: number;
    business_name?: string | null;
    tagline?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
    whatsapp?: string | null;
    instagram?: string | null;
    facebook?: string | null;
    tiktok?: string | null;
    tax_number?: string | null;
    opening_hours?: string | null;
    store_opens_at?: string | null;
    store_closes_at?: string | null;
    hero_title?: string | null;
    hero_subtitle?: string | null;
    homepage_sections?: string[] | null;
    show_featured_products?: boolean;
    featured_products_limit?: number;
    trust_items?: TrustItem[] | null;
    categories_section_title?: string | null;
    categories_section_subtitle?: string | null;
    featured_section_title?: string | null;
    featured_section_subtitle?: string | null;
    contact_preview_title?: string | null;
    contact_preview_subtitle?: string | null;
    categories_page_title?: string | null;
    categories_page_subtitle?: string | null;
    products_page_title?: string | null;
    products_page_subtitle?: string | null;
    about_title?: string | null;
    about_content?: string | null;
    about_features?: AboutFeature[] | null;
    about_eyebrow?: string | null;
    about_why_title?: string | null;
    about_why_subtitle?: string | null;
    about_cta_title?: string | null;
    about_cta_subtitle?: string | null;
    contact_title?: string | null;
    contact_content?: string | null;
    contact_map_embed?: string | null;
    contact_form_enabled?: boolean;
    contact_eyebrow?: string | null;
    map_lat?: number | string | null;
    map_lng?: number | string | null;
    map_title?: string | null;
    map_subtitle?: string | null;
    map_placements?: string[] | null;
    meta_title?: string | null;
    meta_description?: string | null;
    logo_url?: string | null;
    hero_image_url?: string | null;
    about_image_url?: string | null;
    categories_page_image_url?: string | null;
    products_page_image_url?: string | null;
};

type Summary = {
    unread_messages: number;
    total_messages: number;
    homepage_sections_count: number;
    has_logo: boolean;
    has_hero_image: boolean;
    has_about_image: boolean;
    contact_form_enabled: boolean;
    show_featured_products: boolean;
    updated_at: string | null;
};

type Props = {
    settings: SiteSettings;
    summary: Summary;
};

const sectionOptions = [
    { value: 'hero', label: 'البانر الرئيسي' },
    { value: 'categories', label: 'الأقسام' },
    { value: 'featured', label: 'المنتجات المميزة' },
    { value: 'about_preview', label: 'معاينة عن المقهى' },
    { value: 'contact_preview', label: 'معاينة التواصل' },
];

const tabs = [
    { id: 'general', label: 'عام' },
    { id: 'branding', label: 'الشعار والصور' },
    { id: 'homepage', label: 'الصفحة الرئيسية' },
    { id: 'pages', label: 'صفحات القائمة' },
    { id: 'about', label: 'عن المقهى' },
    { id: 'contact', label: 'التواصل' },
    { id: 'seo', label: 'SEO' },
];

const fieldClass =
    'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm';

export default function SiteSettingsPage({ settings, summary }: Props) {
    const [tab, setTab] = useState('general');
    const { data, setData, post, processing, errors } = useForm({
        business_name: settings.business_name ?? '',
        tagline: settings.tagline ?? '',
        phone: settings.phone ?? '',
        email: settings.email ?? '',
        address: settings.address ?? '',
        whatsapp: settings.whatsapp ?? '',
        instagram: settings.instagram ?? '',
        facebook: settings.facebook ?? '',
        tiktok: settings.tiktok ?? '',
        tax_number: settings.tax_number ?? '',
        opening_hours: settings.opening_hours ?? '',
        store_opens_at: (settings.store_opens_at ?? '10:00').toString().slice(0, 5),
        store_closes_at: (settings.store_closes_at ?? '21:00').toString().slice(0, 5),
        hero_title: settings.hero_title ?? '',
        hero_subtitle: settings.hero_subtitle ?? '',
        homepage_sections: settings.homepage_sections ?? ['hero', 'categories', 'featured', 'about_preview'],
        show_featured_products: settings.show_featured_products ?? true,
        featured_products_limit: settings.featured_products_limit ?? 8,
        trust_items: settings.trust_items ?? [
            { title: 'مكونات طازجة يومياً' },
            { title: 'توصيل سريع وموثوق' },
            { title: 'جودة مضمونة' },
            { title: 'تجربة طلب سلسة' },
        ],
        categories_section_title: settings.categories_section_title ?? '',
        categories_section_subtitle: settings.categories_section_subtitle ?? '',
        featured_section_title: settings.featured_section_title ?? '',
        featured_section_subtitle: settings.featured_section_subtitle ?? '',
        contact_preview_title: settings.contact_preview_title ?? '',
        contact_preview_subtitle: settings.contact_preview_subtitle ?? '',
        categories_page_title: settings.categories_page_title ?? '',
        categories_page_subtitle: settings.categories_page_subtitle ?? '',
        products_page_title: settings.products_page_title ?? '',
        products_page_subtitle: settings.products_page_subtitle ?? '',
        about_title: settings.about_title ?? '',
        about_content: settings.about_content ?? '',
        about_features: settings.about_features ?? [],
        about_eyebrow: settings.about_eyebrow ?? '',
        about_why_title: settings.about_why_title ?? '',
        about_why_subtitle: settings.about_why_subtitle ?? '',
        about_cta_title: settings.about_cta_title ?? '',
        about_cta_subtitle: settings.about_cta_subtitle ?? '',
        contact_title: settings.contact_title ?? '',
        contact_content: settings.contact_content ?? '',
        contact_map_embed: settings.contact_map_embed ?? '',
        contact_form_enabled: settings.contact_form_enabled ?? false,
        contact_eyebrow: settings.contact_eyebrow ?? '',
        map_lat: settings.map_lat != null ? String(settings.map_lat) : '',
        map_lng: settings.map_lng != null ? String(settings.map_lng) : '',
        map_title: settings.map_title ?? '',
        map_subtitle: settings.map_subtitle ?? '',
        map_placements: settings.map_placements ?? ['contact', 'home'],
        clear_map_location: false,
        meta_title: settings.meta_title ?? '',
        meta_description: settings.meta_description ?? '',
        logo: null as File | null,
        hero_image: null as File | null,
        about_image: null as File | null,
        categories_page_image: null as File | null,
        products_page_image: null as File | null,
        remove_categories_page_image: false,
        remove_products_page_image: false,
        remove_logo: false,
        remove_hero_image: false,
        remove_about_image: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/site', { forceFormData: true, preserveScroll: true });
    };

    const toggleMapPlacement = (value: string) => {
        const current = data.map_placements ?? [];
        if (current.includes(value)) {
            setData(
                'map_placements',
                current.filter((item) => item !== value),
            );
        } else {
            setData('map_placements', [...current, value]);
        }
    };

    const toggleSection = (value: string) => {
        const current = data.homepage_sections ?? [];
        if (current.includes(value)) {
            setData(
                'homepage_sections',
                current.filter((s) => s !== value),
            );
        } else {
            setData('homepage_sections', [...current, value]);
        }
    };

    const updateFeature = (index: number, field: keyof AboutFeature, value: string) => {
        const features = [...(data.about_features ?? [])];
        features[index] = { ...features[index], [field]: value };
        setData('about_features', features);
    };

    const addFeature = () => {
        setData('about_features', [...(data.about_features ?? []), { title: '', description: '' }]);
    };

    const removeFeature = (index: number) => {
        setData(
            'about_features',
            (data.about_features ?? []).filter((_, i) => i !== index),
        );
    };

    const updateTrustItem = (index: number, value: string) => {
        const items = [...(data.trust_items ?? [])];
        items[index] = { title: value };
        setData('trust_items', items);
    };

    const addTrustItem = () => {
        setData('trust_items', [...(data.trust_items ?? []), { title: '' }]);
    };

    const removeTrustItem = (index: number) => {
        setData(
            'trust_items',
            (data.trust_items ?? []).filter((_, i) => i !== index),
        );
    };

    return (
        <>
            <Head title="إدارة الموقع" />
            <PageHeader
                title="إدارة الموقع"
                description="تحكم بالنصوص والصور لكل صفحة وقسم في الموقع من مكان واحد"
                actions={
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/" target="_blank">
                                <Globe className="size-4" />
                                معاينة الموقع
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/admin/site/messages">
                                <MessageSquare className="size-4" />
                                رسائل التواصل
                                {summary.unread_messages > 0 && (
                                    <Badge variant="destructive" className="ms-2">
                                        {summary.unread_messages}
                                    </Badge>
                                )}
                            </Link>
                        </Button>
                    </div>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title="رسائل جديدة"
                    value={summary.unread_messages}
                    format="number"
                    icon={MessageSquare}
                    description={`${summary.total_messages} إجمالي`}
                />
                <StatCard
                    title="أقسام الرئيسية"
                    value={summary.homepage_sections_count}
                    format="number"
                    icon={Layout}
                />
                <StatCard
                    title="الشعار والصور"
                    value={
                        [summary.has_logo, summary.has_hero_image, summary.has_about_image].filter(
                            Boolean,
                        ).length
                    }
                    format="number"
                    icon={ImageIcon}
                    description="من 3 مرفوعة"
                />
                <StatCard
                    title="قنوات التواصل"
                    value={
                        [
                            Boolean(settings.whatsapp),
                            Boolean(settings.facebook),
                            Boolean(settings.instagram),
                        ].filter(Boolean).length
                    }
                    format="number"
                    icon={MessageSquare}
                    description="من 3 (واتساب / فيسبوك / انستغرام)"
                />
            </div>

            {summary.updated_at && (
                <p className="mb-4 text-sm text-muted-foreground">
                    آخر تحديث: {formatDate(summary.updated_at)}
                </p>
            )}

            <form onSubmit={submit}>
                <div className="mb-4 flex flex-wrap gap-2">
                    {tabs.map((item) => (
                        <Button
                            key={item.id}
                            type="button"
                            variant={tab === item.id ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setTab(item.id)}
                            className={cn(tab === item.id && 'bg-primary')}
                        >
                            {item.label}
                        </Button>
                    ))}
                </div>

                {tab === 'general' && (
                    <Card>
                        <CardContent className="grid max-w-2xl gap-4 pt-6">
                            {[
                                ['business_name', 'اسم المقهى'],
                                ['tagline', 'الشعار النصي'],
                                ['phone', 'الهاتف'],
                                ['email', 'البريد'],
                                ['address', 'العنوان'],
                                ['whatsapp', 'واتساب'],
                                ['instagram', 'انستغرام'],
                                ['facebook', 'فيسبوك'],
                                ['tiktok', 'تيك توك'],
                                    ['tax_number', 'الرقم الضريبي'],
                                    ['opening_hours', 'ساعات العمل (نص للعرض)'],
                                ].map(([key, label]) => (
                                    <div key={key} className="grid gap-2">
                                        <Label htmlFor={key}>{label}</Label>
                                        <Input
                                            id={key}
                                            value={String(data[key as keyof typeof data] ?? '')}
                                            onChange={(e) =>
                                                setData(key as keyof typeof data, e.target.value as never)
                                            }
                                        />
                                        <InputError message={errors[key as keyof typeof errors]} />
                                    </div>
                                ))}
                                <div className="grid gap-4 border-t pt-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="store_opens_at">وقت فتح التوصيل</Label>
                                        <Input
                                            id="store_opens_at"
                                            type="time"
                                            value={data.store_opens_at}
                                            onChange={(e) => setData('store_opens_at', e.target.value)}
                                            dir="ltr"
                                        />
                                        <InputError message={errors.store_opens_at} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="store_closes_at">وقت إغلاق التوصيل</Label>
                                        <Input
                                            id="store_closes_at"
                                            type="time"
                                            value={data.store_closes_at}
                                            onChange={(e) => setData('store_closes_at', e.target.value)}
                                            dir="ltr"
                                        />
                                        <InputError message={errors.store_closes_at} />
                                    </div>
                                    <p className="text-sm text-muted-foreground sm:col-span-2">
                                        خارج هذه الفترة يُمنع طلب التوصيل من الموقع فقط. باقي صفحات الموقع تبقى متاحة.
                                    </p>
                                </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'branding' && (
                    <Card>
                        <CardContent className="grid max-w-3xl gap-5 pt-6">
                            {(
                                [
                                    {
                                        key: 'logo' as const,
                                        label: 'الشعار',
                                        hint: 'يفضّل مربع شفاف أو خلفية نظيفة',
                                        preview: settings.logo_url,
                                        removeKey: 'remove_logo' as const,
                                        aspectClass: 'aspect-square max-w-[220px]',
                                        objectFit: 'contain' as const,
                                    },
                                    {
                                        key: 'hero_image' as const,
                                        label: 'صورة البانر (الرئيسية)',
                                        hint: 'صورة عريضة للصفحة الرئيسية',
                                        preview: settings.hero_image_url,
                                        removeKey: 'remove_hero_image' as const,
                                        aspectClass: 'aspect-[16/9]',
                                        objectFit: 'cover' as const,
                                    },
                                    {
                                        key: 'about_image' as const,
                                        label: 'صورة صفحة عن المقهى',
                                        hint: 'تظهر في صفحة من نحن',
                                        preview: settings.about_image_url,
                                        removeKey: 'remove_about_image' as const,
                                        aspectClass: 'aspect-[16/9]',
                                        objectFit: 'cover' as const,
                                    },
                                    {
                                        key: 'categories_page_image' as const,
                                        label: 'صورة صفحة الأقسام',
                                        hint: 'بانر أعلى صفحة الأقسام',
                                        preview: settings.categories_page_image_url,
                                        removeKey: 'remove_categories_page_image' as const,
                                        aspectClass: 'aspect-[16/9]',
                                        objectFit: 'cover' as const,
                                    },
                                    {
                                        key: 'products_page_image' as const,
                                        label: 'صورة صفحة المنتجات',
                                        hint: 'بانر أعلى صفحة المنتجات',
                                        preview: settings.products_page_image_url,
                                        removeKey: 'remove_products_page_image' as const,
                                        aspectClass: 'aspect-[16/9]',
                                        objectFit: 'cover' as const,
                                    },
                                ] as const
                            ).map((field) => {
                                const markedRemove = Boolean(data[field.removeKey]);
                                const preview = markedRemove ? null : field.preview;

                                return (
                                    <div key={field.key} className="grid gap-2 border-b border-border/60 pb-5 last:border-b-0 last:pb-0">
                                        <div>
                                            <Label className="text-sm font-medium">{field.label}</Label>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {field.hint}
                                            </p>
                                        </div>
                                        <ImageUploadField
                                            value={data[field.key]}
                                            previewUrl={preview}
                                            aspectClass={field.aspectClass}
                                            objectFit={field.objectFit}
                                            title={`اسحب ${field.label} هنا`}
                                            hint="PNG أو JPG — حتى 4 ميجابايت"
                                            onChange={(file) => {
                                                setData(field.key, file);
                                                if (file) {
                                                    setData(field.removeKey, false);
                                                }
                                            }}
                                            onClearExisting={() => setData(field.removeKey, true)}
                                        />
                                        <InputError message={errors[field.key]} />
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                )}

                {tab === 'homepage' && (
                    <Card>
                        <CardContent className="grid max-w-2xl gap-6 pt-6">
                            <div className="grid gap-2">
                                <Label htmlFor="hero_title">عنوان البانر</Label>
                                <Input
                                    id="hero_title"
                                    value={data.hero_title}
                                    onChange={(e) => setData('hero_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="hero_subtitle">وصف البانر</Label>
                                <textarea
                                    id="hero_subtitle"
                                    rows={3}
                                    className={fieldClass}
                                    value={data.hero_subtitle}
                                    onChange={(e) => setData('hero_subtitle', e.target.value)}
                                />
                            </div>

                            <div className="grid gap-3 border-t pt-4">
                                <div className="flex items-center justify-between">
                                    <Label>شريط الثقة (تحت البانر)</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addTrustItem}
                                        disabled={(data.trust_items ?? []).length >= 8}
                                    >
                                        <Plus className="size-4" />
                                        إضافة
                                    </Button>
                                </div>
                                {(data.trust_items ?? []).map((item, index) => (
                                    <div key={index} className="flex gap-2">
                                        <Input
                                            placeholder="نص العنصر"
                                            value={item.title ?? ''}
                                            onChange={(e) => updateTrustItem(index, e.target.value)}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="shrink-0 text-destructive"
                                            onClick={() => removeTrustItem(index)}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>

                            <div className="grid gap-4 border-t pt-4">
                                <p className="text-sm font-medium">عناوين أقسام الصفحة الرئيسية</p>
                                {(
                                    [
                                        ['categories_section_title', 'عنوان قسم الأقسام'],
                                        ['categories_section_subtitle', 'وصف قسم الأقسام'],
                                        ['featured_section_title', 'عنوان المنتجات المميزة'],
                                        ['featured_section_subtitle', 'وصف المنتجات المميزة'],
                                        ['contact_preview_title', 'عنوان معاينة التواصل'],
                                        ['contact_preview_subtitle', 'وصف معاينة التواصل'],
                                    ] as const
                                ).map(([key, label]) => (
                                    <div key={key} className="grid gap-2">
                                        <Label htmlFor={key}>{label}</Label>
                                        <Input
                                            id={key}
                                            value={data[key]}
                                            onChange={(e) => setData(key, e.target.value)}
                                        />
                                    </div>
                                ))}
                            </div>

                            <div className="grid gap-2 border-t pt-4">
                                <Label>أقسام الصفحة الرئيسية</Label>
                                <div className="grid gap-2">
                                    {sectionOptions.map((section) => (
                                        <label key={section.value} className="flex items-center gap-2">
                                            <Checkbox
                                                checked={(data.homepage_sections ?? []).includes(
                                                    section.value,
                                                )}
                                                onCheckedChange={() => toggleSection(section.value)}
                                            />
                                            <span>{section.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <label className="flex items-center gap-2">
                                <Checkbox
                                    checked={data.show_featured_products}
                                    onCheckedChange={(v) =>
                                        setData('show_featured_products', Boolean(v))
                                    }
                                />
                                <span>إظهار المنتجات المميزة</span>
                            </label>
                            <div className="grid gap-2">
                                <Label htmlFor="featured_products_limit">عدد المنتجات المميزة</Label>
                                <Input
                                    id="featured_products_limit"
                                    type="number"
                                    min={1}
                                    max={24}
                                    value={data.featured_products_limit}
                                    onChange={(e) =>
                                        setData('featured_products_limit', Number(e.target.value))
                                    }
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'pages' && (
                    <Card>
                        <CardContent className="grid max-w-2xl gap-6 pt-6">
                            <div className="grid gap-4">
                                <p className="text-sm font-medium">صفحة الأقسام</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="categories_page_title">العنوان</Label>
                                    <Input
                                        id="categories_page_title"
                                        value={data.categories_page_title}
                                        onChange={(e) =>
                                            setData('categories_page_title', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="categories_page_subtitle">الوصف</Label>
                                    <textarea
                                        id="categories_page_subtitle"
                                        rows={2}
                                        className={fieldClass}
                                        value={data.categories_page_subtitle}
                                        onChange={(e) =>
                                            setData('categories_page_subtitle', e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 border-t pt-4">
                                <p className="text-sm font-medium">صفحة المنتجات</p>
                                <div className="grid gap-2">
                                    <Label htmlFor="products_page_title">العنوان</Label>
                                    <Input
                                        id="products_page_title"
                                        value={data.products_page_title}
                                        onChange={(e) => setData('products_page_title', e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="products_page_subtitle">الوصف</Label>
                                    <textarea
                                        id="products_page_subtitle"
                                        rows={2}
                                        className={fieldClass}
                                        value={data.products_page_subtitle}
                                        onChange={(e) =>
                                            setData('products_page_subtitle', e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                صور الصفحات تُرفع من تبويب «الشعار والصور».
                            </p>
                        </CardContent>
                    </Card>
                )}

                {tab === 'about' && (
                    <Card>
                        <CardContent className="grid max-w-2xl gap-4 pt-6">
                            <div className="grid gap-2">
                                <Label htmlFor="about_eyebrow">نص فوق العنوان</Label>
                                <Input
                                    id="about_eyebrow"
                                    value={data.about_eyebrow}
                                    onChange={(e) => setData('about_eyebrow', e.target.value)}
                                    placeholder="قصتنا"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="about_title">عنوان الصفحة</Label>
                                <Input
                                    id="about_title"
                                    value={data.about_title}
                                    onChange={(e) => setData('about_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="about_content">المحتوى</Label>
                                <textarea
                                    id="about_content"
                                    rows={8}
                                    className={cn(fieldClass, 'min-h-[160px]')}
                                    value={data.about_content}
                                    onChange={(e) => setData('about_content', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2 border-t pt-4">
                                <Label htmlFor="about_why_title">عنوان قسم لماذا نحن</Label>
                                <Input
                                    id="about_why_title"
                                    value={data.about_why_title}
                                    onChange={(e) => setData('about_why_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="about_why_subtitle">وصف قسم لماذا نحن</Label>
                                <Input
                                    id="about_why_subtitle"
                                    value={data.about_why_subtitle}
                                    onChange={(e) => setData('about_why_subtitle', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="about_cta_title">عنوان الدعوة للإجراء</Label>
                                <Input
                                    id="about_cta_title"
                                    value={data.about_cta_title}
                                    onChange={(e) => setData('about_cta_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="about_cta_subtitle">وصف الدعوة للإجراء</Label>
                                <textarea
                                    id="about_cta_subtitle"
                                    rows={2}
                                    className={fieldClass}
                                    value={data.about_cta_subtitle}
                                    onChange={(e) => setData('about_cta_subtitle', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-3 border-t pt-4">
                                <div className="flex items-center justify-between">
                                    <Label>المميزات</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addFeature}>
                                        <Plus className="size-4" />
                                        إضافة
                                    </Button>
                                </div>
                                {(data.about_features ?? []).map((feature, index) => (
                                    <div key={index} className="grid gap-2 rounded-lg border p-3">
                                        <Input
                                            placeholder="العنوان"
                                            value={feature.title ?? ''}
                                            onChange={(e) => updateFeature(index, 'title', e.target.value)}
                                        />
                                        <Input
                                            placeholder="الوصف"
                                            value={feature.description ?? ''}
                                            onChange={(e) =>
                                                updateFeature(index, 'description', e.target.value)
                                            }
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="justify-start text-destructive"
                                            onClick={() => removeFeature(index)}
                                        >
                                            <Trash2 className="size-4" />
                                            حذف
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'contact' && (
                    <Card>
                        <CardContent className="grid max-w-3xl gap-4 pt-6">
                            <p className="text-sm text-muted-foreground">
                                صفحة التواصل تعرض قنوات واتساب وفيسبوك وانستغرام فقط — بدون نموذج رسائل.
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="whatsapp">رقم واتساب</Label>
                                <Input
                                    id="whatsapp"
                                    value={data.whatsapp}
                                    onChange={(e) => setData('whatsapp', e.target.value)}
                                    placeholder="9665xxxxxxxx"
                                    dir="ltr"
                                />
                                <InputError message={errors.whatsapp} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="facebook">فيسبوك (رابط أو اسم الصفحة)</Label>
                                <Input
                                    id="facebook"
                                    value={data.facebook}
                                    onChange={(e) => setData('facebook', e.target.value)}
                                    placeholder="https://facebook.com/..."
                                    dir="ltr"
                                />
                                <InputError message={errors.facebook} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="instagram">انستغرام (رابط أو اسم المستخدم)</Label>
                                <Input
                                    id="instagram"
                                    value={data.instagram}
                                    onChange={(e) => setData('instagram', e.target.value)}
                                    placeholder="@username"
                                    dir="ltr"
                                />
                                <InputError message={errors.instagram} />
                            </div>
                            <div className="grid gap-2 border-t pt-4">
                                <Label htmlFor="contact_eyebrow">نص فوق العنوان</Label>
                                <Input
                                    id="contact_eyebrow"
                                    value={data.contact_eyebrow}
                                    onChange={(e) => setData('contact_eyebrow', e.target.value)}
                                    placeholder="نحن هنا لمساعدتك"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="contact_title">عنوان الصفحة</Label>
                                <Input
                                    id="contact_title"
                                    value={data.contact_title}
                                    onChange={(e) => setData('contact_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="contact_content">وصف الصفحة</Label>
                                <textarea
                                    id="contact_content"
                                    rows={3}
                                    className={cn(fieldClass, 'min-h-[80px]')}
                                    value={data.contact_content}
                                    onChange={(e) => setData('contact_content', e.target.value)}
                                />
                            </div>

                            <div className="grid gap-4 border-t pt-4">
                                <div>
                                    <Label>موقع الكافيه على الخريطة</Label>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        اضغط على الخريطة لتحديد الموقع. سيظهر القسم في الصفحات التي تختارها أدناه.
                                    </p>
                                </div>
                                <DeliveryLocationMap
                                    lat={data.map_lat || null}
                                    lng={data.map_lng || null}
                                    hint="اضغط على الخريطة أو اسحب الدبوس لتحديد موقع الكافيه"
                                    onLocationChange={(lat, lng, address) => {
                                        setData('map_lat', String(lat));
                                        setData('map_lng', String(lng));
                                        setData('clear_map_location', false);
                                        if (address && !data.address) {
                                            setData('address', address);
                                        }
                                    }}
                                />
                                {(data.map_lat || data.map_lng) && (
                                    <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                        <span dir="ltr">
                                            {data.map_lat}, {data.map_lng}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => {
                                                setData('map_lat', '');
                                                setData('map_lng', '');
                                                setData('clear_map_location', true);
                                            }}
                                        >
                                            <Trash2 className="size-4" />
                                            مسح الموقع
                                        </Button>
                                    </div>
                                )}
                                <InputError message={errors.map_lat || errors.map_lng} />
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="map_title">عنوان قسم الخريطة</Label>
                                        <Input
                                            id="map_title"
                                            value={data.map_title}
                                            onChange={(e) => setData('map_title', e.target.value)}
                                            placeholder="موقعنا على الخريطة"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="map_subtitle">وصف قسم الخريطة</Label>
                                        <Input
                                            id="map_subtitle"
                                            value={data.map_subtitle}
                                            onChange={(e) => setData('map_subtitle', e.target.value)}
                                            placeholder="تفضل بزيارتنا في أقرب وقت"
                                        />
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <Label>إظهار قسم الخريطة في</Label>
                                    <div className="grid gap-2 sm:grid-cols-3">
                                        {[
                                            { value: 'home', label: 'الصفحة الرئيسية' },
                                            { value: 'about', label: 'عن المقهى' },
                                            { value: 'contact', label: 'تواصل معنا' },
                                        ].map((placement) => (
                                            <label
                                                key={placement.value}
                                                className="flex items-center gap-2 rounded-lg border px-3 py-2"
                                            >
                                                <Checkbox
                                                    checked={(data.map_placements ?? []).includes(
                                                        placement.value,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleMapPlacement(placement.value)
                                                    }
                                                />
                                                <span className="text-sm">{placement.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-2 border-t pt-4">
                                <Label htmlFor="contact_map_embed">
                                    كود iframe مخصص (اختياري — بديل عن الإحداثيات)
                                </Label>
                                <textarea
                                    id="contact_map_embed"
                                    rows={3}
                                    className={cn(fieldClass, 'min-h-[80px] font-mono text-xs')}
                                    value={data.contact_map_embed}
                                    onChange={(e) => setData('contact_map_embed', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                {tab === 'seo' && (
                    <Card>
                        <CardContent className="grid max-w-2xl gap-4 pt-6">
                            <div className="grid gap-2">
                                <Label htmlFor="meta_title">عنوان SEO</Label>
                                <Input
                                    id="meta_title"
                                    value={data.meta_title}
                                    onChange={(e) => setData('meta_title', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="meta_description">وصف SEO</Label>
                                <textarea
                                    id="meta_description"
                                    rows={3}
                                    className={fieldClass}
                                    value={data.meta_description}
                                    onChange={(e) => setData('meta_description', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="mt-4">
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        <Save className="size-4" />
                        حفظ إعدادات الموقع
                    </Button>
                </div>
            </form>
        </>
    );
}
