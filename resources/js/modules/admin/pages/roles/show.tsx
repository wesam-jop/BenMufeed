import { Head, Link } from '@inertiajs/react';
import { ArrowRight, KeyRound, Pencil, Users } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, roleLabel } from '@/lib/format';

type PermissionItem = {
    name: string;
    label: string;
    assigned: boolean;
};

type PermissionGroup = {
    group: string;
    group_label: string;
    permissions: PermissionItem[];
    assigned_count: number;
};

type RoleDetail = {
    id: number;
    name: string;
    label: string;
    permissions_count: number;
    users_count: number;
    is_system: boolean;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    role: RoleDetail;
    permissionGroups: PermissionGroup[];
};

export default function RolesShow({ role, permissionGroups }: Props) {
    const title = role.label || roleLabel(role.name);

    return (
        <>
            <Head title={title} />
            <PageHeader title={title} description="تفاصيل الدور والصلاحيات المعيّنة" />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/roles">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/roles/${role.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                {!role.is_system && (
                    <ResourceActions
                        deleteHref={`/admin/roles/${role.id}`}
                        deletePermission="roles.delete"
                    />
                )}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={role.is_system ? 'default' : 'secondary'}>
                    {role.is_system ? 'دور نظام' : 'دور مخصص'}
                </Badge>
                <Badge variant="outline" className="font-mono">
                    {role.name}
                </Badge>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="الصلاحيات"
                    value={role.permissions_count}
                    format="number"
                    icon={KeyRound}
                />
                <StatCard
                    title="المستخدمون"
                    value={role.users_count}
                    format="number"
                    icon={Users}
                />
            </div>

            <div className="mb-6 grid gap-4 text-sm sm:grid-cols-2">
                <Card>
                    <CardContent className="grid gap-3 pt-6">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">تاريخ الإنشاء</span>
                            <span>{role.created_at ? formatDate(role.created_at) : '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">آخر تحديث</span>
                            <span>{role.updated_at ? formatDate(role.updated_at) : '—'}</span>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="flex flex-wrap items-center gap-2 pt-6">
                        {role.users_count > 0 && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/admin/users?role=${encodeURIComponent(role.name)}`}>
                                    عرض المستخدمين ({role.users_count})
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/users/create">إضافة مستخدم</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>الصلاحيات حسب المجموعة</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4">
                    {permissionGroups.map((group) => (
                        <div key={group.group} className="rounded-lg border p-4">
                            <div className="mb-3 flex items-center justify-between gap-4">
                                <p className="font-medium">{group.group_label}</p>
                                <Badge variant="secondary">
                                    {group.assigned_count} / {group.permissions.length}
                                </Badge>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {group.permissions
                                    .filter((permission) => permission.assigned)
                                    .map((permission) => (
                                        <Badge key={permission.name} variant="outline">
                                            {permission.label}
                                        </Badge>
                                    ))}
                                {group.assigned_count === 0 && (
                                    <span className="text-sm text-muted-foreground">
                                        لا توجد صلاحيات في هذه المجموعة
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </>
    );
}
