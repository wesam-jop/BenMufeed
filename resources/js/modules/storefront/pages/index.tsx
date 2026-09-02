import { Head, Link } from '@inertiajs/react';
import { Clock, Leaf, ShieldCheck, Sparkles, Truck } from 'lucide-react';
import StorefrontProductCard from '@/components/domain/StorefrontProductCard';
import CafeLocationSection, {
    shouldShowCafeMap,
} from '@/components/storefront/CafeLocationSection';
import CategoryCard from '@/components/storefront/CategoryCard';
import SectionHeader from '@/components/storefront/SectionHeader';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string; slug?: string } | null;
};

type Category = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
    products_count?: number;
};

type Props = {
    business: StorefrontBusiness & {
        hero_title?: string | null;
        hero_subtitle?: string | null;
        hero_image?: string | null;
        description?: string | null;
        opening_hours?: string | null;
        about_title?: string | null;
        about_content?: string | null;
        about_eyebrow?: string | null;
        homepage_sections?: string[];
        show_featured_products?: boolean;
        trust_items?: { title?: string }[] | null;
        categories_section_title?: string | null;
        categories_section_subtitle?: string | null;
        featured_section_title?: string | null;
        featured_section_subtitle?: string | null;
        contact_preview_title?: string | null;
        contact_preview_subtitle?: string | null;
        map_lat?: number | null;
        map_lng?: number | null;
        map_title?: string | null;
        map_subtitle?: string | null;
        map_placements?: string[] | null;
        map_url?: string | null;
        contact_map_embed?: string | null;
        meta_title?: string | null;
        meta_description?: string | null;
        currency?: string;
    };
    branch?: { id: number; name: string; code: string } | null;
    navCategories?: NavCategory[];
    categories: Category[];
    featuredProducts: Product[];
};

const trustIcons = [Leaf, Truck, ShieldCheck, Sparkles];

