import { Plus } from 'lucide-react';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = {
    id: number;
    name: string;
    price: number;
    imageUrl?: string | null;
    category?: string;
    disabled?: boolean;
    onAdd: (id: number) => void;
};

export default function PosProductCard({
    id,
    name,
    price,
    imageUrl,
    category,
    disabled = false,
    onAdd,
}: Props) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={() => onAdd(id)}
            className={cn(
                'flex h-full min-h-[104px] flex-col rounded-lg border border-slate-200 bg-white p-3 text-right shadow-sm transition-colors',
                'hover:border-slate-300 hover:bg-slate-50 active:bg-slate-100',
                disabled && 'pointer-events-none opacity-50',
            )}
        >
            {imageUrl && (
                <img
                    src={imageUrl}
                    alt={name}
                    className="mb-2.5 h-16 w-full rounded-md object-cover"
                />
            )}

            {category && (
                <span className="mb-1 text-[11px] font-medium text-slate-400">{category}</span>
            )}

            <span className="line-clamp-2 flex-1 text-sm font-semibold leading-snug text-slate-900">
                {name}
            </span>

            <div className="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5">
                <span className="text-[15px] font-bold tabular-nums text-slate-900">
                    {formatCurrency(price)}
                </span>
                <span className="flex size-7 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500">
                    <Plus className="size-3.5" />
                </span>
            </div>
        </button>
    );
}
