import { Head, Link, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import RoleFormFields, { type PermissionGroup } from '@/components/admin/role-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    permissionGroups: PermissionGroup[];
};

export default function RolesCreate({ permissionGroups }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/roles');
    };

    return (
        <>
            <Head title="دور جديد" />
            <PageHeader title="دور جديد" description="إنشاء دور مخصص وتحديد صلاحياته" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-4xl gap-6">
                        <RoleFormFields
                            data={data}
                            errors={errors}
                            setData={setData}
                            permissionGroups={permissionGroups}
                        />
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/roles">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
