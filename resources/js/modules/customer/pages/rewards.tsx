import { Head } from '@inertiajs/react';
import StatCard from '@/components/domain/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Props = { points?: number; tier?: string };

export default function CustomerRewards({ points = 0, tier = 'برونزي' }: Props) {
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-3xl p-4">
            <Head title="المكافآت" />
            <h1 className="mb-6 text-2xl font-bold">برنامج المكافآت</h1>
            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                <StatCard title="نقاطك" value={points} format="number" />
                <StatCard title="المستوى" value={tier} />
            </div>
            <Card><CardHeader><CardTitle>المكافآت المتاحة</CardTitle></CardHeader>
                <CardContent><p className="text-muted-foreground">استبدل نقاطك بمشروبات مجانية</p></CardContent>
            </Card>
        </div>
    );
}