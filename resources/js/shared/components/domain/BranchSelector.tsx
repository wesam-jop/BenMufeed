import { usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/hooks/use-i18n';
import type { Branch } from '@/types/auth';

type BranchSelectorProps = {
    onChange?: (branchId: string) => void;
    className?: string;
};

export default function BranchSelector({ onChange, className }: BranchSelectorProps) {
    const { branches, currentBranch } = usePage().props;
    const { t } = useI18n();
    const branchList = (branches ?? []) as Branch[];
    const active = currentBranch as Branch | null;

    if (branchList.length <= 1) {
        return active ? (
            <div className={`flex items-center gap-2 text-sm ${className ?? ''}`}>
                <Building2 className="size-4 text-muted-foreground" />
                <span>{active.name}</span>
            </div>
        ) : null;
    }

    return (
        <Select
            value={active?.id?.toString() ?? ''}
            onValueChange={onChange}
        >
            <SelectTrigger className={`w-[180px] ${className ?? ''}`}>
                <Building2 className="size-4" />
                <SelectValue placeholder={t('admin.branch.select')} />
            </SelectTrigger>
            <SelectContent>
                {branchList.map((branch) => (
                    <SelectItem key={branch.id} value={branch.id.toString()}>
                        {branch.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
