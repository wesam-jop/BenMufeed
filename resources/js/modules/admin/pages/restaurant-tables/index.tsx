import { Head, Link, router } from '@inertiajs/react';
import { Copy, KeyRound, QrCode, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import BrandedTableQrCard from '@/components/admin/branded-table-qr-card';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { statusLabel } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type TableItem = {
    id: number;
    name: string;
    capacity: number;
    zone?: string | null;
    status: string;
};

type Props = {
    restaurantTable: Paginated<TableItem>;
    unifiedQrUrl: string;
    tableAccessCode: string;
    businessName: string;
    logoUrl?: string | null;
    hasCustomQrLogo?: boolean;
};

export default function RestaurantTablesIndex({
    restaurantTable,
    unifiedQrUrl,
    tableAccessCode,
    businessName,
    logoUrl = null,
    hasCustomQrLogo = false,
}: Props) {
    const { t } = useI18n();
    const items = restaurantTable.data ?? [];

    const copyUrl = async () => {
        try {
            await navigator.clipboard.writeText(unifiedQrUrl);
            toast.success(t('toast.unified_qr_copied'));
        } catch {
            toast.error(t('toast.copy_failed'));
        }
    };

    const copyCode = async () => {
        try {
            await navigator.clipboard.writeText(tableAccessCode);
            toast.success(t('toast.access_code_copied'));
        } catch {
            toast.error(t('toast.copy_failed'));
        }
    };

    const regenerateShared = () => {
        router.post('/admin/restaurant-tables/regenerate-shared-code', {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('admin.tables.title')} />
            <PageHeader
                title={t('admin.tables.title')}
                description={t('admin.tables.description')}
                createHref="/admin/restaurant-tables/create"
                createLabel={t('admin.tables.new')}
            />

            <Card className="mb-6 border-amber-100">
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <QrCode className="size-5 text-amber-800" />
                        {t('admin.tables.unified_qr_title')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    <p className="text-sm text-muted-foreground">
                        {t('admin.tables.unified_qr_step1')}
                        <br />
                        {t('admin.tables.unified_qr_step2')}
                    </p>

                    <BrandedTableQrCard
                        url={unifiedQrUrl}
                        businessName={businessName}
                        defaultLogoUrl={logoUrl}
                        hasCustomQrLogo={hasCustomQrLogo}
                    />

                    <div className="rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-3">
                        <p className="text-xs font-medium text-amber-900">
                            {t('admin.tables.access_code_label')}
                        </p>
                        <div className="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                className="inline-flex items-center gap-1.5 font-mono text-2xl font-bold tracking-[0.25em] text-amber-950"
                                dir="ltr"
                            >
                                <KeyRound className="size-5" />
                                {tableAccessCode}
                            </span>
                            <Button type="button" variant="outline" size="sm" onClick={copyCode}>
                                <Copy className="size-4" />
                                {t('admin.tables.copy_code')}
                            </Button>
                            <Button type="button" variant="outline" size="sm" onClick={regenerateShared}>
                                <RefreshCw className="size-4" />
                                {t('admin.tables.regenerate_code')}
                            </Button>
                        </div>
                        <p className="mt-2 text-xs text-amber-800/80">
                            {t('admin.tables.regenerate_hint')}
                        </p>
                    </div>

                    <code className="block break-all rounded-lg bg-muted px-3 py-2 text-xs" dir="ltr">
                        {unifiedQrUrl}
                    </code>
                    <p className="text-xs text-muted-foreground">{t('admin.tables.url_hint')}</p>
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" onClick={copyUrl}>
                            <Copy className="size-4" />
                            {t('admin.tables.copy_url')}
                        </Button>
                        <Button type="button" asChild>
                            <Link href="/table-order" target="_blank">
                                {t('admin.tables.preview_page')}
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.tables.col_capacity')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                    <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                            {t('admin.tables.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">
                                                {item.name}
                                                {item.zone && (
                                                    <span className="ms-2 text-xs text-muted-foreground">
                                                        ({item.zone})
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{item.capacity}</td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">{statusLabel(item.status)}</Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap items-center gap-1">
                                                    <ResourceActions
                                                        editHref={`/admin/restaurant-tables/${item.id}/edit`}
                                                        deleteHref={`/admin/restaurant-tables/${item.id}`}
                                                        editPermission="restaurant-tables.update"
                                                        deletePermission="restaurant-tables.delete"
                                                    />
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/admin/restaurant-tables/${item.id}`}>
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={restaurantTable} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
