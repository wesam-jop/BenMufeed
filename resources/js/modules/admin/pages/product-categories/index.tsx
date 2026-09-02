import { Head, Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import type { Paginated } from '@/types/admin';

type CategoryItem = {
    id: number;
    name: string;
    sort_order?: number;
    is_active?: boolean;
    image_url?: string | null;
    products_count?: number;
};

type Props = {
    productCategory: Paginated<CategoryItem>;
};

export default function ProductCategoriesIndex({ productCategory }: Props) {
    const { t } = useI18n();
    const items = productCategory.data ?? [];

    return (
        <>
            <Head title={t('admin.categories.title')} />
            <PageHeader
                title={t('admin.categories.title')}
                description={t('admin.categories.description')}
                createHref="/admin/product-categories/create"
                createLabel={t('admin.categories.create')}
            />

            {items.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center text-muted-foreground">
                        {t('admin.categories.empty')}
                    </CardContent>
                </Card>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {items.map((item) => (
                            <Card key={item.id} className="overflow-hidden">
                                <div className="relative aspect-[4/3] bg-muted">
                                    {item.image_url ? (
                                        <img
                                            src={item.image_url}
                                            alt={item.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground">
                                            <ImageOff className="size-8 opacity-40" />
                                            <span className="text-xs">
                                                {t('admin.categories.no_image')}
                                            </span>
                                        </div>
                                    )}
                                    <div className="absolute end-2 top-2">
                                        <Badge
                                            variant={
                                                item.is_active === false ? 'secondary' : 'default'
                                            }
                                        >
                                            {item.is_active === false
                                                ? t('common.hidden')
                                                : t('common.visible')}
                                        </Badge>
                                    </div>
                                </div>
                                <CardContent className="flex items-start justify-between gap-2 p-4">
                                    <div className="min-w-0">
                                        <Link
                                            href={`/admin/product-categories/${item.id}/edit`}
                                            className="font-semibold hover:underline"
                                        >
                                            {item.name}
                                        </Link>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {t('admin.categories.meta')
                                                .replace(
                                                    ':count',
                                                    String(item.products_count ?? 0),
                                                )
                                                .replace(
                                                    ':order',
                                                    String(item.sort_order ?? 0),
                                                )}
                                        </p>
                                    </div>
                                    <ResourceActions
                                        editHref={`/admin/product-categories/${item.id}/edit`}
                                        deleteHref={`/admin/product-categories/${item.id}`}
                                        editPermission="product-categories.update"
                                        deletePermission="product-categories.delete"
                                    />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    <div className="mt-4">
                        <PaginationLinks paginator={productCategory} />
                    </div>
                </>
            )}
        </>
    );
}
