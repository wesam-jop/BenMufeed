import { Head } from '@inertiajs/react';
import StatCard from '@/components/domain/StatCard';
import { useI18n } from '@/hooks/use-i18n';
import { DollarSign, Package, Receipt, ShoppingCart } from 'lucide-react';

type Props = {
    stats: {
        orders_count: number;
        orders_total: number;
        invoices_count: number;
        invoices_total: number;
        expenses_total: number;
        stock_movements_count: number;
    };
};

export default function Dashboard({ stats }: Props) {
    const { t } = useI18n();

    return (
        <>
            <Head title={t('admin.dashboard.title')} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">{t('admin.dashboard.title')}</h1>
                    <p className="text-muted-foreground">{t('admin.dashboard.subtitle')}</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard
                        title={t('admin.dashboard.orders_today')}
                        value={stats.orders_count}
                        format="number"
                        icon={ShoppingCart}
                        description={t('admin.dashboard.orders_total_desc')}
                    />
                    <StatCard
                        title={t('admin.dashboard.orders_sales')}
                        value={stats.orders_total}
                        format="currency"
                        icon={DollarSign}
                    />
                    <StatCard
                        title={t('admin.dashboard.invoices')}
                        value={stats.invoices_count}
                        format="number"
                        icon={Receipt}
                    />
                    <StatCard
                        title={t('admin.dashboard.invoices_revenue')}
                        value={stats.invoices_total}
                        format="currency"
                        icon={DollarSign}
                    />
                    <StatCard
                        title={t('admin.dashboard.expenses')}
                        value={stats.expenses_total}
                        format="currency"
                        icon={Receipt}
                    />
                    <StatCard
                        title={t('admin.dashboard.stock_movements')}
                        value={stats.stock_movements_count}
                        format="number"
                        icon={Package}
                    />
                </div>
            </div>
        </>
    );
}
