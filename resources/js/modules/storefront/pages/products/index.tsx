import { Head, router } from '@inertiajs/react';
import { Search, SlidersHorizontal, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import PaginationLinks from '@/components/admin/pagination-links';
import StorefrontProductCard from '@/components/domain/StorefrontProductCard';
import StorefrontPageRenderer from '@/components/storefront/page-renderer/StorefrontPageRenderer';
import StorefrontLayout, { type NavCategory, type StorefrontBusiness } from '@/layouts/storefront-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated } from '@/types/admin';
import type { CmsPageTheme, CmsSection } from '@/types/cms-page';

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string; slug?: string } | null;
};

type CategoryOption = {
    id: number;
    name: string;
    slug: string;
};

type Filters = {
    search?: string;
    category_id?: string;
    min_price?: string;
    max_price?: string;
    sort?: string;
};

type Props = {
    business: StorefrontBusiness & Record<string, unknown>;
    navCategories?: NavCategory[];
    products: Paginated<Product>;
    categories: CategoryOption[];
    filters?: Filters | unknown[];
    cmsPage?: {
        id: number;
        title: string;
        path: string;
        seo_title?: string | null;
        seo_description?: string | null;
        theme: CmsPageTheme;
        sections: CmsSection[];
    };
    featuredProducts?: Product[];
};

export default function ProductsIndex({
    business,
    navCategories = [],
    products,
    categories,
    filters: rawFilters,
    cmsPage,
    featuredProducts = [],
}: Props) {
    const filters: Filters =
        rawFilters && !Array.isArray(rawFilters) ? rawFilters : {};

    const currency = business.currency ?? 'SAR';
    const pageTitle =
        cmsPage?.seo_title ||
        cmsPage?.title ||
        (typeof business.products_page_title === 'string' ? business.products_page_title : null) ||
        'جميع المنتجات';
    const [search, setSearch] = useState(
        typeof filters.search === 'string' ? filters.search : '',
    );
    const [categoryId, setCategoryId] = useState(
        typeof filters.category_id === 'string' || typeof filters.category_id === 'number'
            ? String(filters.category_id)
            : 'all',
    );
    const [minPrice, setMinPrice] = useState(
        typeof filters.min_price === 'string' || typeof filters.min_price === 'number'
            ? String(filters.min_price)
            : '',
    );
    const [maxPrice, setMaxPrice] = useState(
        typeof filters.max_price === 'string' || typeof filters.max_price === 'number'
            ? String(filters.max_price)
            : '',
    );
    const [sort, setSort] = useState(
        typeof filters.sort === 'string' ? filters.sort : 'name',
    );
    const [showFilters, setShowFilters] = useState(
        Boolean(filters.category_id || filters.min_price || filters.max_price || filters.sort),
    );

    const hasActiveFilters = Boolean(
        filters.search || filters.category_id || filters.min_price || filters.max_price || filters.sort,
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();

        router.get(
            '/products',
            {
                search: search || undefined,
                category_id: categoryId !== 'all' ? categoryId : undefined,
                min_price: minPrice || undefined,
                max_price: maxPrice || undefined,
                sort: sort !== 'name' ? sort : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setCategoryId('all');
        setMinPrice('');
        setMaxPrice('');
        setSort('name');
        router.get('/products');
    };

    return (
        <StorefrontLayout business={business} navCategories={navCategories}>
            <Head title={`${pageTitle} — ${business.name}`}>
                {(cmsPage?.seo_description ||
                    (typeof business.meta_description === 'string' && business.meta_description)) && (
                    <meta
                        head-key="description"
                        name="description"
                        content={
                            cmsPage?.seo_description ||
                            String(business.meta_description ?? '')
                        }
                    />
                )}
            </Head>

            {cmsPage ? (
                <div className="mx-auto max-w-6xl px-4 pt-4 md:px-6">
                    <StorefrontPageRenderer
                        theme={cmsPage.theme}
                        sections={cmsPage.sections}
                        business={business}
                        categories={[]}
                        featuredProducts={featuredProducts}
                    />
                </div>
            ) : null}

            <div className="mx-auto max-w-6xl px-4 py-10">
                <p className="mb-4 text-sm text-stone-500">{products.total} منتج متاح</p>
                <Card className="mb-8 overflow-hidden border-stone-200/80 shadow-sm">
                    <CardContent className="p-4 md:p-6">
                        <form onSubmit={applyFilters} className="grid gap-4">
                            <div className="flex flex-col gap-3 lg:flex-row">
                                <div className="relative flex-1">
                                    <Search className="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                                    <Input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="ابحث بالاسم أو الوصف..."
                                        className="h-11 border-stone-200 ps-10"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        className="h-11 flex-1 bg-stone-900 hover:bg-stone-800 lg:flex-none lg:px-8"
                                    >
                                        بحث
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-11 border-stone-200"
                                        onClick={() => setShowFilters((v) => !v)}
                                    >
                                        <SlidersHorizontal className="size-4" />
                                        <span className="hidden sm:inline">فلترة</span>
                                    </Button>
                                </div>
                            </div>

                            {showFilters && (
                                <div className="grid gap-4 border-t border-stone-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div className="grid gap-2">
                                        <Label>القسم</Label>
                                        <Select value={categoryId} onValueChange={setCategoryId}>
                                            <SelectTrigger className="border-stone-200">
                                                <SelectValue placeholder="جميع الأقسام" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">جميع الأقسام</SelectItem>
                                                {categories.map((cat) => (
                                                    <SelectItem key={cat.id} value={String(cat.id)}>
                                                        {cat.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="min_price">السعر من</Label>
                                        <Input
                                            id="min_price"
                                            type="number"
                                            min="0"
                                            value={minPrice}
                                            onChange={(e) => setMinPrice(e.target.value)}
                                            className="border-stone-200"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max_price">السعر إلى</Label>
                                        <Input
                                            id="max_price"
                                            type="number"
                                            min="0"
                                            value={maxPrice}
                                            onChange={(e) => setMaxPrice(e.target.value)}
                                            className="border-stone-200"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>الترتيب</Label>
                                        <Select value={sort} onValueChange={setSort}>
                                            <SelectTrigger className="border-stone-200">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="name">الاسم</SelectItem>
                                                <SelectItem value="price_asc">السعر: الأقل</SelectItem>
                                                <SelectItem value="price_desc">السعر: الأعلى</SelectItem>
                                                <SelectItem value="newest">الأحدث</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            )}

                            {hasActiveFilters && (
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">فلاتر نشطة</Badge>
                                    <Button type="button" variant="ghost" size="sm" onClick={clearFilters}>
                                        <X className="size-3.5" />
                                        مسح الكل
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {products.data.length === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center text-muted-foreground">
                            لا توجد منتجات مطابقة لبحثك
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.data.map((product) => (
                            <StorefrontProductCard
                                key={product.id}
                                id={product.id}
                                name={product.name}
                                price={Number(product.sale_price)}
                                image={product.image}
                                imageUrl={product.image_url}
                                category={product.category?.name}
                                description={product.description}
                                currency={String(currency)}
                            />
                        ))}
                    </div>
                )}

                <div className="mt-8">
                    <PaginationLinks paginator={products} />
                </div>
            </div>
        </StorefrontLayout>
    );
}
