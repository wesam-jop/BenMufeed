import JsonLd from '@/Components/Store/JsonLd';
import CategoryCard from '@/Components/Store/CategoryCard';
import SocialMeta from '@/Components/Store/SocialMeta';
import StoreBreadcrumbs from '@/Components/Store/StoreBreadcrumbs';
import StorePagination from '@/Components/Store/StorePagination';
import EmptyState from '@/Components/Ui/EmptyState';
import StoreLayout from '@/Layouts/StoreLayout';

export default function CategoryIndex({ seo, structuredData, breadcrumbs, categories, pagination, listMeta }) {
    return (
        <StoreLayout>
            <SocialMeta seo={seo} />
            <JsonLd structuredData={structuredData} />
            <StoreBreadcrumbs items={breadcrumbs} />

            <header className="store-page-hero">
                <p className="store-section-eyebrow text-store-secondary">القائمة</p>
                <h1 className="font-display mt-2 text-h2 sm:text-h1">أقسام القائمة</h1>
                <p className="mt-2 max-w-2xl text-body text-store-surface/80">
                    تصفّح أقسام مطبخنا — {listMeta?.total ?? 0} قسم
                    {listMeta?.from && listMeta?.to ? ` (عرض ${listMeta.from}–${listMeta.to})` : ''}.
                </p>
            </header>

            {categories.length > 0 ? (
                <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {categories.map((c) => (
                        <CategoryCard key={c.id} category={c} />
                    ))}
                </div>
            ) : (
                <EmptyState
                    className="mt-12"
                    title="لا توجد أقسام في القائمة"
                    description="سيتم إضافة أقسام جديدة قريباً."
                    actionLabel="تصفح المنتجات"
                    actionHref="/products"
                />
            )}

            <StorePagination pagination={pagination} />
        </StoreLayout>
    );
}
