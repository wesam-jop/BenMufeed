import { Link } from '@inertiajs/react';
import { Leaf, MessageCircle, ShieldCheck, Sparkles, Truck } from 'lucide-react';
import StorefrontProductCard from '@/components/domain/StorefrontProductCard';
import CafeLocationSection from '@/components/storefront/CafeLocationSection';
import CategoryCard from '@/components/storefront/CategoryCard';
import SectionHeader from '@/components/storefront/SectionHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { StorefrontBusiness } from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';
import type { CmsPageTheme, CmsSection } from '@/types/cms-page';

type Category = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
    products_count?: number;
};

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string; slug?: string } | null;
};

type Props = {
    sections: CmsSection[];
    theme: CmsPageTheme;
    business: StorefrontBusiness & Record<string, unknown>;
    categories?: Category[];
    featuredProducts?: Product[];
    className?: string;
};

function propString(section: CmsSection, key: string, fallback = ''): string {
    const value = section.props?.[key];
    return typeof value === 'string' ? value : fallback;
}

function propBool(section: CmsSection, key: string, fallback = true): boolean {
    const value = section.props?.[key];
    return typeof value === 'boolean' ? value : fallback;
}

function propNumber(section: CmsSection, key: string, fallback: number): number {
    const value = section.props?.[key];
    return typeof value === 'number' ? value : fallback;
}

function trustItems(section: CmsSection): Array<{ title?: string; description?: string }> {
    const items = section.props?.items;
    return Array.isArray(items) ? (items as Array<{ title?: string; description?: string }>) : [];
}

const trustIcons = [Leaf, Truck, ShieldCheck, Sparkles];

