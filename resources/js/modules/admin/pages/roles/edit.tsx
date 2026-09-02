import { Head, Link, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import RoleFormFields, { type PermissionGroup } from '@/components/admin/role-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { roleLabel } from '@/lib/format';

type RoleDetail = {
    id: number;
    name: string;
    label: string;
    permissions: string[];
    is_system: boolean;
};

type Props = {
    role: RoleDetail;
    permissionGroups: PermissionGroup[];
};

export default function RolesEdit({ role, permissionGroups }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: role.name,
        permissions: role.permissions,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/roles/${role.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${role.label || roleLabel(role.name)}`} />
            <PageHeader
                title={`تعديل ${role.label || roleLabel(role.name)}`}
                description="تحديث صلاحيات الدور"
            />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-4xl gap-6">
                        <RoleFormFields
                            data={data}
                            errors={errors}
                            setData={setData}
                            permissionGroups={permissionGroups}
                            isSystem={role.is_system}
                        />
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/roles/${role.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
