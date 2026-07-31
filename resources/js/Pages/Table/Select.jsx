import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import TableLayout from '@/Layouts/TableLayout';

export default function Select({ tables = [], errors = {} }) {
    const [submitting, setSubmitting] = useState(false);
    const [selected, setSelected] = useState(null);

    const choose = (number) => {
        if (submitting) {
            return;
        }

        setSelected(number);
        setSubmitting(true);

        router.post(
            '/table/select',
            { table_number: number },
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <TableLayout>
            <Head title="اختر طاولتك" />

            <div className="text-center">
                <h1 className="text-xl font-black text-white">اختر رقم طاولتك</h1>
                <p className="mt-2 text-sm text-white/60">اضغط على رقم الطاولة التي تجلس عليها للبدء بالطلب.</p>
            </div>

            {errors.table_number ? (
                <p className="mx-auto mt-4 max-w-md rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-center text-sm font-medium text-red-300">
                    {errors.table_number}
                </p>
            ) : null}

            {tables.length === 0 ? (
                <p className="mt-10 text-center text-sm text-white/50">لا توجد طاولات مفعّلة حالياً.</p>
            ) : (
                <div className="mt-6 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    {tables.map((t) => {
                        const disabled = t.is_occupied || submitting;

                        return (
                            <button
                                key={t.number}
                                type="button"
                                disabled={disabled}
                                onClick={() => choose(t.number)}
                                className={`relative flex aspect-square flex-col items-center justify-center gap-1 rounded-2xl border text-center transition ${
                                    t.is_occupied
                                        ? 'cursor-not-allowed border-white/5 bg-white/[0.02] text-white/25'
                                        : selected === t.number
                                          ? 'border-amber-400 bg-amber-400/20 text-amber-100'
                                          : 'border-white/10 bg-neutral-900/60 text-white hover:border-amber-400/40 hover:bg-neutral-900'
                                }`}
                            >
                                <span className="text-2xl font-black leading-none">{t.number}</span>
                                <span className="text-[11px] font-medium opacity-70">
                                    {t.is_occupied ? 'مشغولة' : t.label}
                                </span>
                            </button>
                        );
                    })}
                </div>
            )}
        </TableLayout>
    );
}
