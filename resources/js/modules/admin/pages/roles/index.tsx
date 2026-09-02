import { Head, Link, router } from '@inertiajs/react';
import { Eye, Filter, KeyRound, RotateCcw, Shield, Users } from 'lucide-react';
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
import { useI18n } from '@/hooks/use-i18n';
import { roleLabel } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type RoleRow = {
    id: number;
    name: string;
    label: string;
    permissions_count: number;
    users_count: number;
    is_system: boolean;
};

type Summary = {
    roles_count: number;
    system_roles_count: number;
    assigned_users_count: number;
    permissions_count: number;
};

type Filters = {
    search: string;
};

type Props = {
    roles: Paginated<RoleRow>;
    summary: Summary;
    filters: Filters;
};

export default function RolesIndex({ roles, summary, filters }: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const items = roles.data ?? [];

    const applyFilters = () => {
        router.get('/admin/roles', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = { search: '' };
        setLocalFilters(reset);
        router.get('/admin/roles', reset, { preserveState: true });
    };

    return (
        <>
            <Head title={t('admin.roles.title')} />
            <PageHeader
                title={t('admin.roles.title')}
                description={t('admin.roles.description')}
                createHref="/admin/roles/create"
                createLabel={t('admin.roles.new')}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    title={t('admin.roles.stat_count')}
                    value={summary.roles_count}
                    format="number"
                    icon={Shield}
                    description={t('admin.roles.stat_system_desc').replace(
                        ':count',
                        String(summary.system_roles_count),
                    )}
                />
                <StatCard
                    title={t('admin.roles.stat_permissions')}
                    value={summary.permissions_count}
                    format="number"
                    icon={KeyRound}
                />
                <StatCard
                    title={t('admin.roles.stat_assigned_users')}
                    value={summary.assigned_users_count}
                    format="number"
                    icon={Users}
                />
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('common.search')}
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-[1fr_auto_auto]">
                    <div className="grid gap-2">
                        <Label htmlFor="search">{t('admin.roles.search_label')}</Label>
                        <Input
                            id="search"
                            type="search"
                            value={localFilters.search}
                            onChange={(e) =>
                                setLocalFilters((prev) => ({ ...prev, search: e.target.value }))
                            }
                            placeholder={t('admin.roles.search_placeholder')}
                        />
                    </div>
                    <Button className="self-end" onClick={applyFilters}>
                        {t('common.apply')}
                    </Button>
                    <Button className="self-end" variant="outline" onClick={resetFilters}>
                        <RotateCcw className="size-4" />
                        {t('common.reset')}
                    </Button>
                </CardContent>
            </Card>

            {items.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <Shield className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">{t('admin.roles.empty')}</p>
                        <Button asChild>
                            <Link href="/admin/roles/create">{t('admin.roles.new')}</Link>
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
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.roles.col_role')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.roles.col_permissions')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.roles.col_users')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.roles.col_type')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <tr key={item.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3">
                                                <p className="font-medium">
                                                    {item.label || roleLabel(item.name)}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {item.name}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">{item.permissions_count}</td>
                                            <td className="px-4 py-3">
                                                {item.users_count > 0 ? (
                                                    <Link
                                                        href={`/admin/users?role=${encodeURIComponent(item.name)}`}
                                                        className="hover:underline"
                                                    >
                                                        {item.users_count}
                                                    </Link>
                                                ) : (
                                                    '0'
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={item.is_system ? 'default' : 'secondary'}
                                                >
                                                    {item.is_system
                                                        ? t('admin.roles.type_system')
                                                        : t('admin.roles.type_custom')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/roles/${item.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        editHref={`/admin/roles/${item.id}/edit`}
                                                        deleteHref={`/admin/roles/${item.id}`}
                                                        editPermission="roles.update"
                                                        deletePermission="roles.delete"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={roles} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
