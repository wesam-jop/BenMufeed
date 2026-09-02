import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Coffee } from 'lucide-react';
import CategoryCard from '@/components/storefront/CategoryCard';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';

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
        categories_page_title?: string | null;
        categories_page_subtitle?: string | null;
        categories_page_image?: string | null;
        meta_description?: string | null;
    };
    navCategories?: NavCategory[];
    categories: Category[];
};

export default function CategoriesIndex({ business, navCategories = [], categories }: Props) {
    const totalProducts = categories.reduce((sum, cat) => sum + (cat.products_count ?? 0), 0);
    const pageTitle = business.categories_page_title ?? 'أقسام القائمة';
    const pageSubtitle =
        business.categories_page_subtitle ?? 'اختَر القسم الذي يناسبك بهدوء — تجربة بسيطة وأنيقة';

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${pageTitle} — ${business.name}`}>
                {business.meta_description && (
                    <meta head-key="description" name="description" content={business.meta_description} />
                )}
            </Head>

            <section className="relative overflow-hidden border-b border-stone-200/60 bg-gradient-to-b from-[#f3efe8] to-[#faf8f5]">
                {business.categories_page_image && (
                    <img
                        src={business.categories_page_image}
                        alt=""
                        className="absolute inset-0 size-full object-cover opacity-20"
                    />
                )}
                <div className="relative mx-auto max-w-5xl px-4 py-16 text-center md:py-20">
                    <p className="text-xs font-medium uppercase tracking-[0.28em] text-stone-500">
                        {business.name}
                    </p>
                    <h1 className="mt-4 text-4xl font-semibold tracking-tight text-stone-900 md:text-5xl">
                        {pageTitle}
                    </h1>
                    <p className="mx-auto mt-4 max-w-md text-base leading-relaxed text-stone-500">
                        {pageSubtitle}
                    </p>
                    {(categories.length > 0 || totalProducts > 0) && (
                        <p className="mt-6 text-sm text-stone-400">
                            {categories.length} قسم
                            <span className="mx-2 text-stone-300">·</span>
                            {totalProducts} منتج
                        </p>
                    )}
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-12 md:py-16">
                {categories.length === 0 ? (
                    <div className="rounded-3xl bg-white/80 py-20 text-center ring-1 ring-stone-900/[0.04]">
                        <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-stone-100 text-stone-500">
                            <Coffee className="size-6" strokeWidth={1.5} />
                        </div>
                        <p className="text-lg font-medium text-stone-800">لا توجد أقسام متاحة حالياً</p>
                        <p className="mt-2 text-sm text-stone-500">سنضيف الأقسام قريباً</p>
                        <Button className="mt-8 bg-stone-900 hover:bg-stone-800" asChild>
                            <Link href="/">العودة للرئيسية</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2">
                        {categories.map((cat, index) => (
                            <CategoryCard
                                key={cat.id}
                                id={cat.id}
                                name={cat.name}
                                slug={cat.slug}
                                image={cat.image}
                                imageUrl={cat.image_url}
                                productsCount={cat.products_count ?? 0}
                                featured={categories.length >= 3 && index === 0}
                            />
                        ))}
                    </div>
                )}

                {categories.length > 0 && (
                    <div className="mt-14 flex justify-center">
                        <Link
                            href="/products"
                            className="group inline-flex items-center gap-2 text-sm font-medium text-stone-600 transition-colors hover:text-stone-900"
                        >
                            تصفّح جميع المنتجات
                            <ArrowLeft className="size-4 transition-transform group-hover:-translate-x-0.5" />
                        </Link>
                    </div>
                )}
            </section>
        </StorefrontLayout>
    );
}
