import SocialMeta from '@/Components/Store/SocialMeta';
import { Link, usePage } from '@inertiajs/react';
import StoreLayout from '@/Layouts/StoreLayout';
import { readTableContext, saveTableContext, saveTableSession, setTableToken } from '@/lib/cart';
import { formatPrice } from '@/lib/formatPrice';
import { useEffect } from 'react';

export default function OrderThankYou({ seo, checkout }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';
    const isTable = checkout.service_type === 'table';

    useEffect(() => {
        if (!isTable || !checkout.table_qr_token || !checkout.customer_session_token) {
            return;
        }

        setTableToken(checkout.table_qr_token);
        saveTableSession({
            qrToken: checkout.table_qr_token,
            session: checkout.customer_session_token,
        });

        const existing = readTableContext();
        saveTableContext({
            qrToken: checkout.table_qr_token,
            number: checkout.table_number || existing?.number || 0,
            label: checkout.table_label || existing?.label || `طاولة ${checkout.table_number || ''}`.trim(),
            locked: existing?.locked ?? true,
            scanSession: existing?.scanSession || null,
            session: checkout.customer_session_token,
        });
    }, [checkout.customer_session_token, checkout.table_label, checkout.table_number, checkout.table_qr_token, isTable]);

    return (
        <StoreLayout>
            <SocialMeta seo={seo} />

            <div className="mx-auto max-w-lg store-card-raised p-8 text-center animate-scale-in">
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-semantic-success-bg text-semantic-success">
                    <svg className="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden>
                        <path d="M5 13l4 4L19 7" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </div>
                <p className="store-section-eyebrow text-store-secondary">تم تسجيل طلبك</p>
                <h1 className="font-display mt-3 text-h2 text-store-primary">شكراً لك!</h1>
                <p className="mt-3 text-body text-store-muted">
                    رقم الطلب: <span className="font-bold text-store-primary">#{checkout.order_id}</span>
                </p>
                <p className="mt-2 text-h3 font-bold text-store-secondary">
                    الإجمالي: {formatPrice(checkout.total, symbol)}
                </p>
                {isTable ? (
                    <p className="mt-4 text-body text-store-muted">
                        طلبك أُضيف لـ{checkout.table_number ? `طاولة ${checkout.table_number}` : 'الطاولة'} — يمكنك إضافة
                        المزيد من الأطباق في أي وقت حتى يغلق الكاشير الحساب.
                    </p>
                ) : (
                    <p className="mt-4 text-body text-store-muted">
                        طلبك السفري وصل للمطعم — سنتواصل معك للتوصيل.
                    </p>
                )}
                <div className="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    {isTable ? (
                        <Link href="/cart" className="store-btn-primary">
                            أضف المزيد للطاولة
                        </Link>
                    ) : null}
                    <Link href="/products" className={isTable ? 'store-btn-outline' : 'store-btn-primary'}>
                        العودة للقائمة
                    </Link>
                </div>
            </div>
        </StoreLayout>
    );
}
