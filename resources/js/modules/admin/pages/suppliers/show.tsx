import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FileText, Pencil, Wallet } from 'lucide-react';
import PageHeader from '@/components/admin/page-header';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';

type SupplierDetail = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    tax_number: string | null;
    balance: number;
    is_active: boolean;
    purchase_invoices_count: number;
    payments_count: number;
};

type Props = {
    supplier: SupplierDetail;
};

export default function SuppliersShow({ supplier }: Props) {
    return (
        <>
            <Head title={supplier.name} />
            <PageHeader title={supplier.name} description="تفاصيل المورد وبيانات التواصل" />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/admin/suppliers">
                        <ArrowRight className="size-4" />
                        العودة للقائمة
                    </Link>
                </Button>
                <Button variant="outline" size="sm" asChild>
                    <Link href={`/admin/suppliers/${supplier.id}/edit`}>
                        <Pencil className="size-4" />
                        تعديل
                    </Link>
                </Button>
                <ResourceActions
                    deleteHref={`/admin/suppliers/${supplier.id}`}
                    deletePermission="suppliers.delete"
                />
            </div>

            <div className="mb-6">
                <Badge variant={supplier.is_active ? 'default' : 'secondary'}>
                    {supplier.is_active ? 'نشط' : 'موقوف'}
                </Badge>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    title="فواتير الشراء"
                    value={supplier.purchase_invoices_count}
                    format="number"
                    icon={FileText}
                />
                <StatCard
                    title="الرصيد المستحق"
                    value={supplier.balance}
                    format="currency"
                    icon={Wallet}
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>بيانات التواصل</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <DetailRow label="جهة الاتصال" value={supplier.contact_person} />
                    <DetailRow label="الهاتف" value={supplier.phone} />
                    <DetailRow label="البريد" value={supplier.email} />
                    <DetailRow label="الرقم الضريبي" value={supplier.tax_number} />
                    {supplier.address && (
                        <div className="sm:col-span-2">
                            <DetailRow label="العنوان" value={supplier.address} />
                        </div>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

function DetailRow({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="font-medium">{value ?? '—'}</p>
        </div>
    );
}
