import { Link } from '@inertiajs/react';
import { BarChart3, Clock } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';
import BusinessBrand from '@/components/domain/BusinessBrand';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/format';
import PosModeTabs, { type PosSaleMode } from './pos-mode-tabs';

type DailySession = {
    session_date: string;
    register_name?: string | null;
    invoice_count: number;
    total_sales: number;
};

type Props = {
    mode: PosSaleMode;
    onModeChange: (mode: PosSaleMode) => void;
    dailySession?: DailySession | null;
    children: ReactNode;
};

export default function PosShell({
    mode,
    onModeChange,
    dailySession = null,
    children,
}: Props) {
    const [time, setTime] = useState(() => new Date());

    useEffect(() => {
        const timer = setInterval(() => setTime(new Date()), 30_000);
        return () => clearInterval(timer);
    }, []);

    return (
        <div className="flex h-screen flex-col bg-slate-100 dark:bg-slate-950" dir="rtl">
            <header className="shrink-0 border-b border-slate-800 bg-slate-900 text-white shadow-lg">
                <div className="flex flex-wrap items-center gap-3 px-4 py-3">
                    <div className="flex items-center gap-2">
                        <BusinessBrand
                            iconWrapperClassName="bg-white/10"
                            textClassName="text-white [&_span]:text-white"
                        />
                    </div>

                    <PosModeTabs mode={mode} onChange={onModeChange} />

                    <div className="ms-auto flex flex-wrap items-center gap-2">
                        {dailySession && (
                            <div className="hidden items-center gap-2 rounded-lg bg-slate-800/80 px-3 py-1.5 text-sm sm:flex">
                                <span className="text-slate-400">{dailySession.session_date}</span>
                                <span className="text-slate-300">{dailySession.invoice_count} فاتورة</span>
                                <span className="font-semibold">{formatCurrency(dailySession.total_sales)}</span>
                            </div>
                        )}

                        <div className="flex items-center gap-1.5 rounded-lg bg-slate-800/80 px-3 py-1.5 text-sm text-slate-300">
                            <Clock className="size-4" />
                            {time.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' })}
                        </div>

                        <Button
                            variant="secondary"
                            size="sm"
                            className="gap-1.5 bg-amber-500 text-slate-900 hover:bg-amber-400"
                            asChild
                        >
                            <Link href="/pos/daily-summary">
                                <BarChart3 className="size-4" />
                                ملخص اليوم
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <div className="flex min-h-0 flex-1">{children}</div>
        </div>
    );
}
