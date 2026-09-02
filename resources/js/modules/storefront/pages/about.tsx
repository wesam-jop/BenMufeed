import { Head, Link } from '@inertiajs/react';
import { Coffee, Heart, Leaf, Sparkles, Star } from 'lucide-react';
import CafeLocationSection, {
    shouldShowCafeMap,
} from '@/components/storefront/CafeLocationSection';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';

type AboutFeature = {
    title?: string;
    description?: string;
};

type Props = {
    business: StorefrontBusiness & {
        about_title?: string | null;
        about_content?: string | null;
        about_image?: string | null;
        about_features?: AboutFeature[];
        about_eyebrow?: string | null;
        about_why_title?: string | null;
        about_why_subtitle?: string | null;
        about_cta_title?: string | null;
        about_cta_subtitle?: string | null;
        tagline?: string | null;
        opening_hours?: string | null;
        meta_description?: string | null;
        map_lat?: number | null;
        map_lng?: number | null;
        map_title?: string | null;
        map_subtitle?: string | null;
        map_placements?: string[] | null;
        map_url?: string | null;
        contact_map_embed?: string | null;
    };
    navCategories?: NavCategory[];
};

const defaultHighlights = [
    { icon: Coffee, title: 'قهوة مختصة', description: 'حبوب مختارة وتحميص بعناية لتجربة مذاق استثنائية' },
    { icon: Leaf, title: 'مكونات طازجة', description: 'نختار أجود المكونات يومياً لضمان الجودة' },
    { icon: Heart, title: 'خدمة بروح', description: 'نهتم بكل تفصيلة لراحتك ورضاك' },
    { icon: Sparkles, title: 'أجواء مميزة', description: 'مكان يجمع الدفء والأناقة في كل زيارة' },
];

const featureIcons = [Star, Coffee, Leaf, Heart, Sparkles];

