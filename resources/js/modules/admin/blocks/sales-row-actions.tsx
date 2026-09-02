import { Link, router } from '@inertiajs/react';
import { Eye, Pencil, Printer, Trash2 } from 'lucide-react';
import PermissionGate from '@/components/PermissionGate';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useI18n } from '@/hooks/use-i18n';

type Props = {
    viewHref?: string;
    editHref?: string;
    printHref?: string;
    deleteHref?: string;
    viewPermission?: string;
    editPermission?: string;
    printPermission?: string;
    deletePermission?: string;
};

function ActionButton({
    label,
    href,
    onClick,
    openInNewTab = false,
    icon: Icon,
    className,
}: {
    label: string;
    href?: string;
    onClick?: () => void;
    openInNewTab?: boolean;
    icon: typeof Eye;
    className?: string;
}) {
    const button = href ? (
        <Button variant="ghost" size="icon" className={className} asChild>
            {openInNewTab ? (
                <a href={href} target="_blank" rel="noopener noreferrer">
                    <Icon className="size-4" />
                </a>
            ) : (
                <Link href={href}>
                    <Icon className="size-4" />
                </Link>
            )}
        </Button>
    ) : (
        <Button variant="ghost" size="icon" className={className} onClick={onClick}>
            <Icon className="size-4" />
        </Button>
    );

    return (
        <Tooltip>
            <TooltipTrigger asChild>{button}</TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

export default function SalesRowActions({
    viewHref,
    editHref,
    printHref,
    deleteHref,
    viewPermission,
    editPermission,
    printPermission,
    deletePermission,
}: Props) {
    const { t } = useI18n();

    const handleDelete = () => {
        if (!deleteHref) return;

        if (window.confirm(t('common.confirm_delete'))) {
            router.delete(deleteHref, { preserveScroll: true });
        }
    };

    return (
        <TooltipProvider delayDuration={200}>
            <div className="flex items-center justify-end gap-0.5">
                {viewHref && (
                    <PermissionGate permission={viewPermission ?? 'orders.view'}>
                        <ActionButton label={t('common.view')} href={viewHref} icon={Eye} />
                    </PermissionGate>
                )}
                {editHref && (
                    <PermissionGate permission={editPermission ?? 'sales.update'}>
                        <ActionButton
                            label={t('common.edit')}
                            href={editHref}
                            icon={Pencil}
                            className="text-blue-600 hover:text-blue-700"
                        />
                    </PermissionGate>
                )}
                {printHref && (
                    <PermissionGate permission={printPermission ?? 'sales.print_invoice'}>
                        <ActionButton
                            label={t('common.print')}
                            href={printHref}
                            icon={Printer}
                            openInNewTab
                        />
                    </PermissionGate>
                )}
                {deleteHref && (
                    <PermissionGate permission={deletePermission ?? 'sales.cancel'}>
                        <ActionButton
                            label={t('common.delete')}
                            icon={Trash2}
                            className="text-destructive hover:text-destructive"
                            onClick={handleDelete}
                        />
                    </PermissionGate>
                )}
            </div>
        </TooltipProvider>
    );
}
