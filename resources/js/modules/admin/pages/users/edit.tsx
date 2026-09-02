import { Head, Link, useForm } from '@inertiajs/react';
import PageHeader from '@/components/admin/page-header';
import UserFormFields from '@/components/admin/user-form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type BranchOption = { id: number; name: string };
type RoleOption = { id: number; name: string };

type UserDetail = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    branch_id: number | null;
    is_active: boolean;
    is_super_admin: boolean;
    roles: string[];
};

type Props = {
    user: UserDetail;
    branches: BranchOption[];
    roles: RoleOption[];
    canAssignRoles: boolean;
    canManageSuperAdmin: boolean;
};

export default function UsersEdit({
    user,
    branches,
    roles,
    canAssignRoles,
    canManageSuperAdmin,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        username: user.username,
        email: user.email ?? '',
        password: '',
        pin: '',
        branch_id: user.branch_id ? String(user.branch_id) : '',
        is_active: user.is_active,
        is_super_admin: user.is_super_admin,
        roles: user.roles,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    return (
        <>
            <Head title={`تعديل ${user.name}`} />
            <PageHeader title={`تعديل ${user.name}`} description="تحديث بيانات المستخدم والأدوار" />
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
                            isEdit
                        />
                        <div className="flex gap-2 pt-2">
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                تحديث
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/admin/users/${user.id}`}>إلغاء</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </>
    );
}
