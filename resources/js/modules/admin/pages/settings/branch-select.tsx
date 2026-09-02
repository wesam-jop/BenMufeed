import AdminLayout from '@/layouts/admin-layout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/hooks/use-i18n';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Branch = {
    id: number;
    name: string;
    code: string;
};

export default function BranchSelect({
    branches,
    currentBranchId,
}: {
    branches: Branch[];
    currentBranchId: number | null;
}) {
    const { t } = useI18n();
    const [branchId, setBranchId] = useState(
        currentBranchId?.toString() ?? branches[0]?.id?.toString() ?? '',
    );
    const { post, processing } = useForm({ branch_id: branchId });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/branch/select');
    };

    return (
        <AdminLayout
            breadcrumbs={[{ title: t('admin.branch.title'), href: '/admin/branch/select' }]}
        >
            <Head title={t('admin.branch.title')} />
            <div className="mx-auto max-w-md p-6">
                <h1 className="mb-6 text-2xl font-bold">{t('admin.branch.select')}</h1>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="branch">{t('common.branch')}</Label>
                        <Select value={branchId} onValueChange={setBranchId}>
                            <SelectTrigger id="branch">
                                <SelectValue placeholder={t('admin.branch.placeholder')} />
                            </SelectTrigger>
                            <SelectContent>
                                {branches.map((branch) => (
                                    <SelectItem key={branch.id} value={branch.id.toString()}>
                                        {branch.name} ({branch.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" disabled={processing || !branchId}>
                        {t('admin.branch.continue')}
                    </Button>
                </form>
            </div>
        </AdminLayout>
    );
}
