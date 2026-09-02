import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Mail, MailOpen, MessageSquare, Phone } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';

type ContactMessage = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
    message: string;
    is_read: boolean;
    whatsapp_url?: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    message: ContactMessage;
};

export default function SiteMessageShow({ message }: Props) {
    const markUnread = () => {
        router.patch(`/admin/site/messages/${message.id}/unread`, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`رسالة من ${message.name}`} />
            <PageHeader
                title={`رسالة من ${message.name}`}
                description="تفاصيل رسالة التواصل الواردة من الموقع"
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/site/messages">
                        <ArrowRight className="size-4" />
                        العودة للرسائل
                    </Link>
                </Button>
                {message.is_read && (
                    <Button variant="outline" size="sm" onClick={markUnread}>
                        <Mail className="size-4" />
                        تعليم كغير مقروءة
                    </Button>
                )}
                <ResourceActions
                    deleteHref={`/admin/site/messages/${message.id}`}
                    deletePermission="settings.update"
                />
            </div>

            <div className="mb-6">
                <Badge variant={message.is_read ? 'secondary' : 'default'}>
                    {message.is_read ? 'مقروءة' : 'جديدة'}
                </Badge>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>بيانات المرسل</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">الاسم</span>
                            <span className="font-medium">{message.name}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">الهاتف</span>
                            <span dir="ltr">{message.phone ?? '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">البريد</span>
                            <span dir="ltr">{message.email ?? '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">تاريخ الإرسال</span>
                            <span>
                                {message.created_at ? formatDate(message.created_at) : '—'}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>رد سريع</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {message.phone && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={`tel:${message.phone}`}>
                                    <Phone className="size-4" />
                                    اتصال
                                </a>
                            </Button>
                        )}
                        {message.whatsapp_url && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={message.whatsapp_url} target="_blank" rel="noreferrer">
                                    <MessageSquare className="size-4" />
                                    واتساب
                                </a>
                            </Button>
                        )}
                        {message.email && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={`mailto:${message.email}`}>
                                    <MailOpen className="size-4" />
                                    بريد
                                </a>
                            </Button>
                        )}
                        {!message.phone && !message.email && (
                            <p className="text-sm text-muted-foreground">
                                لا توجد بيانات تواصل للرد
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>نص الرسالة</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="whitespace-pre-line text-sm leading-relaxed">{message.message}</p>
                </CardContent>
            </Card>
        </>
    );
}
