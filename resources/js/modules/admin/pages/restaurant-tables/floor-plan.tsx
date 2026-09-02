import { Head } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Table = { id: number; name: string; capacity: number; status?: string; x?: number; y?: number };

type Props = { tables?: Table[] };

export default function FloorPlan({ tables = [] }: Props) {
    return (
        <>
            <Head title="مخطط الصالة" />
            <PageHeader title="مخطط الصالة" description="عرض تخطيط الطاولات" />
            <Card><CardContent className="p-6">
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    {tables.length === 0 ? (
                        <p className="col-span-full py-8 text-center text-muted-foreground">لا توجد طاولات</p>
                    ) : tables.map((table) => (
                        <div key={table.id} className="flex aspect-square flex-col items-center justify-center rounded-lg border-2 border-dashed p-4 text-center">
                            <span className="text-lg font-bold">{table.name}</span>
                            <span className="text-sm text-muted-foreground">{table.capacity} أشخاص</span>
                            <Badge className="mt-2" variant="secondary">{table.status ?? 'متاح'}</Badge>
                        </div>
                    ))}
                </div>
            </CardContent></Card>
        </>
    );
}