import { Head, router } from '@inertiajs/react';
import { ArrowDownLeft, ArrowUpRight, ClipboardList, Search, Wallet } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import StatCard from '@/components/domain/StatCard';
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
import { useI18n } from '@/hooks/use-i18n';
import {
    formatCurrency,
    formatDate,
    formatQuantity,
    stockMovementTypeLabel,
    stockMovementTypeVariant,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';

type MovementRow = {
    id: number;
    type: string;
    type_label: string;
    direction: 'in' | 'out' | 'neutral';
    product_name: string;
    product_sku: string | null;
    unit: { name: string; abbreviation: string } | null;
    warehouse_name: string;
    user_name: string | null;
    quantity: number;
    quantity_before: number | null;
    quantity_after: number | null;
    unit_cost: number | null;
    total_cost: number | null;
    notes: string | null;
    reason: string | null;
    created_at: string | null;
};

type Summary = {
    movements_count: number;
    inbound_count: number;
    outbound_count: number;
    total_value: number;
};

type TypeOption = { value: string; label: string };

type Filters = {
    search: string;
    type: string;
};

type Props = {
    movements: Paginated<MovementRow>;
    summary: Summary;
    filters: Filters;
    typeOptions: TypeOption[];
};

export default function InventoryMovements({ movements, summary, filters, typeOptions }: Props) {
    const { t } = useI18n();
    const items = movements.data ?? [];
    const [search, setSearch] = useState(filters.search);
    const [type, setType] = useState(filters.type || 'all');

    const applyFilters = () => {
        router.get(
            '/admin/inventory/movements',
            {
                search: search || undefined,
                type: type !== 'all' ? type : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        setSearch('');
        setType('all');
        router.get('/admin/inventory/movements', {}, { preserveState: true, replace: true });
    };

    const directionBadge = (direction: MovementRow['direction']) => {
        if (direction === 'in') {
            return (
                <Badge className="gap-1 border-emerald-200 bg-emerald-100 text-emerald-800 hover:bg-emerald-100">
                    <ArrowDownLeft className="size-3" />
                    {t('admin.movements.direction_in')}
                </Badge>
            );
        }

        if (direction === 'out') {
            return (
                <Badge variant="destructive" className="gap-1">
                    <ArrowUpRight className="size-3" />
                    {t('admin.movements.direction_out')}
                </Badge>
            );
        }

        return <Badge variant="secondary">{t('admin.movements.direction_adjust')}</Badge>;
    };

    return (
        <>
            <Head title={t('admin.nav.movements')} />
            <PageHeader
                title={t('admin.nav.movements')}
                description={t('admin.movements.description')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.movements.total')}
                    value={summary.movements_count}
                    format="number"
                    icon={ClipboardList}
                    description={t('admin.movements.filter_scope')}
                />
                <StatCard
                    title={t('admin.movements.inbound')}
                    value={summary.inbound_count}
                    format="number"
                    icon={ArrowDownLeft}
                    description={t('admin.movements.inbound_desc')}
                />
                <StatCard
                    title={t('admin.movements.outbound')}
                    value={summary.outbound_count}
                    format="number"
                    icon={ArrowUpRight}
                    description={t('admin.movements.outbound_desc')}
                />
                <StatCard
                    title={t('admin.movements.total_value')}
                    value={summary.total_value}
                    format="currency"
                    icon={Wallet}
                    description={t('admin.movements.total_value_desc')}
                />
            </div>

            <Card className="mb-6">
                <CardContent className="grid gap-4 pt-6 md:grid-cols-[1fr_220px_auto_auto] md:items-end">
                    <div className="grid gap-2">
                        <Label htmlFor="search">{t('admin.movements.search_label')}</Label>
                        <div className="relative">
                            <Search className="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                placeholder={t('admin.movements.search_placeholder')}
                                className="pe-10"
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="type">{t('admin.movements.type_label')}</Label>
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger id="type">
                                <SelectValue placeholder={t('admin.movements.all_types')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('admin.movements.all_types')}</SelectItem>
                                {typeOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Button type="button" onClick={applyFilters}>
                        {t('admin.movements.apply')}
                    </Button>
                    <Button type="button" variant="outline" onClick={clearFilters}>
                        {t('admin.movements.clear')}
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50 text-right">
                                    <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_material')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_type')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_direction')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_quantity')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_before_after')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_warehouse')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_value')}
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        {t('admin.movements.col_by')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={9}
                                            className="px-4 py-10 text-center text-muted-foreground"
                                        >
                                            {t('admin.movements.empty')}
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((item) => (
                                        <tr
                                            key={item.id}
                                            className={cn(
                                                'border-b hover:bg-muted/30',
                                                item.direction === 'in' && 'bg-emerald-50/40',
                                                item.direction === 'out' && 'bg-red-50/30',
                                            )}
                                        >
                                            <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                                {item.created_at
                                                    ? formatDate(item.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.product_name}</div>
                                                {item.product_sku && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.product_sku}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={stockMovementTypeVariant(item.type)}>
                                                    {stockMovementTypeLabel(item.type)}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {directionBadge(item.direction)}
                                            </td>
                                            <td
                                                className={cn(
                                                    'px-4 py-3 font-semibold',
                                                    item.direction === 'in' && 'text-emerald-700',
                                                    item.direction === 'out' && 'text-red-700',
                                                )}
                                            >
                                                {item.direction === 'in'
                                                    ? '+'
                                                    : item.direction === 'out'
                                                      ? '-'
                                                      : ''}
                                                {formatQuantity(item.quantity)}
                                                {item.unit ? ` ${item.unit.abbreviation}` : ''}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.quantity_before !== null &&
                                                item.quantity_after !== null ? (
                                                    <span>
                                                        {formatQuantity(item.quantity_before)} →{' '}
                                                        <span className="font-medium text-foreground">
                                                            {formatQuantity(item.quantity_after)}
                                                        </span>
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">{item.warehouse_name}</td>
                                            <td className="px-4 py-3 font-medium">
                                                {item.total_cost !== null
                                                    ? formatCurrency(item.total_cost)
                                                    : item.unit_cost !== null
                                                      ? formatCurrency(item.unit_cost * item.quantity)
                                                      : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.user_name ?? '—'}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="px-4 pb-4">
                        <PaginationLinks paginator={movements} />
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
