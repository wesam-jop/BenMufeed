import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PermissionItem = { name: string; label: string };

export type PermissionGroup = {
    group: string;
    group_label: string;
    permissions: PermissionItem[];
};

export type RoleFormData = {
    name: string;
    permissions: string[];
};

type Props = {
    data: RoleFormData;
    errors: Partial<Record<keyof RoleFormData | 'permissions.0', string>>;
    setData: <K extends keyof RoleFormData>(key: K, value: RoleFormData[K]) => void;
    permissionGroups: PermissionGroup[];
    isSystem?: boolean;
};

export default function RoleFormFields({
    data,
    errors,
    setData,
    permissionGroups,
    isSystem = false,
}: Props) {
    const togglePermission = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
            return;
        }

        setData(
            'permissions',
            data.permissions.filter((permission) => permission !== permissionName),
        );
    };

    const toggleGroup = (group: PermissionGroup, checked: boolean) => {
        const groupNames = group.permissions.map((permission) => permission.name);

        if (checked) {
            setData('permissions', [...new Set([...data.permissions, ...groupNames])]);
            return;
        }

        setData(
            'permissions',
            data.permissions.filter((permission) => !groupNames.includes(permission)),
        );
    };

    const isGroupFullySelected = (group: PermissionGroup) =>
        group.permissions.every((permission) => data.permissions.includes(permission.name));

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">اسم الدور *</Label>
                <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="مثال: supervisor"
                    dir="ltr"
                    className="text-left"
                    disabled={isSystem}
                    required
                />
                {isSystem && (
                    <p className="text-xs text-muted-foreground">
                        اسم دور النظام لا يمكن تغييره
                    </p>
                )}
                <InputError message={errors.name} />
            </div>

            <div className="space-y-4">
                <div>
                    <p className="font-medium">الصلاحيات</p>
                    <p className="text-sm text-muted-foreground">
                        {data.permissions.length} صلاحية محددة
                    </p>
                </div>

                {permissionGroups.map((group) => (
                    <div key={group.group} className="rounded-lg border bg-muted/20 p-4">
                        <label
                            htmlFor={`group-${group.group}`}
                            className="mb-3 flex items-center gap-2 font-medium"
                        >
                            <Checkbox
                                id={`group-${group.group}`}
                                checked={isGroupFullySelected(group)}
                                onCheckedChange={(checked) =>
                                    toggleGroup(group, checked === true)
                                }
                            />
                            <span>{group.group_label}</span>
                            <span className="text-xs font-normal text-muted-foreground">
                                ({group.permissions.length})
                            </span>
                        </label>
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {group.permissions.map((permission) => (
                                <label
                                    key={permission.name}
                                    htmlFor={`permission-${permission.name}`}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        id={`permission-${permission.name}`}
                                        checked={data.permissions.includes(permission.name)}
                                        onCheckedChange={(checked) =>
                                            togglePermission(permission.name, checked === true)
                                        }
                                    />
                                    <span>{permission.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                ))}
                <InputError message={errors.permissions as string | undefined} />
            </div>
        </>
    );
}
