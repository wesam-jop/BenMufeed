import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

type PermissionGateProps = {
    permission?: string | string[];
    any?: boolean;
    fallback?: ReactNode;
    children: ReactNode;
};

export function hasPermission(
    permissions: string[],
    required: string | string[],
    any = false,
): boolean {
    const requiredList = Array.isArray(required) ? required : [required];

    if (requiredList.length === 0) {
        return true;
    }

    if (any) {
        return requiredList.some((p) => permissions.includes(p));
    }

    return requiredList.every((p) => permissions.includes(p));
}

export default function PermissionGate({
    permission,
    any = false,
    fallback = null,
    children,
}: PermissionGateProps) {
    const { auth } = usePage().props;
    const permissions = auth.permissions ?? auth.user?.permissions ?? [];

    if (auth.user?.is_super_admin) {
        return children;
    }

    if (!permission) {
        return children;
    }

    if (!hasPermission(permissions, permission, any)) {
        return fallback;
    }

    return children;
}
