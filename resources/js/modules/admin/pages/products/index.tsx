import { Head, Link } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatCurrency } from '@/lib/format';
import { resolveMediaUrl } from '@/lib/media';
import type { Paginated } from '@/types/admin';

type ProductItem = {
    id: number;
    name: string;
    sku?: string | null;
    sale_price?: number;
    is_active?: boolean;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string } | null;
};

type Props = {
    product: Paginated<ProductItem>;
};

export default function ProductsIndex({ product }: Props) {
    const { t } = useI18n();
    const items = product.data ?? [];

    return (
        <>
            <Head title={t('admin.nav.products')} />
            <PageHeader
                title={t('admin.nav.products')}
                description={t('admin.products.description')}
                createHref="/admin/products/create"
                createLabel={t('admin.products.create')}
            />

            {items.length === 0 ? (
                <Card>
                    <CardContent className="py-16 text-center text-muted-foreground">
                        {t('admin.products.empty')}
                    </CardContent>
                </Card>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {items.map((item) => {
                            const src = resolveMediaUrl(item.image_url, item.image);

                            return (
                                <Card key={item.id} className="overflow-hidden">
                                    <div className="relative aspect-[4/5] bg-muted">
                                        {src ? (
                                            <img
                                                src={src}
                                                alt={item.name}
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex size-full flex-col items-center justify-center gap-2 text-muted-foreground">
                                                <ImageOff className="size-8 opacity-40" />
                                                <span className="text-xs">
                                                    {t('admin.products.no_image')}
                                                </span>
                                            </div>
                                        )}
                                        <div className="absolute end-2 top-2">
                                            <Badge
                                                variant={item.is_active ? 'default' : 'secondary'}
                                            >
                                                {item.is_active
                                                    ? t('common.active')
                                                    : t('common.inactive')}
                                            </Badge>
                                        </div>
                                    </div>
                                    <CardContent className="flex items-start justify-between gap-2 p-4">
                                        <div className="min-w-0">
                                            <Link
                                                href={`/admin/products/${item.id}/edit`}
                                                className="line-clamp-2 font-semibold hover:underline"
                                            >
                                                {item.name}
                                            </Link>
                                            <p className="mt-1 text-sm font-medium text-amber-900">
                                                {formatCurrency(Number(item.sale_price ?? 0))}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {item.category?.name ??
                                                    t('admin.products.no_category')}
                                                {item.sku ? ` · ${item.sku}` : ''}
                                            </p>
                                        </div>
                                        <ResourceActions
                                            editHref={`/admin/products/${item.id}/edit`}
                                            deleteHref={`/admin/products/${item.id}`}
                                            editPermission="products.update"
                                            deletePermission="products.delete"
                                        />
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                    <div className="mt-4">
                        <PaginationLinks paginator={product} />
                    </div>
                </>
            )}
        </>
    );
}
