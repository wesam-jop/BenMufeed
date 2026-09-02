import { Head, Link, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import UserFormFields from '@/components/admin/user-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type BranchOption = { id: number; name: string };
type RoleOption = { id: number; name: string };

type Props = {
    branches: BranchOption[];
    roles: RoleOption[];
    canAssignRoles: boolean;
    canManageSuperAdmin: boolean;
};

export default function UsersCreate({
    branches,
    roles,
    canAssignRoles,
    canManageSuperAdmin,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        pin: '',
        branch_id: '',
        is_active: true,
        is_super_admin: false,
        roles: [] as string[],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/users');
    };

    return (
        <>
            <Head title="مستخدم جديد" />
            <PageHeader title="مستخدم جديد" description="إضافة حساب موظف جديد" />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="grid max-w-2xl gap-4">
                        <UserFormFields
                            data={data}
                            errors={errors}
                            setData={setData}
                            branches={branches}
                            roles={roles}
                            canAssignRoles={canAssignRoles}
                            canManageSuperAdmin={canManageSuperAdmin}
                        />
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                حفظ
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href="/admin/users">إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
