import { Head, Link, router } from '@inertiajs/react';
import { Eye, Filter, RotateCcw, Shield, UserCheck, Users } from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { formatDate, roleLabel } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type UserRow = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    is_active: boolean;
    is_super_admin: boolean;
    branch_id: number | null;
    branch_name: string | null;
    roles: string[];
    orders_count: number;
    invoices_count: number;
    last_login_at: string | null;
};

type Summary = {
    users_count: number;
    active_count: number;
    super_admin_count: number;
    with_sales_count: number;
};

type FilterOption = { value: string; label: string };

type Filters = {
    search: string;
    status: string;
    role: string;
};

type Props = {
    users: Paginated<UserRow>;
    summary: Summary;
    filters: Filters;
    filterOptions: {
        statuses: FilterOption[];
        roles: FilterOption[];
    };
};

export default function UsersIndex({ users, summary, filters, filterOptions }: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const items = users.data ?? [];

    const updateFilter = (key: keyof Filters, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = () => {
        router.get('/admin/users', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = { search: '', status: 'all', role: '' };
        setLocalFilters(reset);
        router.get('/admin/users', reset, { preserveState: true });
    };

    return (
        <>
            <Head title={t('admin.users.title')} />
            <PageHeader
                title={t('admin.users.title')}
                description={t('admin.users.description')}
                createHref="/admin/users/create"
                createLabel={t('admin.users.new')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.users.stat_count')}
                    value={summary.users_count}
                    format="number"
                    icon={Users}
                    description={t('admin.users.stat_active_desc').replace(
                        ':count',
                        String(summary.active_count),
                    )}
                />
                <StatCard
                    title={t('admin.users.stat_super_admins')}
                    value={summary.super_admin_count}
                    format="number"
                    icon={Shield}
                />
                <StatCard
                    title={t('admin.users.stat_with_sales')}
                    value={summary.with_sales_count}
                    format="number"
                    icon={UserCheck}
                    description={t('admin.users.stat_with_sales_desc')}
                />
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('common.filters')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div className="grid gap-2">
                            <Label htmlFor="status">{t('common.status')}</Label>
                            <Select
                                value={localFilters.status}
                                onValueChange={(value) => updateFilter('status', value)}
                            >
                                <SelectTrigger id="status" className="h-11 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {filterOptions.statuses.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="role">{t('common.role')}</Label>
                            <Select
                                value={localFilters.role || '__all__'}
                                onValueChange={(value) =>
                                    updateFilter('role', value === '__all__' ? '' : value)
                                }
                            >
                                <SelectTrigger id="role" className="h-11 w-full">
                                    <SelectValue placeholder={t('admin.users.all_roles')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">{t('admin.users.all_roles')}</SelectItem>
                                    {filterOptions.roles.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="search">{t('common.search')}</Label>
                            <Input
                                id="search"
                                type="search"
                                value={localFilters.search}
                                onChange={(e) => updateFilter('search', e.target.value)}
                                placeholder={t('admin.users.search_placeholder')}
                            />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={applyFilters}>{t('common.apply_filters')}</Button>
                        <Button variant="outline" onClick={resetFilters}>
                            <RotateCcw className="size-4" />
                            {t('common.reset')}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <Users className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">{t('admin.users.empty')}</p>
                        <Button asChild>
                            <Link href="/admin/users/create">{t('admin.users.new')}</Link>
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">{t('common.name')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('common.username')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.branch')}</th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.users.col_roles')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.users.col_invoices')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.users.col_last_login')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <p className="font-medium">{item.name}</p>
                                                {item.email && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {item.email}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-muted-foreground">
                                                {item.username}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.branch_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-1">
                                                    {item.is_super_admin && (
                                                        <Badge variant="default">
                                                            {t('admin.users.super_admin')}
                                                        </Badge>
                                                    )}
                                                    {item.roles.map((role) => (
                                                        <Badge key={role} variant="secondary">
                                                            {roleLabel(role)}
                                                        </Badge>
                                                    ))}
                                                    {!item.is_super_admin && item.roles.length === 0 && (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">{item.invoices_count}</td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {item.last_login_at
                                                    ? formatDate(item.last_login_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={item.is_active ? 'default' : 'secondary'}
                                                >
                                                    {item.is_active
                                                        ? t('common.active')
                                                        : t('common.suspended')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/users/${item.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/users/${item.id}/edit`}
                                                        deleteHref={`/admin/users/${item.id}`}
                                                        editPermission="users.update"
                                                        deletePermission="users.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={users} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
