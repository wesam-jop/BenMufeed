import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { roleLabel } from '@/lib/format';

type BranchOption = { id: number; name: string };
type RoleOption = { id: number; name: string };

export type UserFormData = {
    name: string;
    username: string;
    email: string;
    password: string;
    pin: string;
    branch_id: string;
    is_active: boolean;
    is_super_admin: boolean;
    roles: string[];
};

type Props = {
    data: UserFormData;
    errors: Partial<Record<keyof UserFormData | 'roles.0', string>>;
    setData: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void;
    branches: BranchOption[];
    roles: RoleOption[];
    canAssignRoles: boolean;
    canManageSuperAdmin: boolean;
    isEdit?: boolean;
};

function OptionalLabel({ htmlFor, children }: { htmlFor: string; children: React.ReactNode }) {
    return (
        <Label htmlFor={htmlFor} className="font-normal">
            {children}{' '}
            <span className="text-xs text-muted-foreground">(اختياري)</span>
        </Label>
    );
}

export default function UserFormFields({
    data,
    errors,
    setData,
    branches,
    roles,
    canAssignRoles,
    canManageSuperAdmin,
    isEdit = false,
}: Props) {
    const toggleRole = (roleName: string, checked: boolean) => {
        if (checked) {
            setData('roles', [...data.roles, roleName]);
            return;
        }

        setData(
            'roles',
            data.roles.filter((role) => role !== roleName),
        );
    };

    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">الاسم *</Label>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="username">اسم المستخدم *</Label>
                    <Input
                        id="username"
                        type="text"
                        value={data.username}
                        onChange={(e) => setData('username', e.target.value)}
                        dir="ltr"
                        className="text-left"
                        required
                    />
                    <InputError message={errors.username} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <OptionalLabel htmlFor="email">البريد</OptionalLabel>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        dir="ltr"
                        className="text-left"
                    />
                    <InputError message={errors.email} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="branch_id">الفرع</Label>
                    <Select
                        value={data.branch_id || '__none__'}
                        onValueChange={(value) =>
                            setData('branch_id', value === '__none__' ? '' : value)
                        }
                    >
                        <SelectTrigger id="branch_id" className="h-11 w-full">
                            <SelectValue placeholder="بدون فرع" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">بدون فرع</SelectItem>
                            {branches.map((branch) => (
                                <SelectItem key={branch.id} value={String(branch.id)}>
                                    {branch.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.branch_id} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="password">
                        {isEdit ? 'كلمة المرور الجديدة' : 'كلمة المرور *'}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required={!isEdit}
                        placeholder={isEdit ? 'اتركها فارغة للإبقاء على الحالية' : undefined}
                    />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <OptionalLabel htmlFor="pin">رمز PIN للنقطة البيع</OptionalLabel>
                    <Input
                        id="pin"
                        type="password"
                        inputMode="numeric"
                        value={data.pin}
                        onChange={(e) => setData('pin', e.target.value)}
                        maxLength={6}
                        placeholder={isEdit ? 'اتركه فارغاً للإبقاء على الحالي' : '4–6 أرقام'}
                        dir="ltr"
                        className="text-left"
                    />
                    <InputError message={errors.pin} />
                </div>
            </div>

            {canAssignRoles && (
                <div className="rounded-lg border bg-muted/30 p-4">
                    <p className="mb-4 text-sm font-medium">الأدوار</p>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {roles.map((role) => (
                            <label
                                key={role.id}
                                htmlFor={`role-${role.name}`}
                                className="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    id={`role-${role.name}`}
                                    checked={data.roles.includes(role.name)}
                                    onCheckedChange={(checked) =>
                                        toggleRole(role.name, checked === true)
                                    }
                                />
                                <span>{roleLabel(role.name)}</span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.roles as string | undefined} />
                </div>
            )}

            <div className="flex flex-wrap gap-6">
                <label htmlFor="is_active" className="flex items-center gap-2 text-sm">
                    <Checkbox
                        id="is_active"
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked === true)}
                    />
                    <span>حساب نشط</span>
                </label>
                {canManageSuperAdmin && (
                    <label htmlFor="is_super_admin" className="flex items-center gap-2 text-sm">
                        <Checkbox
                            id="is_super_admin"
                            checked={data.is_super_admin}
                            onCheckedChange={(checked) =>
                                setData('is_super_admin', checked === true)
                            }
                        />
                        <span>مدير نظام (Super Admin)</span>
                    </label>
                )}
            </div>
        </>
    );
}
