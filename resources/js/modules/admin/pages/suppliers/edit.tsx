import { Head, Link, useForm } from '@inertiajs/react';
import SupplierFormFields from '@/components/admin/supplier-form-fields';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type SupplierForm = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    tax_number: string | null;
    is_active: boolean;
};

type Props = {
    supplier: SupplierForm;
};

export default function SuppliersEdit({ supplier }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: supplier.name,
        contact_person: supplier.contact_person ?? '',
        phone: supplier.phone ?? '',
        email: supplier.email ?? '',
        address: supplier.address ?? '',
        tax_number: supplier.tax_number ?? '',
        is_active: supplier.is_active,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/suppliers/${supplier.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${supplier.name}`} />
            <PageHeader title="تعديل المورد" description={supplier.name} />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>بيانات المورد</CardTitle>
                    <CardDescription>
                        الاسم إلزامي — باقي الحقول اختيارية
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid gap-5">
                        <SupplierFormFields
                            data={data}
                            errors={errors}
                            setData={setData}
                        />

                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing || !data.name.trim()}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/suppliers/${supplier.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
