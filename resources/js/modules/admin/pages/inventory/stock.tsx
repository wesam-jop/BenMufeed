import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';
import { formatNumber } from '@/lib/format';

type StockItem = { id: number; name: string; sku?: string; quantity: number; min_stock?: number };

type Props = { stock?: StockItem[] };

export default function InventoryStock({ stock = [] }: Props) {
    const { t } = useI18n();

    return (
        <>
            <Head title={t('admin.nav.stock')} />
            <PageHeader
                title={t('admin.nav.stock')}
                description={t('admin.stock.description')}
            />
            <Card>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50 text-right">
                                <th className="px-4 py-3">{t('admin.stock.col_product')}</th>
                                <th className="px-4 py-3">{t('admin.stock.col_sku')}</th>
                                <th className="px-4 py-3">{t('admin.stock.col_quantity')}</th>
                                <th className="px-4 py-3">{t('admin.stock.col_min')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stock.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        {t('admin.stock.empty')}
                                    </td>
                                </tr>
                            ) : (
                                stock.map((item) => (
                                    <tr key={item.id} className="border-b">
                                        <td className="px-4 py-3">{item.name}</td>
                                        <td className="px-4 py-3">{item.sku ?? '-'}</td>
                                        <td className="px-4 py-3">{formatNumber(item.quantity)}</td>
                                        <td className="px-4 py-3">
                                            {item.min_stock != null
                                                ? formatNumber(item.min_stock)
                                                : '-'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </>
    );
}
