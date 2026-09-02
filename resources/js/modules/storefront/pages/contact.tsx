import { Head } from '@inertiajs/react';
import { Clock, Facebook, Instagram, MapPin, MessageCircle, Phone } from 'lucide-react';
import CafeLocationSection, {
    shouldShowCafeMap,
} from '@/components/storefront/CafeLocationSection';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';

type Props = {
    business: StorefrontBusiness & {
        contact_title?: string | null;
        contact_content?: string | null;
        contact_map_embed?: string | null;
        contact_eyebrow?: string | null;
        opening_hours?: string | null;
        meta_description?: string | null;
        map_lat?: number | null;
        map_lng?: number | null;
        map_title?: string | null;
        map_subtitle?: string | null;
        map_placements?: string[] | null;
        map_url?: string | null;
    };
    navCategories?: NavCategory[];
};

function socialUrl(
    value: string | null | undefined,
    kind: 'instagram' | 'facebook',
): string | null {
    if (!value?.trim()) {
        return null;
    }

    if (value.startsWith('http')) {
        return value;
    }

    if (kind === 'instagram') {
        return `https://instagram.com/${value.replace(/^@/, '')}`;
    }

    return `https://facebook.com/${value}`;
}

export default function ContactPage({ business, navCategories = [] }: Props) {
    const whatsappDigits = business.whatsapp?.replace(/\D/g, '') ?? '';
    const whatsappUrl = whatsappDigits ? `https://wa.me/${whatsappDigits}` : null;
    const instagramUrl = socialUrl(business.instagram, 'instagram');
    const facebookUrl = socialUrl(business.facebook, 'facebook');

    const title = business.contact_title ?? 'تواصل معنا';
    const subtitle =
        business.contact_content ??
        'تواصل معنا مباشرة عبر واتساب أو تابعنا على فيسبوك وانستغرام.';

    const channels = [
        whatsappUrl && {
            key: 'whatsapp',
            href: whatsappUrl,
            icon: MessageCircle,
            title: 'واتساب',
            description: 'راسلنا مباشرة وسنرد عليك بأسرع وقت',
            cta: 'فتح واتساب',
            accent: 'bg-emerald-50 text-emerald-700 ring-emerald-100 hover:bg-emerald-100',
            button: 'bg-emerald-700 hover:bg-emerald-800',
        },
        facebookUrl && {
            key: 'facebook',
            href: facebookUrl,
            icon: Facebook,
            title: 'فيسبوك',
            description: 'تابع صفحتنا وتواصل معنا عبر الرسائل',
            cta: 'فتح فيسبوك',
            accent: 'bg-blue-50 text-blue-700 ring-blue-100 hover:bg-blue-100',
            button: 'bg-[#1877F2] hover:bg-[#166FE5]',
        },
        instagramUrl && {
            key: 'instagram',
            href: instagramUrl,
            icon: Instagram,
            title: 'انستغرام',
            description: 'شاهد جديدنا وتواصل معنا عبر الرسائل',
            cta: 'فتح انستغرام',
            accent: 'bg-pink-50 text-pink-700 ring-pink-100 hover:bg-pink-100',
            button: 'bg-gradient-to-l from-amber-600 via-pink-600 to-purple-700 hover:opacity-95',
        },
    ].filter(Boolean) as Array<{
        key: string;
        href: string;
        icon: typeof MessageCircle;
        title: string;
        description: string;
        cta: string;
        accent: string;
        button: string;
    }>;

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${title} — ${business.name}`}>
                {business.meta_description && (
                    <meta
                        head-key="description"
                        name="description"
                        content={business.meta_description}
                    />
                )}
            </Head>

            <section className="relative overflow-hidden bg-stone-950 text-white">
                <div className="storefront-hero-glow absolute inset-0" />
                <div className="relative mx-auto max-w-6xl px-4 py-16 md:py-20">
                    <div className="mx-auto max-w-2xl text-center">
                        <span className="mb-5 inline-flex rounded-full bg-white/10 px-4 py-1.5 text-sm text-amber-100 backdrop-blur-sm">
                            {business.contact_eyebrow ?? 'نحن هنا لمساعدتك'}
                        </span>
                        <h1 className="text-4xl font-bold tracking-tight md:text-5xl">{title}</h1>
                        <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-stone-300 md:text-lg">
                            {subtitle}
                        </p>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 py-12 md:py-16">
                {channels.length > 0 ? (
                    <div className="grid gap-5 md:grid-cols-3">
                        {channels.map((channel) => {
                            const Icon = channel.icon;

                            return (
                                <a
                                    key={channel.key}
                                    href={channel.href}
                                    target="_blank"
                                    rel="noreferrer"
                                    className={`group flex flex-col rounded-3xl p-6 ring-1 transition-all hover:-translate-y-0.5 hover:shadow-lg ${channel.accent}`}
                                >
                                    <span className="flex size-12 items-center justify-center rounded-2xl bg-white/80 shadow-sm">
                                        <Icon className="size-6" />
                                    </span>
                                    <h2 className="mt-5 text-xl font-bold text-stone-900">
                                        {channel.title}
                                    </h2>
                                    <p className="mt-2 flex-1 text-sm leading-relaxed text-stone-600">
                                        {channel.description}
                                    </p>
                                    <span
                                        className={`mt-6 inline-flex h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold text-white ${channel.button}`}
                                    >
                                        {channel.cta}
                                    </span>
                                </a>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-3xl bg-stone-50 py-16 text-center ring-1 ring-stone-200/80">
                        <MessageCircle className="mx-auto size-10 text-stone-300" />
                        <p className="mt-4 text-lg font-medium text-stone-800">
                            قنوات التواصل غير مضافة بعد
                        </p>
                        <p className="mt-2 text-sm text-stone-500">
                            أضف رقم واتساب أو روابط فيسبوك وانستغرام من لوحة التحكم
                        </p>
                    </div>
                )}

                {(business.address || business.phone || business.opening_hours) && (
                    <div className="mt-12 grid gap-4 sm:grid-cols-3">
                        {business.address && (
                            <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-stone-200/80">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-800">
                                    <MapPin className="size-5" />
                                </span>
                                <p className="mt-3 text-sm font-medium text-stone-500">العنوان</p>
                                <p className="mt-1 font-semibold text-stone-900">{business.address}</p>
                            </div>
                        )}
                        {business.phone && (
                            <a
                                href={`tel:${business.phone}`}
                                className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-stone-200/80 transition-shadow hover:shadow-md"
                            >
                                <span className="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-800">
                                    <Phone className="size-5" />
                                </span>
                                <p className="mt-3 text-sm font-medium text-stone-500">الهاتف</p>
                                <p className="mt-1 font-semibold text-stone-900" dir="ltr">
                                    {business.phone}
                                </p>
                            </a>
                        )}
                        {business.opening_hours && (
                            <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-stone-200/80">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-800">
                                    <Clock className="size-5" />
                                </span>
                                <p className="mt-3 text-sm font-medium text-stone-500">ساعات العمل</p>
                                <p className="mt-1 font-semibold text-stone-900">
                                    {business.opening_hours}
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {whatsappUrl && (
                    <div className="mt-10 flex justify-center">
                        <Button
                            size="lg"
                            className="h-12 bg-emerald-700 px-8 hover:bg-emerald-800"
                            asChild
                        >
                            <a href={whatsappUrl} target="_blank" rel="noreferrer">
                                <MessageCircle className="size-4" />
                                راسلنا على واتساب
                            </a>
                        </Button>
                    </div>
                )}
            </section>

            {shouldShowCafeMap(business.map_placements, 'contact') && (
                <CafeLocationSection
                    lat={business.map_lat}
                    lng={business.map_lng}
                    title={business.map_title}
                    subtitle={business.map_subtitle}
                    address={business.address}
                    mapUrl={business.map_url}
                    embedHtml={business.contact_map_embed}
                    className="pb-16"
                />
            )}
        </StorefrontLayout>
    );
}
