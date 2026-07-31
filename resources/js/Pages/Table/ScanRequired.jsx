import { Head } from '@inertiajs/react';
import TableLayout from '@/Layouts/TableLayout';

export default function ScanRequired() {
    return (
        <TableLayout>
            <Head title="امسح باركود الطاولة" />

            <div className="mx-auto mt-10 max-w-md rounded-3xl border border-white/10 bg-neutral-900/60 p-8 text-center">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-amber-400/10 text-amber-300 ring-1 ring-amber-400/30">
                    <svg className="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
                        <path d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2" strokeLinecap="round" />
                        <path d="M4 12h16" strokeLinecap="round" />
                    </svg>
                </div>

                <h1 className="mt-6 text-xl font-black text-white">امسح باركود الطاولة للبدء</h1>
                <p className="mt-3 text-sm leading-relaxed text-white/60">
                    للطلب من الطاولة، وجّه كاميرا جوالك نحو الباركود الموجود على الطاولة.
                    الجلسة صالحة لمدة 4 ساعات، وبعد انتهائها يلزم مسح الباركود من جديد.
                </p>

                <p className="mt-5 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-xs text-white/50">
                    لا يمكن الدخول إلى صفحات الطلب مباشرةً عبر الرابط — يجب مسح الباركود أولاً.
                </p>
            </div>
        </TableLayout>
    );
}