function HeroSection({
    section,
    theme,
    business,
}: {
    section: CmsSection;
    theme: CmsPageTheme;
    business: StorefrontBusiness & Record<string, unknown>;
}) {
    const title = propString(section, 'title', 'مرحباً بكم');
    const subtitle = propString(section, 'subtitle');
    const eyebrow = propString(section, 'eyebrow');
    const showCta = propBool(section, 'show_cta');
    const ctaLabel = propString(section, 'cta_label', 'تصفح القائمة');
    const ctaHref = propString(section, 'cta_href', '/products');
    const secondaryCtaLabel = propString(section, 'secondary_cta_label');
    const secondaryCtaHref = propString(section, 'secondary_cta_href', '/cart');
    const showHours = propBool(section, 'show_hours', false);
    const hoursText =
        propString(section, 'hours_text') ||
        (typeof business.opening_hours === 'string' ? business.opening_hours : '');
    const image =
        propString(section, 'image') ||
        (typeof business.hero_image === 'string' ? business.hero_image : '');
    const variant = section.variant;

    if (variant === 'design-2') {
        return (
            <section className="grid gap-8 py-10 lg:grid-cols-2 lg:items-center">
                <div className="space-y-4 text-right">
                    {eyebrow ? (
                        <p className="text-sm font-medium" style={{ color: theme.secondary }}>
                            {eyebrow}
                        </p>
                    ) : null}
                    <h1 className="text-3xl font-bold tracking-tight md:text-5xl" style={{ color: theme.primary }}>
                        {title}
                    </h1>
                    {subtitle ? <p className="text-muted-foreground md:text-lg">{subtitle}</p> : null}
                    {showCta ? (
                        <div className="flex flex-wrap gap-3">
                            <Button asChild className="mt-2" style={{ backgroundColor: theme.primary }}>
                                <Link href={ctaHref}>{ctaLabel}</Link>
                            </Button>
                            {secondaryCtaLabel ? (
                                <Button asChild variant="outline" className="mt-2">
                                    <Link href={secondaryCtaHref}>{secondaryCtaLabel}</Link>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>
                {image ? (
                    <div className="overflow-hidden rounded-3xl">
                        <img src={image} alt="" className="aspect-[4/3] w-full object-cover" />
                    </div>
                ) : (
                    <div
                        className="min-h-56 rounded-3xl"
                        style={{ background: `linear-gradient(135deg, ${theme.primary}, ${theme.secondary})` }}
                    />
                )}
            </section>
        );
    }

    if (variant === 'design-3') {
        return (
            <section
                className="relative overflow-hidden rounded-3xl px-6 py-16 text-center text-white md:px-10 md:py-20"
                style={{ background: `linear-gradient(120deg, ${theme.primary}, ${theme.secondary})` }}
            >
                {image ? (
                    <img
                        src={image}
                        alt=""
                        className="absolute inset-0 size-full object-cover opacity-25"
                    />
                ) : null}
                <div className="relative">
                    {eyebrow ? (
                        <p className="mb-4 text-sm text-white/80">{eyebrow}</p>
                    ) : null}
                    <h1 className="text-3xl font-bold md:text-5xl">{title}</h1>
                    {subtitle ? <p className="mx-auto mt-3 max-w-2xl text-white/90">{subtitle}</p> : null}
                    {showCta ? (
                        <div className="mt-6 flex flex-wrap justify-center gap-3">
                            <Button asChild variant="secondary">
                                <Link href={ctaHref}>{ctaLabel}</Link>
                            </Button>
                            {secondaryCtaLabel ? (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="border-white/40 bg-white/10 text-white hover:bg-white/20"
                                >
                                    <Link href={secondaryCtaHref}>{secondaryCtaLabel}</Link>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            </section>
        );
    }

    return (
        <section className="relative -mx-4 overflow-hidden bg-stone-950 text-white md:-mx-6">
            {image ? (
                <img
                    src={image}
                    alt=""
                    className="absolute inset-0 size-full object-cover opacity-30"
                />
            ) : null}
            <div
                className="absolute inset-0 opacity-80"
                style={{
                    background: `linear-gradient(135deg, ${theme.primary}cc, ${theme.secondary}99)`,
                }}
            />
            <div className="relative mx-auto max-w-3xl space-y-5 px-6 py-20 text-center md:px-10 md:py-28">
                {eyebrow ? (
                    <span className="inline-flex rounded-full bg-white/10 px-4 py-1.5 text-sm text-amber-100 backdrop-blur-sm">
                        {eyebrow}
                    </span>
                ) : null}
                <h1 className="text-4xl font-bold tracking-tight md:text-6xl md:leading-[1.1]">{title}</h1>
                {subtitle ? (
                    <p className="mx-auto max-w-2xl text-lg leading-relaxed text-white/85 md:text-xl">
                        {subtitle}
                    </p>
                ) : null}
                {showCta ? (
                    <div className="flex flex-wrap items-center justify-center gap-3 pt-2">
                        <Button
                            asChild
                            size="lg"
                            className="h-12 bg-white px-8 text-base font-semibold text-stone-900 hover:bg-stone-100"
                        >
                            <Link href={ctaHref}>{ctaLabel}</Link>
                        </Button>
                        {secondaryCtaLabel ? (
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="h-12 border-white/30 bg-white/5 px-8 text-base text-white hover:bg-white/10"
                            >
                                <Link href={secondaryCtaHref}>{secondaryCtaLabel}</Link>
                            </Button>
                        ) : null}
                    </div>
                ) : null}
                {showHours && hoursText ? (
                    <p className="pt-2 text-sm text-white/70">{hoursText}</p>
                ) : null}
            </div>
        </section>
    );
}

function TrustSection({ section, theme }: { section: CmsSection; theme: CmsPageTheme }) {
    const items = trustItems(section);
    const title = propString(section, 'title');
    const subtitle = propString(section, 'subtitle');
    const variant = section.variant;

    if (items.length === 0) {
        return null;
    }

    if (variant === 'design-2') {
        return (
            <section className="space-y-6 py-8">
                {(title || subtitle) && <SectionHeader title={title} subtitle={subtitle} />}
                <div className="grid gap-4 md:grid-cols-2">
                    {items.map((item, index) => (
                        <Card key={`${item.title}-${index}`}>
                            <CardContent className="space-y-2 p-5">
                                <p className="font-semibold" style={{ color: theme.primary }}>
                                    {item.title}
                                </p>
                                {item.description ? (
                                    <p className="text-sm text-muted-foreground">{item.description}</p>
                                ) : null}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </section>
        );
    }

    if (variant === 'design-3') {
        return (
            <section className="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-y py-5 text-sm">
                {items.map((item, index) => (
                    <span key={`${item.title}-${index}`} className="font-medium" style={{ color: theme.secondary }}>
                        {item.title}
                    </span>
                ))}
            </section>
        );
    }

    return (
        <section className="grid gap-4 py-6 sm:grid-cols-2 lg:grid-cols-4">
            {items.map((item, index) => {
                const Icon = trustIcons[index % trustIcons.length];
                return (
                    <div key={`${item.title}-${index}`} className="flex items-center gap-3 rounded-2xl border bg-white p-4">
                        <span
                            className="flex size-10 items-center justify-center rounded-xl text-white"
                            style={{ backgroundColor: theme.primary }}
                        >
                            <Icon className="size-5" />
                        </span>
                        <span className="text-sm font-medium">{item.title}</span>
                    </div>
                );
            })}
        </section>
    );
}

function CategoriesSection({
    section,
    categories,
}: {
    section: CmsSection;
    categories: Category[];
}) {
    const title = propString(section, 'title', 'الأقسام');
    const subtitle = propString(section, 'subtitle');
    const variant = section.variant;
    const cols =
        variant === 'design-2' ? 'sm:grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-3';

    if (categories.length === 0) {
        return null;
    }

    if (variant === 'design-3') {
        return (
            <section className="space-y-6 py-8">
                <SectionHeader title={title} subtitle={subtitle} />
                <div className="flex gap-4 overflow-x-auto pb-2">
                    {categories.map((category) => (
                        <div key={category.id} className="min-w-[220px] max-w-[240px] shrink-0">
                            <CategoryCard
                                id={category.id}
                                name={category.name}
                                slug={category.slug}
                                image={category.image}
                                imageUrl={category.image_url}
                                productsCount={category.products_count}
                            />
                        </div>
                    ))}
                </div>
            </section>
        );
    }

    return (
        <section className="space-y-6 py-8">
            <SectionHeader title={title} subtitle={subtitle} />
            <div className={cn('grid gap-4', cols)}>
                {categories.map((category) => (
                    <CategoryCard
                        key={category.id}
                        id={category.id}
                        name={category.name}
                        slug={category.slug}
                        image={category.image}
                        imageUrl={category.image_url}
                        productsCount={category.products_count}
                    />
                ))}
            </div>
        </section>
    );
}

function FeaturedSection({
    section,
    products,
}: {
    section: CmsSection;
    products: Product[];
}) {
    const title = propString(section, 'title', 'الأكثر طلباً');
    const subtitle = propString(section, 'subtitle');
    const limit = propNumber(section, 'limit', 8);
    const list = products.slice(0, limit);
    const variant = section.variant;

    if (list.length === 0) {
        return null;
    }

    if (variant === 'design-2') {
        return (
            <section className="space-y-6 py-8">
                <SectionHeader title={title} subtitle={subtitle} />
                <div className="space-y-3">
                    {list.map((product) => (
                        <div
                            key={product.id}
                            className="flex items-center justify-between gap-4 rounded-2xl border bg-white px-4 py-3"
                        >
                            <div>
                                <p className="font-medium">{product.name}</p>
                                <p className="text-sm text-muted-foreground">
                                    {product.category?.name}
                                </p>
                            </div>
                            <p className="font-semibold">{product.sale_price}</p>
                        </div>
                    ))}
                </div>
            </section>
        );
    }

    if (variant === 'design-3' && list[0]) {
        const hero = list[0];
        return (
            <section className="space-y-6 py-8">
                <SectionHeader title={title} subtitle={subtitle} />
                <div className="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
                    <StorefrontProductCard
                        id={hero.id}
                        name={hero.name}
                        price={Number(hero.sale_price)}
                        image={hero.image}
                        imageUrl={hero.image_url}
                        category={hero.category?.name}
                        description={hero.description}
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        {list.slice(1, 5).map((product) => (
                            <StorefrontProductCard
                                key={product.id}
                                id={product.id}
                                name={product.name}
                                price={Number(product.sale_price)}
                                image={product.image}
                                imageUrl={product.image_url}
                                category={product.category?.name}
                                description={product.description}
                            />
                        ))}
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="space-y-6 py-8">
            <SectionHeader title={title} subtitle={subtitle} />
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {list.map((product) => (
                    <StorefrontProductCard
                        key={product.id}
                        id={product.id}
                        name={product.name}
                        price={Number(product.sale_price)}
                        image={product.image}
                        imageUrl={product.image_url}
                        category={product.category?.name}
                        description={product.description}
                    />
                ))}
            </div>
        </section>
    );
}

function RichTextSection({ section, theme }: { section: CmsSection; theme: CmsPageTheme }) {
    const eyebrow = propString(section, 'eyebrow');
    const title = propString(section, 'title');
    const content = propString(section, 'content');
    const variant = section.variant;

    if (variant === 'design-2') {
        return (
            <section className="grid gap-6 py-10 lg:grid-cols-2 lg:items-start">
                <div>
                    {eyebrow ? (
                        <p className="text-sm font-medium" style={{ color: theme.secondary }}>
                            {eyebrow}
                        </p>
                    ) : null}
                    <h2 className="mt-2 text-3xl font-bold" style={{ color: theme.primary }}>
                        {title}
                    </h2>
                </div>
                <p className="whitespace-pre-wrap leading-8 text-muted-foreground">{content}</p>
            </section>
        );
    }

    if (variant === 'design-3') {
        return (
            <section className="py-10">
                <blockquote
                    className="rounded-3xl border-r-4 bg-stone-50 px-6 py-8 text-lg leading-9"
                    style={{ borderColor: theme.primary }}
                >
                    {content || title}
                </blockquote>
            </section>
        );
    }

    return (
        <section className="mx-auto max-w-3xl space-y-3 py-10 text-center">
            {eyebrow ? (
                <p className="text-sm font-medium" style={{ color: theme.secondary }}>
                    {eyebrow}
                </p>
            ) : null}
            <h2 className="text-3xl font-bold" style={{ color: theme.primary }}>
                {title}
            </h2>
            <p className="whitespace-pre-wrap leading-8 text-muted-foreground">{content}</p>
        </section>
    );
}

function CtaSection({ section, theme }: { section: CmsSection; theme: CmsPageTheme }) {
    const title = propString(section, 'title');
    const subtitle = propString(section, 'subtitle');
    const ctaLabel = propString(section, 'cta_label', 'ابدأ');
    const ctaHref = propString(section, 'cta_href', '/products');
    const variant = section.variant;

    if (variant === 'design-2') {
        return (
            <section className="py-8">
                <Card className="overflow-hidden">
                    <CardContent className="flex flex-col items-start gap-4 p-8 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 className="text-2xl font-bold" style={{ color: theme.primary }}>
                                {title}
                            </h3>
                            {subtitle ? <p className="mt-1 text-muted-foreground">{subtitle}</p> : null}
                        </div>
                        <Button asChild style={{ backgroundColor: theme.primary }}>
                            <Link href={ctaHref}>{ctaLabel}</Link>
                        </Button>
                    </CardContent>
                </Card>
            </section>
        );
    }

    if (variant === 'design-3') {
        return (
            <section
                className="rounded-3xl px-6 py-12 text-center text-white"
                style={{ backgroundColor: theme.primary }}
            >
                <h3 className="text-2xl font-bold md:text-3xl">{title}</h3>
                {subtitle ? <p className="mt-2 text-white/90">{subtitle}</p> : null}
                <Button asChild variant="secondary" className="mt-6">
                    <Link href={ctaHref}>{ctaLabel}</Link>
                </Button>
            </section>
        );
    }

    return (
        <section
            className="flex flex-col items-center justify-between gap-4 rounded-2xl border px-6 py-8 md:flex-row"
            style={{ borderColor: `${theme.secondary}55` }}
        >
            <div>
                <h3 className="text-xl font-bold" style={{ color: theme.primary }}>
                    {title}
                </h3>
                {subtitle ? <p className="text-sm text-muted-foreground">{subtitle}</p> : null}
            </div>
            <Button asChild style={{ backgroundColor: theme.secondary }}>
                <Link href={ctaHref}>{ctaLabel}</Link>
            </Button>
        </section>
    );
}

function ContactChannelsSection({
    section,
    business,
    theme,
}: {
    section: CmsSection;
    business: StorefrontBusiness;
    theme: CmsPageTheme;
}) {
    const channels = [
        { key: 'whatsapp', label: 'واتساب', href: business.whatsapp ? `https://wa.me/${String(business.whatsapp).replace(/\D/g, '')}` : null },
        { key: 'facebook', label: 'فيسبوك', href: business.facebook || null },
        { key: 'instagram', label: 'انستغرام', href: business.instagram || null },
    ].filter((c) => c.href);

    if (channels.length === 0) {
        return null;
    }

    const title = propString(section, 'title');
    const subtitle = propString(section, 'subtitle');
    const variant = section.variant;

    if (variant === 'design-2') {
        return (
            <section className="space-y-3 py-8">
                {(title || subtitle) && <SectionHeader title={title} subtitle={subtitle} />}
                {channels.map((channel) => (
                    <a
                        key={channel.key}
                        href={channel.href!}
                        target="_blank"
                        rel="noreferrer"
                        className="flex items-center justify-between rounded-xl border px-4 py-3 hover:bg-muted/40"
                    >
                        <span className="font-medium">{channel.label}</span>
                        <MessageCircle className="size-4" style={{ color: theme.primary }} />
                    </a>
                ))}
            </section>
        );
    }

    return (
        <section className="space-y-6 py-8">
            {(title || subtitle) && <SectionHeader title={title} subtitle={subtitle} />}
            <div
                className={cn(
                    'grid gap-4',
                    variant === 'design-3' ? 'sm:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-3',
                )}
            >
                {channels.map((channel) => (
                    <a
                        key={channel.key}
                        href={channel.href!}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded-2xl border bg-white p-5 text-center transition hover:shadow-md"
                    >
                        <MessageCircle className="mx-auto mb-2 size-6" style={{ color: theme.primary }} />
                        <p className="font-semibold">{channel.label}</p>
                    </a>
                ))}
            </div>
        </section>
    );
}

function MapSection({
    section,
    business,
}: {
    section: CmsSection;
    business: StorefrontBusiness & Record<string, unknown>;
}) {
    const title = propString(section, 'title', String(business.map_title ?? 'موقعنا'));
    const subtitle = propString(section, 'subtitle', String(business.map_subtitle ?? ''));
    const variant = section.variant;

    if (business.map_lat == null || business.map_lng == null) {
        return null;
    }

    const map = (
        <CafeLocationSection
            title={variant === 'design-2' ? '' : title}
            subtitle={variant === 'design-2' ? '' : subtitle}
            lat={Number(business.map_lat)}
            lng={Number(business.map_lng)}
            address={
                variant === 'design-2'
                    ? undefined
                    : typeof business.address === 'string'
                      ? business.address
                      : undefined
            }
            mapUrl={typeof business.map_url === 'string' ? business.map_url : undefined}
            className="max-w-none px-0"
        />
    );

    if (variant === 'design-2') {
        return (
            <section className="grid gap-8 py-8 lg:grid-cols-2 lg:items-center">
                <div className="space-y-3 text-right">
                    <h2 className="text-2xl font-bold md:text-3xl">{title}</h2>
                    {subtitle ? <p className="text-muted-foreground">{subtitle}</p> : null}
                    {typeof business.address === 'string' && business.address ? (
                        <p className="text-sm text-muted-foreground">{business.address}</p>
                    ) : null}
                </div>
                <div>{map}</div>
            </section>
        );
    }

    return <section className="py-8">{map}</section>;
}

function CustomHtmlSection({ section }: { section: CmsSection }) {
    const text = propString(section, 'text');
    if (!text) {
        return null;
    }
    return (
        <section className="py-6">
            <p className="whitespace-pre-wrap text-center leading-8 text-muted-foreground">{text}</p>
        </section>
    );
}

export default function StorefrontPageRenderer({
    sections,
    theme,
    business,
    categories = [],
    featuredProducts = [],
    className,
}: Props) {
    const enabled = sections.filter((section) => section.enabled);

    return (
        <div
            className={cn('space-y-2', className)}
            style={
                {
                    '--page-primary': theme.primary,
                    '--page-secondary': theme.secondary,
                    fontFamily: `'${theme.font_family}', Cairo, Tajawal, sans-serif`,
                    fontSize: `${theme.base_font_size}px`,
                } as React.CSSProperties
            }
        >
            {enabled.map((section) => {
                switch (section.type) {
                    case 'hero':
                        return (
                            <HeroSection
                                key={section.id}
                                section={section}
                                theme={theme}
                                business={business}
                            />
                        );
                    case 'trust':
                        return <TrustSection key={section.id} section={section} theme={theme} />;
                    case 'categories':
                        return (
                            <CategoriesSection
                                key={section.id}
                                section={section}
                                categories={categories}
                            />
                        );
                    case 'featured_products':
                        return (
                            <FeaturedSection
                                key={section.id}
                                section={section}
                                products={featuredProducts}
                            />
                        );
                    case 'rich_text':
                        return <RichTextSection key={section.id} section={section} theme={theme} />;
                    case 'cta':
                        return <CtaSection key={section.id} section={section} theme={theme} />;
                    case 'contact_channels':
                        return (
                            <ContactChannelsSection
                                key={section.id}
                                section={section}
                                business={business}
                                theme={theme}
                            />
                        );
                    case 'map':
                        return (
                            <MapSection key={section.id} section={section} business={business} />
                        );
                    case 'custom_html':
                        return <CustomHtmlSection key={section.id} section={section} />;
                    default:
                        return null;
                }
            })}
        </div>
    );
}
