import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DeliveryMapPicker from '@/Components/Store/DeliveryMapPicker';
import { withStoreLayout } from '@/Layouts/StoreLayout';
import { createSavedAddress, deleteSavedAddress, updateStorefrontProfile } from '@/lib/storefrontAccount';
import { formatPrice } from '@/lib/formatPrice';

export default withStoreLayout(AccountPage);

function AccountPage({ customer, addresses = [], recentOrders = [] }) {
    const { site } = usePage().props;
    const symbol = site?.currency_symbol || '$';
    const [name, setName] = useState(customer?.name || '');
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);
    const [error, setError] = useState(null);
    const [showAddAddress, setShowAddAddress] = useState(false);
    const [addressLabel, setAddressLabel] = useState('');
    const [addressNote, setAddressNote] = useState('');
    const [addressLatitude, setAddressLatitude] = useState(null);
    const [addressLongitude, setAddressLongitude] = useState(null);
    const [savingAddress, setSavingAddress] = useState(false);

    const saveProfile = async () => {
        setSaving(true);
        setMessage(null);
        setError(null);

        try {
            await updateStorefrontProfile(name.trim());
            setMessage('تم حفظ الاسم.');
            router.reload({ only: ['storefrontCustomer'] });
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    const removeAddress = async (id) => {
        try {
            await deleteSavedAddress(id);
            router.reload();
        } catch (err) {
            setError(err.message);
        }
    };

    const resetAddressForm = () => {
        setAddressLabel('');
        setAddressNote('');
        setAddressLatitude(null);
        setAddressLongitude(null);
        setShowAddAddress(false);
    };

    const saveAddress = async () => {
        if (!addressLabel.trim()) {
            setError('يرجى تسمية الموقع (مثال: البيت، العمل).');
            return;
        }

        if (addressLatitude == null || addressLongitude == null) {
            setError('حدّد موقعك على الخريطة.');
            return;
        }

        if (!addressNote.trim()) {
            setError('أضف وصفاً للموقع (مثال: بناء أبيض، الطابق الثاني).');
            return;
        }

        setSavingAddress(true);
        setError(null);
        setMessage(null);

        try {
            await createSavedAddress({
                label: addressLabel,
                locationNote: addressNote,
                latitude: addressLatitude,
                longitude: addressLongitude,
            });
            resetAddressForm();
            setMessage('تم حفظ الموقع.');
            router.reload();
        } catch (err) {
            setError(err.message);
        } finally {
            setSavingAddress(false);
        }
    };

    return (
        <>
            <h1 className="font-display text-3xl font-bold text-store-primary">حسابي</h1>
            <p className="mt-2 text-sm text-store-muted">بياناتك ومواقع التوصيل المحفوظة وطلباتك الأخيرة.</p>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <section className="store-card p-6">
                    <h2 className="text-lg font-bold text-store-primary">المعلومات الشخصية</h2>
                    <p className="mt-1 text-xs text-store-muted">
                        {customer?.is_verified ? (
                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800">
                                موثّق عبر واتساب
                            </span>
                        ) : (
                            customer?.account_type_label
                        )}
                    </p>

                    <label htmlFor="account_name" className="mt-4 block text-sm font-medium text-store-primary">
                        الاسم
                    </label>
                    <input
                        id="account_name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className="store-input mt-2"
                    />

                    {customer?.phone ? (
                        <p className="mt-4 text-sm text-store-muted">
                            واتساب: <span className="font-semibold text-store-primary">{customer.phone}</span>
                        </p>
                    ) : (
                        <p className="mt-4 text-sm text-store-muted">لم يُضف رقم واتساب بعد — يُطلب عند أول طلب سفري أو توصيل.</p>
                    )}

                    {message ? <p className="mt-3 text-sm text-emerald-700">{message}</p> : null}
                    {error ? <p className="mt-3 text-sm text-red-700">{error}</p> : null}

                    <button
                        type="button"
                        disabled={saving || !name.trim()}
                        onClick={saveProfile}
                        className="store-btn-primary mt-4 disabled:opacity-40"
                    >
                        {saving ? 'جاري الحفظ…' : 'حفظ الاسم'}
                    </button>
                </section>

                <section className="store-card p-6">
                    <div className="flex items-start justify-between gap-3">
                        <h2 className="text-lg font-bold text-store-primary">مواقع التوصيل المحفوظة</h2>
                        {customer?.is_verified && !showAddAddress ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setError(null);
                                    setMessage(null);
                                    setShowAddAddress(true);
                                }}
                                className="shrink-0 text-sm font-semibold text-store-secondary hover:text-store-primary"
                            >
                                + إضافة موقع
                            </button>
                        ) : null}
                    </div>

                    {!customer?.is_verified ? (
                        <p className="mt-3 text-sm text-store-muted">
                            للحفظ والإضافة،{' '}
                            <Link href="/account/login" className="font-semibold text-store-secondary hover:underline">
                                تحقق عبر واتساب
                            </Link>{' '}
                            أولاً.
                        </p>
                    ) : null}

                    {customer?.is_verified && showAddAddress ? (
                        <div className="mt-4 space-y-4 rounded-xl border border-store-primary/10 bg-store-cream/40 p-4">
                            <div>
                                <label htmlFor="address_label" className="block text-sm font-medium text-store-primary">
                                    اسم الموقع *
                                </label>
                                <input
                                    id="address_label"
                                    value={addressLabel}
                                    onChange={(e) => setAddressLabel(e.target.value)}
                                    placeholder="مثال: البيت، العمل"
                                    className="store-input mt-2"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-store-primary">الموقع على الخريطة *</label>
                                <p className="mt-1 text-xs text-store-muted">اضغط على الخريطة لتحديد موقع التوصيل</p>
                                <div className="mt-2">
                                    <DeliveryMapPicker
                                        latitude={addressLatitude}
                                        longitude={addressLongitude}
                                        onChange={({ latitude, longitude }) => {
                                            setAddressLatitude(latitude);
                                            setAddressLongitude(longitude);
                                        }}
                                    />
                                </div>
                            </div>

                            <div>
                                <label htmlFor="address_note" className="block text-sm font-medium text-store-primary">
                                    وصف الموقع *
                                </label>
                                <textarea
                                    id="address_note"
                                    value={addressNote}
                                    onChange={(e) => setAddressNote(e.target.value)}
                                    rows={2}
                                    placeholder="مثال: بناء أبيض، الطابق الثاني، باب أزرق…"
                                    className="store-input mt-2"
                                />
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    disabled={savingAddress}
                                    onClick={saveAddress}
                                    className="store-btn-primary disabled:opacity-40"
                                >
                                    {savingAddress ? 'جاري الحفظ…' : 'حفظ الموقع'}
                                </button>
                                <button
                                    type="button"
                                    disabled={savingAddress}
                                    onClick={resetAddressForm}
                                    className="text-sm font-semibold text-store-muted hover:text-store-primary"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    ) : null}

                    {addresses.length === 0 ? (
                        customer?.is_verified ? (
                            <p className="mt-3 text-sm text-store-muted">
                                لا توجد مواقع محفوظة بعد — اضغط «إضافة موقع» لحفظ عنوان التوصيل.
                            </p>
                        ) : null
                    ) : (
                        <ul className="mt-4 space-y-3">
                            {addresses.map((address) => (
                                <li
                                    key={address.id}
                                    className="flex items-start justify-between gap-3 rounded-xl border border-store-primary/10 bg-store-cream/50 px-4 py-3"
                                >
                                    <div>
                                        <p className="font-bold text-store-primary">
                                            {address.label}
                                            {address.is_default ? (
                                                <span className="ms-2 text-xs font-normal text-store-muted">(افتراضي)</span>
                                            ) : null}
                                        </p>
                                        <p className="mt-1 text-xs text-store-muted">{address.location_note}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => removeAddress(address.id)}
                                        className="shrink-0 text-xs font-semibold text-red-600 hover:text-red-700"
                                    >
                                        حذف
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <section className="store-card mt-6 p-6">
                <h2 className="text-lg font-bold text-store-primary">آخر الطلبات</h2>
                {recentOrders.length === 0 ? (
                    <p className="mt-3 text-sm text-store-muted">لا توجد طلبات مرتبطة بحسابك بعد.</p>
                ) : (
                    <ul className="mt-4 divide-y divide-store-primary/10">
                        {recentOrders.map((order) => (
                            <li key={order.id} className="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <p className="font-semibold text-store-primary">طلب #{order.id}</p>
                                    <p className="text-xs text-store-muted">{order.service_type}</p>
                                </div>
                                <p className="font-bold text-store-secondary">{formatPrice(order.total, symbol)}</p>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <div className="mt-8 flex flex-wrap items-center gap-4">
                <Link href="/products" className="text-sm font-semibold text-store-primary hover:text-store-secondary">
                    ← العودة للقائمة
                </Link>
                {!customer?.is_verified ? (
                    <Link
                        href="/account/login"
                        className="text-sm font-semibold text-store-secondary hover:text-store-primary"
                    >
                        إكمال التحقق عبر واتساب
                    </Link>
                ) : null}
            </div>
        </>
    );
}
