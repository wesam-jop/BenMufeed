import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, KeyRound, RefreshCw } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { statusLabel } from '@/lib/format';

type TableItem = {
    id: number;
    name: string;
    capacity: number;
    zone?: string | null;
    status: string;
};

type Props = {
    restaurantTable: TableItem;
    unifiedQrUrl: string;
    tableAccessCode: string;
};

export default function RestaurantTableShow({
    restaurantTable,
    unifiedQrUrl,
    tableAccessCode,
}: Props) {
    return (
        <>
            <Head title={`طاولة ${restaurantTable.name}`} />
            <PageHeader
                title={restaurantTable.name}
                description="تفاصيل الطاولة — رمز التفعيل عام لكل الطاولات"
                actions={
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/restaurant-tables">
                                <ArrowRight className="size-4" />
                                رجوع
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/admin/restaurant-tables/${restaurantTable.id}/edit`}>تعديل</Link>
                        </Button>
                    </div>
                }
            />

            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardContent className="space-y-3 p-6">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">السعة</span>
                            <span className="font-medium">{restaurantTable.capacity}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">المنطقة</span>
                            <span className="font-medium">{restaurantTable.zone ?? '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">الحالة</span>
                            <Badge variant="secondary">{statusLabel(restaurantTable.status)}</Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-amber-100">
                    <CardContent className="space-y-4 p-6">
                        <p className="text-sm text-muted-foreground">رمز التفعيل العام (لكل الطاولات)</p>
                        <p
                            className="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 font-mono text-3xl font-bold text-amber-950"
                            dir="ltr"
                        >
                            <KeyRound className="size-6" />
                            {tableAccessCode}
                        </p>
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    '/admin/restaurant-tables/regenerate-shared-code',
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <RefreshCw className="size-4" />
                            توليد رمز عام جديد
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            الباركود الموحد يحتوي الرمز داخله. بعد التوليد أعد طباعته:{' '}
                            <a href={unifiedQrUrl} className="text-amber-800 underline" dir="ltr">
                                {unifiedQrUrl}
                            </a>
                        </p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
