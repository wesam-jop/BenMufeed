import { QrCode, ShoppingBag, Truck, UtensilsCrossed } from 'lucide-react';
import { cn } from '@/lib/utils';

export type PosSaleMode = 'takeaway' | 'delivery' | 'tables' | 'table-qr';

const MODES: Array<{ id: PosSaleMode; label: string; icon: typeof ShoppingBag }> = [
    { id: 'takeaway', label: 'بيع سفري', icon: ShoppingBag },
    { id: 'delivery', label: 'طلبات توصيل', icon: Truck },
    { id: 'tables', label: 'بيع طاولات', icon: UtensilsCrossed },
    { id: 'table-qr', label: 'طلبات QR', icon: QrCode },
];

type Props = {
    mode: PosSaleMode;
    onChange: (mode: PosSaleMode) => void;
    disabled?: boolean;
};

export default function PosModeTabs({ mode, onChange, disabled = false }: Props) {
    return (
        <div className="flex flex-wrap rounded-xl bg-slate-800 p-1">
            {MODES.map(({ id, label, icon: Icon }) => (
                <button
                    key={id}
                    type="button"
                    disabled={disabled}
                    onClick={() => onChange(id)}
                    className={cn(
                        'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all sm:px-4',
                        mode === id
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-300 hover:bg-slate-700 hover:text-white',
                        disabled && 'pointer-events-none opacity-50',
                    )}
                >
                    <Icon className="size-4" />
                    <span className="hidden lg:inline">{label}</span>
                </button>
            ))}
        </div>
    );
}
