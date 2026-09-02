import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Pencil, Receipt, ShoppingCart, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, roleLabel } from '@/lib/format';

type UserDetail = {
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
    cash_register_sessions_count: number;
    last_login_at: string | null;
    created_at: string | null;
};

type Props = {
    user: UserDetail;
};

export default function UsersShow({ user }: Props) {
    const salesReportHref = `/admin/reports/sales?user_id=${user.id}`;

    return (
        <>
            <Head title={user.name} />
            <PageHeader title={user.name} description="تفاصيل حساب المستخدم والنشاط" />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/users">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/users/${user.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/users/${user.id}`}
                    deletePermission="users.delete"
                />
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={user.is_active ? 'default' : 'secondary'}>
                    {user.is_active ? 'نشط' : 'موقوف'}
                </Badge>
                {user.is_super_admin && <Badge>Super Admin</Badge>}
                {user.roles.map((role) => (
                    <Badge key={role} variant="outline">
                        {roleLabel(role)}
                    </Badge>
                ))}
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="الفواتير"
                    value={user.invoices_count}
                    format="number"
                    icon={Receipt}
                />
                <StatCard
                    title="الطلبات"
                    value={user.orders_count}
                    format="number"
                    icon={ShoppingCart}
                />
                <StatCard
                    title="جلسات الصندوق"
                    value={user.cash_register_sessions_count}
                    format="number"
                    icon={Wallet}
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>بيانات الحساب</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">اسم المستخدم</span>
                            <span className="font-mono">{user.username}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">البريد</span>
                            <span>{user.email ?? '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">الفرع</span>
                            <span>{user.branch_name ?? '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">تاريخ الإنشاء</span>
                            <span>
                                {user.created_at ? formatDate(user.created_at) : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">آخر دخول</span>
                            <span>
                                {user.last_login_at ? formatDate(user.last_login_at) : '—'}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>روابط سريعة</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {user.invoices_count > 0 && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={salesReportHref}>تقرير مبيعات هذا المستخدم</Link>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/cash-register-sessions">جلسات الصندوق</Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/roles">الأدوار والصلاحيات</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
