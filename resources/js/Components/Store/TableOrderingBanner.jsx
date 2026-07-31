import { Link } from '@inertiajs/react';
import Icon from '@/Components/Ui/Icon';
import { useTableOrdering } from '@/Components/Store/TableOrderingProvider';

export default function TableOrderingBanner() {
    const { active, locked, table, loading, error, scanPolicy } = useTableOrdering();

    if (!active && !error) {
        return null;
    }

    if (error && !active) {
        return (
            <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p className="font-bold">تعذّر تفعيل الطاولة</p>
                <p className="mt-1">{error}</p>
                <p className="mt-2 text-xs text-red-800/80">
                    صوّر رمز QR على الطاولة بكاميرا جوالك واضغط الرابط لفتح القائمة.
                </p>
            </div>
        );
    }

    return (
        <div className="mb-4 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="flex items-center gap-2 font-bold">
                        <Icon name="table-cells" className="h-4 w-4 shrink-0" />
                        {loading ? 'جاري التحقق من الطاولة...' : table?.label || 'طاولة'}
                    </p>
                    <p className="mt-1 text-xs leading-relaxed text-emerald-900/80">
                        {locked
                            ? `طلبك مربوط بهذه الطاولة — الجلسة تنتهي بعد ${scanPolicy?.idle_minutes || 45} دقيقة بدون نشاط.`
                            : 'وضع الطاولة مفعّل — أضف أطباقك من القائمة.'}
                    </p>
                </div>
                <Link
                    href="/products"
                    className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-800"
                >
                    القائمة
                    <Icon name="arrow-left" className="h-3.5 w-3.5" />
                </Link>
            </div>
        </div>
    );
}
