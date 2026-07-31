import { Link } from '@inertiajs/react';
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

export default function ProductIndex({
    seo,
    structuredData,
    breadcrumbs,
    filters,
    products,
    pagination,
    catalogMeta,
    categories,
}) {
    const catalog = useInertiaProductCatalog(filters);

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <JsonLd structuredData={structuredData} />
            <StoreBreadcrumbs items={breadcrumbs} />

            <ProductCatalogToolbar
                q={catalog.q}
                setQ={catalog.setQ}
                sort={catalog.sort}
                setSort={catalog.setSort}
                category={catalog.category}
                setCategory={catalog.setCategory}
                categories={categories}
                loading={catalog.loading}
                meta={catalogMeta}
                hasActiveFilters={catalog.hasActiveFilters}
                onClear={catalog.clearFilters}
            />

            <div
                className={`mt-8 grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 ${
                    catalog.loading ? 'pointer-events-none opacity-60' : ''
                }`}
                aria-busy={catalog.loading}
            >
                {catalog.loading && products.length === 0
                    ? Array.from({ length: 8 }).map((_, i) => <ProductSkeleton key={i} />)
                    : products.map((p) => (
                          <div key={p.id} className="flex h-full flex-col">
                              <ProductCard product={p} className="flex-1" />
                              {p.category ? (
                                  <Link
                                      href={`/categories/${p.category.slug}`}
                                      className="mt-2 block px-1 text-center text-caption font-medium text-store-secondary transition duration-fast hover:text-store-accent"
                                  >
                                      {p.category.name}
                                  </Link>
                              ) : null}
                          </div>
                      ))}
            </div>

            {!catalog.loading && products.length === 0 ? (
                <EmptyState
                    className="mt-12"
                    title="لا توجد أطباق مطابقة"
                    description="جرّب تغيير كلمة البحث أو اختيار قسم آخر."
                    actionLabel={catalog.hasActiveFilters ? 'عرض كل القائمة' : undefined}
                    onAction={catalog.hasActiveFilters ? catalog.clearFilters : undefined}
                />
            ) : null}

            <StorePagination pagination={pagination} />
        </StoreLayout>
    );
}
