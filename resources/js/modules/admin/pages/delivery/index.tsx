import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useI18n } from '@/hooks/use-i18n';

type DeliveryOrder = {
    id: number;
    order_number?: string;
    customer_name?: string;
    status: string;
    address?: string;
};

type Props = { orders?: DeliveryOrder[] };

export default function DeliveryIndex({ orders = [] }: Props) {
    const { t } = useI18n();

    return (
        <>
            <Head title={t('admin.nav.delivery')} />
            <PageHeader
                title={t('admin.delivery.title')}
                description={t('admin.delivery.description')}
            />
            <Card>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50 text-right">
                                <th className="px-4 py-3">{t('admin.delivery.order')}</th>
                                <th className="px-4 py-3">{t('admin.delivery.customer')}</th>
                                <th className="px-4 py-3">{t('common.address')}</th>
                                <th className="px-4 py-3">{t('common.status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        {t('admin.delivery.empty')}
                                    </td>
                                </tr>
                            ) : (
                                orders.map((order) => (
                                    <tr key={order.id} className="border-b">
                                        <td className="px-4 py-3">
                                            #{order.order_number ?? order.id}
                                        </td>
                                        <td className="px-4 py-3">
                                            {order.customer_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">{order.address ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            <Badge>{order.status}</Badge>
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
