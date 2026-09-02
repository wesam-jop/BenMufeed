import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import StorefrontProductCard from '@/components/domain/StorefrontProductCard';
import SectionHeader from '@/components/storefront/SectionHeader';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string } | null;
};

type Category = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
    products?: Product[];
};

type Props = {
    business: StorefrontBusiness;
    navCategories?: NavCategory[];
    category: Category;
};

export default function CategoryShow({ business, navCategories = [], category }: Props) {
    const products = category.products ?? [];
    const currency = business.currency ?? 'SAR';

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${category.name} — ${business.name}`} />

            <div className="relative overflow-hidden border-b border-stone-200/80 bg-stone-900 text-white">
                {(category.image_url || category.image) && (
                    <img
                        src={category.image_url ?? category.image ?? ''}
                        alt=""
                        className="absolute inset-0 size-full object-cover opacity-25"
                    />
                )}
                <div className="storefront-hero-glow absolute inset-0 opacity-60" />
                <div className="relative mx-auto max-w-6xl px-4 py-12 md:py-16">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="mb-6 text-stone-300 hover:bg-white/10 hover:text-white"
                        asChild
                    >
                        <Link href="/categories">
                            <ArrowRight className="size-4" />
                            جميع الأقسام
                        </Link>
                    </Button>
                    <h1 className="text-3xl font-bold md:text-4xl">{category.name}</h1>
                    <p className="mt-2 text-stone-300">{products.length} منتج في هذا القسم</p>
                </div>
            </div>

            <div className="mx-auto max-w-6xl px-4 py-10">
                {products.length === 0 ? (
                    <Card className="border-stone-200">
                        <CardContent className="py-20 text-center text-muted-foreground">
                            لا توجد منتجات في هذا القسم حالياً
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <SectionHeader
                            title="منتجات القسم"
                            subtitle={`تصفّح ${products.length} منتجاً في ${category.name}`}
                            className="mb-8"
                        />
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                            {products.map((product) => (
                                <StorefrontProductCard
                                    key={product.id}
                                    id={product.id}
                                    name={product.name}
                                    price={Number(product.sale_price)}
                                    image={product.image}
                                    imageUrl={product.image_url}
                                    category={category.name}
                                    description={product.description}
                                    currency={currency}
                                />
                            ))}
                        </div>
                    </>
                )}
            </div>
        </StorefrontLayout>
    );
}
