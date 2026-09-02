import { Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import PermissionGate from '@/components/PermissionGate';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/hooks/use-i18n';

type ResourceActionsProps = {
    editHref?: string;
    deleteHref?: string;
    editPermission?: string;
    deletePermission?: string;
    confirmMessage?: string;
};

export default function ResourceActions({
    editHref,
    deleteHref,
    editPermission,
    deletePermission,
    confirmMessage,
}: ResourceActionsProps) {
    const { t } = useI18n();
    const confirmText = confirmMessage ?? t('common.confirm_delete');

    const handleDelete = () => {
        if (!deleteHref) {
            return;
        }

        if (window.confirm(confirmText)) {
            router.delete(deleteHref, { preserveScroll: true });
        }
    };

    return (
        <div className="flex items-center justify-end gap-1">
            {editHref && (
                <PermissionGate permission={editPermission}>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={editHref}>
                            <Pencil className="size-4" />
                        </Link>
                    </Button>
                </PermissionGate>
            )}
            {deleteHref && (
                <PermissionGate permission={deletePermission}>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive"
                        onClick={handleDelete}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </PermissionGate>
            )}
        </div>
    );
}
