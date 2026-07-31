import CachedImage from '@/Components/Store/CachedImage';
import JsonLd from '@/Components/Store/JsonLd';
import ProductCard from '@/Components/Store/ProductCard';
import ProductCatalogToolbar from '@/Components/Store/ProductCatalogToolbar';
import SocialMeta from '@/Components/Store/SocialMeta';
import StoreBreadcrumbs from '@/Components/Store/StoreBreadcrumbs';
import StorePagination from '@/Components/Store/StorePagination';
import EmptyState from '@/Components/Ui/EmptyState';
import Skeleton from '@/Components/Ui/Skeleton';
import { useInertiaProductCatalog } from '@/hooks/useInertiaProductCatalog';
import StoreLayout from '@/Layouts/StoreLayout';

function ProductSkeleton() {
    return (
        <div className="store-card overflow-hidden">
            <Skeleton className="aspect-square w-full rounded-none" />
            <div className="space-y-3 p-4">
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-5 w-1/3" />
            </div>
        </div>
    );
}

export default function CategoryShow({ seo, structuredData, breadcrumbs, category, filters, products, pagination }) {
    const catalog = useInertiaProductCatalog(filters, {
        basePath: `/categories/${category.slug}`,
        only: ['products', 'pagination', 'filters', 'seo', 'structuredData'],
    });

    const catalogMeta = { total: pagination?.total ?? products.length };

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <JsonLd structuredData={structuredData} />
            <StoreBreadcrumbs items={breadcrumbs} />

            {category.image_url ? (
                <div className="mb-6 store-card overflow-hidden">
                    <div className="aspect-[3/1] max-h-56 w-full sm:aspect-[4/1] sm:max-h-64">
                        <CachedImage
                            src={category.image_url}
                            alt={category.name}
                            className="h-full w-full object-cover object-center"
                        />
                    </div>
                </div>
            ) : null}

            {category.description ? (
                <p className="mb-6 max-w-3xl text-body leading-relaxed text-store-muted">{category.description}</p>
            ) : null}

            <ProductCatalogToolbar
                q={catalog.q}
                setQ={catalog.setQ}
                sort={catalog.sort}
                setSort={catalog.setSort}
                category=""
                setCategory={() => {}}
                categories={[]}
                loading={catalog.loading}
                meta={catalogMeta}
                hasActiveFilters={catalog.hasActiveFilters}
                onClear={catalog.clearFilters}
                title={category.name}
                subtitle={category.description || undefined}
                hideCategoryFilter
            />

            {catalog.loading && products.length === 0 ? (
                <div className="mt-10 grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {Array.from({ length: 6 }).map((_, i) => (
                        <ProductSkeleton key={i} />
                    ))}
                </div>
            ) : products.length === 0 ? (
                <EmptyState
                    className="mt-10"
                    title="لا توجد أطباق في هذا القسم"
                    description="جرّب بحثاً آخر أو تصفح قسماً مختلفاً."
                    actionLabel={catalog.hasActiveFilters ? 'مسح الفلاتر' : 'كل الأقسام'}
                    actionHref={catalog.hasActiveFilters ? undefined : '/categories'}
                    onAction={catalog.hasActiveFilters ? catalog.clearFilters : undefined}
                />
            ) : (
                <div className="mt-10 grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((p) => (
                        <ProductCard key={p.id} product={p} />
                    ))}
                </div>
            )}

            <StorePagination pagination={pagination} />
        </StoreLayout>
    );
}