export default function StorefrontIndex({
    business,
    branch,
    navCategories = [],
    categories,
    featuredProducts,
}: Props) {
    const sections = business.homepage_sections ?? ['hero', 'categories', 'featured', 'about_preview'];
    const currency = business.currency ?? 'SAR';
    const trustItems = (business.trust_items ?? [])
        .map((item) => item.title?.trim())
        .filter((title): title is string => Boolean(title));
    const displayTrust =
        trustItems.length > 0
            ? trustItems
            : ['مكونات طازجة يومياً', 'توصيل سريع وموثوق', 'جودة مضمونة', 'تجربة طلب سلسة'];

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={business.meta_title ?? business.name}>
                {business.meta_description && (
                    <meta head-key="description" name="description" content={business.meta_description} />
                )}
            </Head>

            {sections.includes('hero') && (
                <section className="storefront-hero relative overflow-hidden bg-stone-950 text-white">
                    {business.hero_image && (
                        <img
                            src={business.hero_image}
                            alt=""
                            className="absolute inset-0 size-full object-cover opacity-30"
                        />
                    )}
                    <div className="storefront-hero-glow absolute inset-0" />
                    <div className="relative mx-auto max-w-6xl px-4 py-20 md:py-28">
                        <div className="mx-auto max-w-3xl text-center">
                            <Badge className="mb-6 border-0 bg-white/10 px-4 py-1.5 text-sm text-amber-100 backdrop-blur-sm">
                                {branch?.name ?? 'مرحباً بكم'}
                            </Badge>
                            <h1 className="text-4xl font-bold leading-tight tracking-tight md:text-6xl md:leading-[1.1]">
                                {business.hero_title ?? business.name}
                            </h1>
                            <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-stone-300 md:text-xl">
                                {business.hero_subtitle ?? business.description ?? business.tagline}
                            </p>
                            <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
                                <Button
                                    size="lg"
                                    className="h-12 bg-white px-8 text-base font-semibold text-stone-900 hover:bg-stone-100"
                                    asChild
                                >
                                    <Link href="/products">تصفّح القائمة</Link>
                                </Button>
                                <Button
                                    size="lg"
                                    variant="outline"
                                    className="h-12 border-white/30 bg-white/5 px-8 text-base text-white backdrop-blur-sm hover:bg-white/10"
                                    asChild
                                >
                                    <Link href="/cart">اطلب الآن</Link>
                                </Button>
                            </div>
                            {business.opening_hours && (
                                <p className="mt-8 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm text-stone-300 backdrop-blur-sm">
                                    <Clock className="size-4 text-amber-300" />
                                    {business.opening_hours}
                                </p>
                            )}
                        </div>
                    </div>
                </section>
            )}

            {displayTrust.length > 0 && (
                <section className="border-b border-stone-200/80 bg-white">
                    <div className="mx-auto grid max-w-6xl gap-4 px-4 py-8 sm:grid-cols-2 lg:grid-cols-4">
                        {displayTrust.map((label, index) => {
                            const Icon = trustIcons[index % trustIcons.length];

                            return (
                                <div
                                    key={`${label}-${index}`}
                                    className="flex items-center gap-3 rounded-xl px-2 py-1 text-stone-700"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-800">
                                        <Icon className="size-5" />
                                    </span>
                                    <span className="text-sm font-medium">{label}</span>
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            {sections.includes('categories') && (
                <section className="mx-auto max-w-6xl px-4 py-16 md:py-20">
                    <SectionHeader
                        title={business.categories_section_title ?? 'أقسام القائمة'}
                        subtitle={
                            business.categories_section_subtitle ??
                            'اكتشف مجموعتنا المتنوعة من المشروبات والحلويات'
                        }
                        href="/categories"
                    />

                    {categories.length === 0 ? (
                        <Card className="border-stone-200">
                            <CardContent className="py-16 text-center text-muted-foreground">
                                لا توجد أقسام متاحة حالياً
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {categories.map((cat) => (
                                <CategoryCard
                                    key={cat.id}
                                    id={cat.id}
                                    name={cat.name}
                                    slug={cat.slug}
                                    image={cat.image}
                                    imageUrl={cat.image_url}
                                    productsCount={cat.products_count ?? 0}
                                />
                            ))}
                        </div>
                    )}
                </section>
            )}

            {sections.includes('featured') && business.show_featured_products !== false && (
                <section className="bg-stone-50/80 py-16 md:py-20">
                    <div className="mx-auto max-w-6xl px-4">
                        <SectionHeader
                            title={business.featured_section_title ?? 'الأكثر طلباً'}
                            subtitle={
                                business.featured_section_subtitle ??
                                'منتجات مختارة بعناية — جرّبها اليوم'
                            }
                            href="/products"
                            linkLabel="جميع المنتجات"
                        />

                        {featuredProducts.length === 0 ? (
                            <Card className="border-stone-200 bg-white">
                                <CardContent className="py-16 text-center text-muted-foreground">
                                    لا توجد منتجات مميزة حالياً
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                                {featuredProducts.map((product) => (
                                    <StorefrontProductCard
                                        key={product.id}
                                        id={product.id}
                                        name={product.name}
                                        price={Number(product.sale_price)}
                                        image={product.image}
                                        imageUrl={product.image_url}
                                        category={product.category?.name}
                                        description={product.description}
                                        currency={currency}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            )}

            {sections.includes('about_preview') && (
                <section className="mx-auto max-w-6xl px-4 py-16 md:py-20">
                    <div className="overflow-hidden rounded-3xl bg-gradient-to-bl from-amber-900 to-stone-900 text-white shadow-xl">
                        <div className="grid gap-8 p-8 md:grid-cols-2 md:p-12">
                            <div>
                                <p className="text-sm font-medium uppercase tracking-wider text-amber-300/90">
                                    {business.about_eyebrow ?? 'قصتنا'}
                                </p>
                                <h2 className="mt-3 text-3xl font-bold md:text-4xl">
                                    {business.about_title ?? `عن ${business.name}`}
                                </h2>
                                <p className="mt-4 leading-relaxed text-stone-300">
                                    {business.about_content ?? business.description}
                                </p>
                                <Button
                                    className="mt-8 bg-white text-stone-900 hover:bg-stone-100"
                                    asChild
                                >
                                    <Link href="/about">اقرأ المزيد</Link>
                                </Button>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                {categories.slice(0, 4).map((cat) => (
                                    <Link
                                        key={cat.id}
                                        href={`/categories/${cat.slug}`}
                                        className="group rounded-2xl bg-white/10 p-4 backdrop-blur-sm transition-colors hover:bg-white/15"
                                    >
                                        <p className="font-semibold">{cat.name}</p>
                                        <p className="mt-1 text-sm text-stone-400">
                                            {cat.products_count ?? 0} منتج
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>
            )}

            {shouldShowCafeMap(business.map_placements, 'home') && (
                <CafeLocationSection
                    lat={business.map_lat}
                    lng={business.map_lng}
                    title={business.map_title}
                    subtitle={business.map_subtitle}
                    address={business.address}
                    mapUrl={business.map_url}
                    embedHtml={business.contact_map_embed}
                    className="py-14 md:py-20"
                />
            )}

            {sections.includes('contact_preview') && (
                <section className="mx-auto max-w-6xl px-4 pb-20 pt-4 text-center">
                    <h2 className="text-2xl font-bold text-stone-900 md:text-3xl">
                        {business.contact_preview_title ?? 'هل لديك استفسار؟'}
                    </h2>
                    <p className="mx-auto mt-3 max-w-lg text-stone-500">
                        {business.contact_preview_subtitle ??
                            'فريقنا جاهز للرد على أسئلتك ومساعدتك في اختيار الطلب المناسب'}
                    </p>
                    <Button className="mt-6 bg-stone-900 hover:bg-stone-800" size="lg" asChild>
                        <Link href="/contact">تواصل معنا</Link>
                    </Button>
                </section>
            )}
        </StorefrontLayout>
    );
}
