import { usePage } from '@inertiajs/react';

/**
 * تخطيط مستقل لواجهة طلب الطاولة — مختلف عن واجهة الموقع الرسمية.
 * لا يحتوي روابط التوصيل/الحساب/الأقسام؛ تجربة مغلقة داخل الطاولة.
 *
 * @param {{ table?: { number: number, label: string }|null, children: import('react').ReactNode }} props
 */
export default function TableLayout({ table = null, children }) {
    const { site } = usePage().props;
    const siteName = site?.site_name || 'بن مفيد';
    const logo = site?.logo_url || null;

    return (
        <div className="min-h-screen bg-neutral-950 text-white" dir="rtl">
            <header className="sticky top-0 z-40 border-b border-white/10 bg-neutral-950/95 backdrop-blur">
                <div className="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
                    <div className="flex items-center gap-3">
                        {logo ? (
                            <img
                                src={logo}
                                alt={siteName}
                                className="h-10 w-10 rounded-xl object-contain ring-1 ring-amber-400/30"
                            />
                        ) : (
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400/15 text-lg font-black text-amber-300 ring-1 ring-amber-400/30">
                                {siteName.slice(0, 1)}
                            </span>
                        )}
                        <div className="leading-tight">
                            <p className="text-sm font-bold text-white">{siteName}</p>
                            <p className="text-[11px] text-amber-300/80">طلب من الطاولة</p>
                        </div>
                    </div>

                    {table ? (
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1.5 text-xs font-bold text-amber-200">
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
                                <path d="M4 10h16M6 10v9M18 10v9M5 10l1-4h12l1 4" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            {table.label}
                        </span>
                    ) : null}
                </div>
            </header>

            <main className="mx-auto max-w-3xl px-4 pb-[calc(6rem+env(safe-area-inset-bottom))] pt-5">
                {children}
            </main>
        </div>
    );
}