export default function AboutPage({ business, navCategories = [] }: Props) {
    const features = business.about_features ?? [];
    const title = business.about_title ?? `عن ${business.name}`;
    const content =
        business.about_content?.trim() ||
        business.tagline ||
        `${business.name} — مكانك المفضل للقهوة والأجواء الراقية.`;

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${title} — ${business.name}`}>
                {business.meta_description && (
                    <meta head-key="description" name="description" content={business.meta_description} />
                )}
            </Head>

            <section className="relative overflow-hidden bg-stone-950 text-white">
                {business.about_image && (
                    <img
                        src={business.about_image}
                        alt=""
                        className="absolute inset-0 size-full object-cover opacity-25"
                    />
                )}
                <div className="storefront-hero-glow absolute inset-0" />
                <div className="relative mx-auto max-w-6xl px-4 py-16 md:py-24">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="mb-5 inline-flex rounded-full bg-white/10 px-4 py-1.5 text-sm text-amber-100 backdrop-blur-sm">
                            {business.about_eyebrow ?? 'قصتنا'}
                        </span>
                        <h1 className="text-4xl font-bold tracking-tight md:text-5xl md:leading-tight">
                            {title}
                        </h1>
                        {business.tagline && (
                            <p className="mx-auto mt-5 max-w-xl text-lg text-stone-300">{business.tagline}</p>
                        )}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-14 md:py-20">
                <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
                    <div>
                        <p className="text-sm font-medium uppercase tracking-wider text-amber-800">من نحن</p>
                        <h2 className="mt-3 text-3xl font-bold tracking-tight text-stone-900 md:text-4xl">
                            {business.name}
                        </h2>
                        <div className="mt-6 space-y-4 text-base leading-relaxed text-stone-600 whitespace-pre-line">
                            {content}
                        </div>
                        {business.opening_hours && (
                            <p className="mt-6 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                أوقات العمل: {business.opening_hours}
                            </p>
                        )}
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button size="lg" className="bg-stone-900 hover:bg-stone-800" asChild>
                                <Link href="/products">تصفّح القائمة</Link>
                            </Button>
                            <Button size="lg" variant="outline" className="border-stone-200" asChild>
                                <Link href="/contact">تواصل معنا</Link>
                            </Button>
                        </div>
                    </div>

                    <div className="relative">
                        {business.about_image ? (
                            <div className="overflow-hidden rounded-3xl shadow-2xl ring-1 ring-stone-200/60">
                                <img
                                    src={business.about_image}
                                    alt={title}
                                    className="aspect-[4/5] w-full object-cover md:aspect-[5/6]"
                                />
                            </div>
                        ) : (
                            <div className="flex aspect-[4/5] items-center justify-center rounded-3xl bg-gradient-to-br from-amber-900 via-stone-800 to-stone-950 text-white shadow-2xl md:aspect-[5/6]">
                                <div className="text-center">
                                    <Coffee className="mx-auto size-16 text-amber-200" />
                                    <p className="mt-4 text-2xl font-bold">{business.name}</p>
                                </div>
                            </div>
                        )}
                        <div className="absolute -bottom-4 -start-4 hidden rounded-2xl bg-white px-5 py-4 shadow-lg ring-1 ring-stone-200/80 sm:block">
                            <p className="text-sm font-semibold text-stone-900">تجربة ضيافة راقية</p>
                            <p className="mt-0.5 text-xs text-stone-500">منذ انطلاقتنا ونحن نهتم بالتفاصيل</p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="border-y border-stone-200/80 bg-stone-50/80 py-14 md:py-20">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="mx-auto mb-10 max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-stone-900">
                            {business.about_why_title ?? 'لماذا نحن؟'}
                        </h2>
                        <p className="mt-3 text-stone-500">
                            {business.about_why_subtitle ?? 'قيمنا التي نصنع بها كل كوب وكل زيارة'}
                        </p>
                    </div>

                    {features.length > 0 ? (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature, index) => {
                                const Icon = featureIcons[index % featureIcons.length];

                                return (
                                    <div
                                        key={index}
                                        className="group rounded-3xl bg-white p-6 shadow-sm ring-1 ring-stone-200/80 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-amber-200/50"
                                    >
                                        <span className="flex size-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-800 transition-colors group-hover:bg-amber-100">
                                            <Icon className="size-5" />
                                        </span>
                                        <h3 className="mt-4 text-lg font-semibold text-stone-900">
                                            {feature.title}
                                        </h3>
                                        {feature.description && (
                                            <p className="mt-2 text-sm leading-relaxed text-stone-500">
                                                {feature.description}
                                            </p>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {defaultHighlights.map(({ icon: Icon, title: itemTitle, description }) => (
                                <div
                                    key={itemTitle}
                                    className="group rounded-3xl bg-white p-6 shadow-sm ring-1 ring-stone-200/80 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-amber-200/50"
                                >
                                    <span className="flex size-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-800">
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="mt-4 text-lg font-semibold text-stone-900">{itemTitle}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-stone-500">{description}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            {shouldShowCafeMap(business.map_placements, 'about') && (
                <CafeLocationSection
                    lat={business.map_lat}
                    lng={business.map_lng}
                    title={business.map_title}
                    subtitle={business.map_subtitle}
                    address={business.address}
                    mapUrl={business.map_url}
                    embedHtml={business.contact_map_embed}
                    className="pb-4 md:pb-8"
                />
            )}

            <section className="mx-auto max-w-6xl px-4 py-14 md:py-20">
                <div className="overflow-hidden rounded-3xl bg-gradient-to-bl from-amber-900 to-stone-900 px-8 py-12 text-center text-white shadow-xl md:px-12 md:py-16">
                    <h2 className="text-3xl font-bold md:text-4xl">
                        {business.about_cta_title ?? `جاهز لتجربة ${business.name}؟`}
                    </h2>
                    <p className="mx-auto mt-4 max-w-lg text-stone-300">
                        {business.about_cta_subtitle ??
                            'تصفّح قائمتنا واطلب ما تحب — أو تواصل معنا لأي استفسار'}
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Button size="lg" className="h-11 bg-white px-7 text-stone-900 hover:bg-stone-100" asChild>
                            <Link href="/products">اطلب الآن</Link>
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            className="h-11 border-white/30 bg-white/5 px-7 text-white hover:bg-white/10"
                            asChild
                        >
                            <Link href="/categories">الأقسام</Link>
                        </Button>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
