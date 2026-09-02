import { Head, Link } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export default function TableSessionsCreate() {
    return (
        <>
            <Head title="إضافة جلسة طاولة" />
            <PageHeader title="إضافة جلسة طاولة" description="الجلسات تُفتح تلقائياً من الكاشير" />
            <Card>
                <CardContent className="py-10 text-center text-muted-foreground">
                    <p className="mb-4">جلسات الطاولات تُنشأ من نقطة البيع عند فتح طاولة.</p>
                    <Button asChild>
                        <Link href="/admin/table-sessions">رجوع للقائمة</Link>
                    </Button>
                </CardContent>
            </Card>
        </>
    );
}
