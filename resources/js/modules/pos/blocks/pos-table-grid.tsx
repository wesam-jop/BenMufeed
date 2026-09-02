import { Armchair, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type PosTable = {
    id: number;
    name: string;
    capacity?: number;
    zone?: string | null;
    status?: string;
    sessions?: Array<{ id: number; orders_count?: number }>;
};

type Props = {
    tables: PosTable[];
    selectedId?: number | null;
    onSelect: (table: PosTable) => void;
    disabled?: boolean;
};

const statusColors: Record<string, string> = {
    free: 'border-emerald-200 bg-emerald-50 hover:border-emerald-400',
    occupied: 'border-amber-200 bg-amber-50 hover:border-amber-400',
    reserved: 'border-blue-200 bg-blue-50 hover:border-blue-400',
    cleaning: 'border-slate-200 bg-slate-50 hover:border-slate-400',
};

const statusLabels: Record<string, string> = {
    free: 'متاحة',
    occupied: 'مشغولة',
    reserved: 'محجوزة',
    cleaning: 'تنظيف',
};

export default function PosTableGrid({ tables, selectedId, onSelect, disabled = false }: Props) {
    return (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            {tables.map((table) => {
                const status = table.status ?? 'free';
                const hasOpenOrder = (table.sessions?.[0]?.orders_count ?? 0) > 0;
                const isSelected = selectedId === table.id;

                return (
                    <button
                        key={table.id}
                        type="button"
                        disabled={disabled}
                        onClick={() => onSelect(table)}
                        className={cn(
                            'flex flex-col items-center gap-3 rounded-2xl border-2 p-6 transition-all',
                            statusColors[status] ?? statusColors.free,
                            isSelected && 'ring-2 ring-emerald-500 ring-offset-2',
                            disabled && 'pointer-events-none opacity-50',
                        )}
                    >
                        <div className="flex size-16 items-center justify-center rounded-full bg-white text-2xl font-bold shadow-inner">
                            {table.name.replace(/[^\d]/g, '') || (
                                <Armchair className="size-7 text-slate-500" strokeWidth={1.75} />
                            )}
                        </div>
                        <div className="text-center">
                            <p className="font-bold">{table.name}</p>
                            {table.zone && (
                                <p className="text-xs text-slate-500">{table.zone}</p>
                            )}
                        </div>
                        <div className="flex flex-wrap items-center justify-center gap-1.5">
                            <Badge variant="outline">{statusLabels[status] ?? status}</Badge>
                            {table.capacity && (
                                <span className="flex items-center gap-0.5 text-xs text-slate-500">
                                    <Users className="size-3" />
                                    {table.capacity}
                                </span>
                            )}
                            {hasOpenOrder && (
                                <Badge className="bg-amber-500">طلب مفتوح</Badge>
                            )}
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
