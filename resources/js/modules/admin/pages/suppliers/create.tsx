import { Head, Link, useForm } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import SupplierFormFields from '@/components/admin/supplier-form-fields';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

export default function SuppliersCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        tax_number: '',
        is_active: true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/suppliers');
    };

    return (
        <>
            <Head title="مورد جديد" />
            <PageHeader
                title="إضافة مورد"
                description="الاسم فقط إلزامي — يمكنك إكمال باقي البيانات لاحقاً"
            />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Truck className="size-5" />
                        بيانات المورد
                    </CardTitle>
                    <CardDescription>
                        أدخل اسم المورد على الأقل، ثم أي معلومات إضافية تريدها
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
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/suppliers">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
