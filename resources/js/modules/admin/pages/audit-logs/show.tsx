import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ClipboardList, Globe, Monitor, User } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { auditEventVariant, formatDate } from '@/lib/format';

type ChangedField = {
    field: string;
    old: unknown;
    new: unknown;
};

type AuditLogDetail = {
    id: number;
    event: string;
    event_label: string;
    auditable_type: string | null;
    auditable_type_label: string;
    auditable_id: number | null;
    user_id: number | null;
    user_name: string | null;
    ip: string | null;
    user_agent: string | null;
    created_at: string | null;
    changes_count: number;
    user: {
        id: number;
        name: string;
        username: string;
        email: string | null;
    } | null;
    old_values: Record<string, unknown>;
    new_values: Record<string, unknown>;
    changed_fields: ChangedField[];
};

type Props = {
    auditLog: AuditLogDetail;
};

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    return String(value);
}

export default function AuditLogsShow({ auditLog }: Props) {
    return (
        <>
            <Head title={`سجل #${auditLog.id}`} />
            <PageHeader
                title={auditLog.event_label}
                description={`سجل تدقيق #${auditLog.id}`}
            />

            <div className="mb-6">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/audit-logs">
                        <ArrowRight className="size-4" />
                        العودة للسجل
                    </Link>
                </Button>
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Badge variant={auditEventVariant(auditLog.event)}>{auditLog.event_label}</Badge>
                <Badge variant="outline">{auditLog.event}</Badge>
            </div>

            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardList className="size-4" />
                            تفاصيل الحدث
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">نوع السجل</span>
                            <span>{auditLog.auditable_type_label}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">المعرف</span>
                            <span>{auditLog.auditable_id ? `#${auditLog.auditable_id}` : '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">التاريخ</span>
                            <span>
                                {auditLog.created_at ? formatDate(auditLog.created_at) : '—'}
                            </span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">عدد الحقول</span>
                            <span>{auditLog.changes_count}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <User className="size-4" />
                            المستخدم والجلسة
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">المستخدم</span>
                            <span>
                                {auditLog.user ? (
                                    <Link
                                        href={`/admin/users/${auditLog.user.id}`}
                                        className="hover:underline"
                                    >
                                        {auditLog.user.name}
                                    </Link>
                                ) : (
                                    '—'
                                )}
                            </span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">IP</span>
                            <span dir="ltr" className="font-mono text-xs">
                                {auditLog.ip ?? '—'}
                            </span>
                        </div>
                        {auditLog.user_agent && (
                            <div className="grid gap-1">
                                <span className="flex items-center gap-1 text-muted-foreground">
                                    <Monitor className="size-3.5" />
                                    User Agent
                                </span>
                                <p className="break-all text-xs text-muted-foreground">
                                    {auditLog.user_agent}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Globe className="size-4" />
                        التغييرات
                    </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    {auditLog.changed_fields.length === 0 ? (
                        <p className="px-6 py-12 text-center text-sm text-muted-foreground">
                            لا توجد قيم مسجّلة للمقارنة
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">الحقل</th>
                                        <th className="px-4 py-3 font-medium">القيمة السابقة</th>
                                        <th className="px-4 py-3 font-medium">القيمة الجديدة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {auditLog.changed_fields.map((row) => (
                                        <tr key={row.field} className="border-b align-top">
                                            <td className="px-4 py-3 font-medium">{row.field}</td>
                                            <td className="px-4 py-3">
                                                <pre className="whitespace-pre-wrap break-all text-xs text-muted-foreground">
                                                    {formatValue(row.old)}
                                                </pre>
                                            </td>
                                            <td className="px-4 py-3">
                                                <pre className="whitespace-pre-wrap break-all text-xs">
                                                    {formatValue(row.new)}
                                                </pre>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </>
    );
}
