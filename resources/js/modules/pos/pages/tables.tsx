import { Head, Link } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Table = { id: number; name: string; status?: string; capacity?: number };

type Props = { tables: Table[] };

export default function PosTables({ tables = [] }: Props) {
    return (
        <div dir="rtl" className="min-h-screen p-4">
            <Head title="طاولات POS" />
            <PageHeader title="الطاولات" description="اختر طاولة لبدء الطلب" />
            <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {tables.map((table) => (
                    <Link key={table.id} href={`/pos/tables/${table.id}/order`}>
                        <Card className="cursor-pointer transition-shadow hover:shadow-md">
                            <CardContent className="flex flex-col items-center p-6">
                                <span className="text-2xl font-bold">{table.name}</span>
                                <Badge className="mt-2">{table.status ?? 'متاح'}</Badge>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}