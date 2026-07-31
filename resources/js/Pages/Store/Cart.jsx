import { Link, router, usePage } from '@inertiajs/react';
import Icon from '@/Components/Ui/Icon';
import CustomerOtpVerification from '@/Components/Store/CustomerOtpVerification';
import PhoneField from '@/Components/Store/PhoneField';
import SocialMeta from '@/Components/Store/SocialMeta';
import DeliveryMapPicker from '@/Components/Store/DeliveryMapPicker';
import { useTableOrdering } from '@/Components/Store/TableOrderingProvider';
import { useEffect, useMemo, useState } from 'react';
import { withStoreLayout } from '@/Layouts/StoreLayout';
import {
    clearCart,
    clearTableContext,
    clearTableSession,
    clearTableToken,
    readCart,
    readTableContext,
    readTableSession,
    readTableToken,
    removeItem,
    resolveTableToken,
    saveTableSession,
    setQuantity,
    saveTableContext,
    setTableToken,
    syncCartWithServer,
} from '@/lib/cart';
import { formatPrice } from '@/lib/formatPrice';
import { formatPhone, splitCountryKey } from '@/lib/phoneCountries';

const btnPrimary = 'store-btn-primary w-full disabled:cursor-not-allowed disabled:opacity-40';

const btnSecondary =
    'w-full rounded-xl border-2 border-store-primary/15 py-3 text-sm font-bold text-store-primary transition hover:border-store-secondary/50 hover:bg-store-cream';

const inputClass = 'store-input';

export default withStoreLayout(CartContent);

function parseStoredPhone(phone, countries, defaultKey) {
    const digits = String(phone || '').replace(/\D/g, '');

    for (const country of countries || []) {
        const dial = String(country.dial || '').replace(/\D/g, '');

        if (dial && digits.startsWith(dial)) {
            return {
                countryKey: `${dial}|${country.iso}`,
                local: digits.slice(dial.length),
            };
        }
    }

    return { countryKey: defaultKey, local: digits };
}

function CartContent({ seo, tableToken: tableTokenFromUrl = null }) {
    const { errors, site, phoneCountries, deliveryFee = 0, storefrontCustomer } = usePage().props;
    const { active: tableModeActive, locked: tableModeLocked, table: tableContext, refresh: refreshTableContext } =
        useTableOrdering();
    const symbol = site?.currency_symbol || '$';
    const defaultCountryKey = phoneCountries?.defaultKey || '963|SY';

    const [cart, setCart] = useState({ items: [] });
    const [serviceType, setServiceType] = useState('delivery');
    const [note, setNote] = useState('');
    const [customerName, setCustomerName] = useState('');
    const [customerPhoneCountry, setCustomerPhoneCountry] = useState(defaultCountryKey);
    const [customerPhoneLocal, setCustomerPhoneLocal] = useState('');
    const [deliveryLocationNote, setDeliveryLocationNote] = useState('');
    const [deliveryLatitude, setDeliveryLatitude] = useState(null);
    const [deliveryLongitude, setDeliveryLongitude] = useState(null);
    const [selectedAddressId, setSelectedAddressId] = useState(null);
    const [saveAddress, setSaveAddress] = useState(false);
    const [addressLabel, setAddressLabel] = useState('');
    const [isVerified, setIsVerified] = useState(storefrontCustomer?.is_verified === true);
    const [tableToken, setTableTokenState] = useState(null);
    const [tableInfo, setTableInfo] = useState(null);
    const [tableError, setTableError] = useState(null);
    const [syncNotice, setSyncNotice] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [syncing, setSyncing] = useState(true);

    const refresh = () => setCart(readCart());

    const applyTableToken = async (token) => {
        if (!token) {
            return;
        }

        try {
            const existing = readTableContext();

            const info = await resolveTableToken(token, {
                scanSession: existing?.qrToken === token ? existing.scanSession : null,
                freshScan: false,
            });

            if (!info.can_order) {
                clearTableContext();
                setTableTokenState(null);
                setTableInfo(info);
                setTableError(`الطاولة ${info.label} مفتوحة — لا يمكن الطلب عليها من هذا الجهاز.`);
                setServiceType('delivery');

                return;
            }

            if (!info.is_occupied) {
                clearTableSession();
            }

            saveTableContext({
                qrToken: token,
                number: info.number,
                label: info.label,
                locked: existing?.locked ?? false,
                scanSession: info.scan_session,
                session: readTableSession()?.qrToken === token ? readTableSession()?.session : existing?.session,
            });

            setTableTokenState(token);
            setTableInfo(info);
            setTableError(null);
            setServiceType('table');
        } catch (err) {
            if (err.code === 'scan_session_expired' || err.code === 'scan_session_required') {
                clearTableContext();
                setTableTokenState(null);
                setTableInfo(null);
                setTableError('انتهت جلسة الطاولة — افتح رابط QR على الطاولة من كاميرا جوالك من جديد.');
                return;
            }

            setTableError('رمز الطاولة غير صالح — افتح رابط QR من كاميرا جوالك.');
            setTableInfo(null);
        }
    };

    useEffect(() => {
        let active = true;

        (async () => {
            setSyncing(true);
            const result = await syncCartWithServer();

            if (!active) {
                return;
            }

            setCart(result.cart);

            if (result.priceChanged || result.removedUnavailable) {
                setSyncNotice('تم تحديث طلبك وفق الأسعار الحالية في القائمة.');
            }

            const initialToken = tableTokenFromUrl || (tableModeLocked ? tableContext?.qrToken : null) || readTableToken();

            if (initialToken) {
                await applyTableToken(initialToken);
            } else if (tableModeActive && tableContext?.qrToken) {
                await applyTableToken(tableContext.qrToken);
            }

            setSyncing(false);
        })();

        return () => {
            active = false;
        };
    }, [tableTokenFromUrl, tableModeActive, tableModeLocked, tableContext?.qrToken]);

    useEffect(() => {
        if (tableModeLocked || tableModeActive) {
            setServiceType('table');
        } else if (serviceType === 'table') {
            setServiceType('delivery');
        }
    }, [tableModeLocked, tableModeActive, serviceType]);

    useEffect(() => {
        setIsVerified(storefrontCustomer?.is_verified === true);

        if (!storefrontCustomer?.is_verified) {
            return;
        }

        if (storefrontCustomer.name && !storefrontCustomer.needs_profile_for_pickup) {
            setCustomerName(storefrontCustomer.name);
        }

        if (storefrontCustomer.phone) {
            const parsed = parseStoredPhone(
                storefrontCustomer.phone,
                phoneCountries?.countries || [],
                defaultCountryKey,
            );
            setCustomerPhoneCountry(parsed.countryKey);
            setCustomerPhoneLocal(parsed.local);
        }

        const defaultAddress = (storefrontCustomer.addresses || []).find((a) => a.is_default)
            || storefrontCustomer.addresses?.[0];

        if (defaultAddress && serviceType === 'delivery') {
            setSelectedAddressId(defaultAddress.id);
            setDeliveryLatitude(defaultAddress.latitude);
            setDeliveryLongitude(defaultAddress.longitude);
            setDeliveryLocationNote(defaultAddress.location_note);
        }
    }, [storefrontCustomer, defaultCountryKey, serviceType]);

    const savedAddresses = storefrontCustomer?.addresses || [];

    const selectSavedAddress = (address) => {
        if (!address) {
            setSelectedAddressId(null);
            setDeliveryLatitude(null);
            setDeliveryLongitude(null);
            setDeliveryLocationNote('');
            setSaveAddress(false);
            setAddressLabel('');
            return;
        }

        setSelectedAddressId(address.id);
        setDeliveryLatitude(address.latitude);
        setDeliveryLongitude(address.longitude);
        setDeliveryLocationNote(address.location_note);
        setSaveAddress(false);
        setAddressLabel('');
    };

    const total = useMemo(
        () => cart.items.reduce((sum, i) => sum + i.price * i.quantity, 0),
        [cart],
    );
    const deliveryRestrictedItems = useMemo(
        () => cart.items.filter((item) => item.is_delivery_available === false),
        [cart],
    );

    const formattedPhone = useMemo(() => {
        const { dial } = splitCountryKey(customerPhoneCountry);

        return formatPhone(dial, customerPhoneLocal);
    }, [customerPhoneCountry, customerPhoneLocal]);

    const activeTableToken = tableToken || tableContext?.qrToken || null;
    const canSubmitTable =
        serviceType === 'table' && activeTableToken && tableInfo?.can_order !== false;
    const canSubmitPickup =
        serviceType === 'takeaway' && isVerified && customerName.trim() && formattedPhone.length >= 10;
    const hasDeliveryLocation =
        selectedAddressId != null ||
        (deliveryLocationNote.trim() && deliveryLatitude != null && deliveryLongitude != null);
    const canSubmitDelivery =
        serviceType === 'delivery' &&
        isVerified &&
        customerName.trim() &&
        formattedPhone.length >= 10 &&
        hasDeliveryLocation &&
        deliveryRestrictedItems.length === 0 &&
        (!saveAddress || addressLabel.trim());

    const deliveryFeeAmount = serviceType === 'delivery' ? Number(deliveryFee) || 0 : 0;
    const orderTotal = total + deliveryFeeAmount;

    const submit = async () => {
        if (!cart.items.length || submitting) {
            return;
        }

        setSubmitting(true);

        const payload = {
            service_type: serviceType,
            items: cart.items.map((i) => ({
                product_id: i.product_id,
                product_variant_id: i.product_variant_id,
                quantity: i.quantity,
                addons: (i.addons || []).map((a) => ({
                    product_addon_id: a.product_addon_id,
                    quantity: a.quantity,
                })),
            })),
            customer_note: note || null,
        };

        if (serviceType === 'table') {
            payload.table_token = activeTableToken;

            const session = readTableSession();

            if (session?.qrToken === activeTableToken && session.session) {
                payload.table_session_token = session.session;
            }

            const tableContext = readTableContext();

            if (tableContext?.scanSession) {
                payload.table_scan_session = tableContext.scanSession;
            }
        } else {
            payload.customer_name = customerName;
            payload.customer_phone_country = customerPhoneCountry;
            payload.customer_phone_local = customerPhoneLocal;
            payload.customer_phone = formattedPhone;

            if (serviceType === 'delivery') {
                if (selectedAddressId) {
                    payload.saved_address_id = selectedAddressId;
                } else {
                    payload.delivery_latitude = deliveryLatitude;
                    payload.delivery_longitude = deliveryLongitude;
                    payload.delivery_location_note = deliveryLocationNote;

                    if (saveAddress && addressLabel.trim()) {
                        payload.save_address = true;
                        payload.address_label = addressLabel.trim();
                    }
                }
            }
        }

        router.post('/orders', payload, {
            preserveScroll: true,
            onFinish: () => setSubmitting(false),
            onSuccess: () => {
                clearCart();
                setCart({ items: [] });

                if (serviceType !== 'table') {
                    clearTableToken();
                    clearTableSession();
                    setTableInfo(null);
                    setTableTokenState(null);
                } else {
                    refreshTableContext();
                }
            },
        });
    };

    return (
        <>
            <SocialMeta seo={seo} />

            <h1 className="font-display text-3xl font-bold text-store-primary">طلبك</h1>
            <p className="mt-2 text-sm text-store-muted">
                {tableModeLocked
                    ? 'أنت على طاولة — راجع أطباقك وأرسل الطلب للمطبخ.'
                    : 'أضف الأطباق، ثم اختر نوع البيع المناسب قبل إرسال الطلب للمطعم.'}
            </p>

            {syncNotice ? (
                <div className="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {syncNotice}
                </div>
            ) : null}
            {serviceType === 'delivery' && deliveryRestrictedItems.length > 0 ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    لا يمكن طلب هذه الأصناف مع التوصيل: {deliveryRestrictedItems.map((item) => item.name).join('، ')}
                </div>
            ) : null}

            {errors?.items ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.items}
                </div>
            ) : null}

            {errors?.customer_phone_local || errors?.customer_phone ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.customer_phone_local || errors.customer_phone}
                </div>
            ) : null}

            {errors?.otp ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.otp}
                </div>
            ) : null}

            {errors?.table_token ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.table_token}
                </div>
            ) : null}

            {errors?.table_scan_session ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.table_scan_session}
                </div>
            ) : null}

            {errors?.delivery_latitude ||
            errors?.delivery_longitude ||
            errors?.delivery_location_note ||
            errors?.saved_address_id ||
            errors?.address_label ? (
                <div className="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {errors.delivery_latitude ||
                        errors.delivery_longitude ||
                        errors.delivery_location_note ||
                        errors.saved_address_id ||
                        errors.address_label}
                </div>
            ) : null}

            <div className="mt-10 grid gap-10 lg:grid-cols-3">
                <div className="space-y-4 lg:col-span-2">
                    {syncing ? (
                        <p className="rounded-2xl border border-store-primary/10 bg-store-surface p-8 text-center text-store-muted">
                            جاري تحديث أسعار القائمة…
                        </p>
                    ) : cart.items.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-store-primary/20 bg-store-surface p-8 text-center shadow-sm">
                            <p className="text-store-muted">طلبك فارغ — اختر من قائمتنا الشهية.</p>
                            <div className="mt-6 flex flex-wrap justify-center gap-3">
                                <Link href="/products" className={btnPrimary}>
                                    تصفح القائمة
                                </Link>
                                <Link
                                    href="/categories"
                                    className="rounded-md border border-store-primary/20 px-5 py-3 text-sm font-bold text-store-primary"
                                >
                                    الأقسام
                                </Link>
                            </div>
                        </div>
                    ) : (
                        cart.items.map((item) => (
                            <div
                                key={item.line_key}
                                className="flex flex-col gap-4 rounded-2xl border border-store-primary/10 bg-store-surface p-4 shadow-sm sm:flex-row"
                            >
                                <div className="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-store-primary/10 bg-store-primary/[0.05]">
                                    {item.image_url ? (
                                        <img src={item.image_url} alt={item.name} className="h-full w-full object-cover" />
                                    ) : null}
                                </div>
                                <div className="flex-1">
                                    <Link href={`/products/${item.slug}`} className="font-semibold text-store-primary hover:text-store-secondary">
                                        {item.name}
                                    </Link>
                                    {serviceType === 'delivery' && item.is_delivery_available === false ? (
                                        <p className="mt-1 text-xs font-medium text-red-600">
                                            هذا الصنف غير متاح ضمن طلبات التوصيل.
                                        </p>
                                    ) : null}
                                    <p className="mt-1 text-sm font-semibold text-store-secondary">
                                        {formatPrice(item.price, symbol)}
                                    </p>
                                    <div className="mt-3 flex items-center gap-3">
                                        <label htmlFor={`qty-${item.line_key}`} className="text-xs text-store-primary/50">
                                            الكمية
                                        </label>
                                        <input
                                            id={`qty-${item.line_key}`}
                                            type="number"
                                            min={1}
                                            max={999}
                                            value={item.quantity}
                                            onChange={(e) => {
                                                setQuantity(item.line_key, Number(e.target.value) || 1);
                                                refresh();
                                            }}
                                            className="w-20 rounded-lg border border-store-primary/15 bg-store-surface px-2 py-1 text-sm text-store-primary"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => {
                                                removeItem(item.line_key);
                                                refresh();
                                            }}
                                            className="text-xs font-medium text-red-600 hover:text-red-700"
                                        >
                                            حذف
                                        </button>
                                    </div>
                                </div>
                                <div className="text-start sm:text-end">
                                    <p className="text-sm text-store-primary/50">المجموع</p>
                                    <p className="text-lg font-bold text-store-primary">
                                        {formatPrice(item.price * item.quantity, symbol)}
                                    </p>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                <div className="rounded-2xl border border-store-primary/10 bg-store-surface p-6 shadow-sm">
                    <p className="text-sm text-store-primary/50">مجموع الأطباق</p>
                    <p className="mt-1 text-2xl font-bold text-store-primary">{formatPrice(total, symbol)}</p>
                    {deliveryFeeAmount > 0 ? (
                        <p className="mt-2 text-sm text-store-muted">
                            + رسوم توصيل {formatPrice(deliveryFeeAmount, symbol)}
                        </p>
                    ) : null}
                    <p className="mt-2 text-sm text-store-primary/50">الإجمالي</p>
                    <p className="mt-1 text-3xl font-bold text-store-secondary">{formatPrice(orderTotal, symbol)}</p>

                    <p className="mt-6 text-sm font-medium text-store-primary">أنواع البيع *</p>
                    {tableModeLocked || tableModeActive ? (
                        <div className="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            <p className="font-bold">{tableInfo?.label || tableContext?.label || 'طاولة'}</p>
                            <p className="mt-1 text-xs">
                                {tableModeLocked
                                    ? 'طلبك مربوط بهذه الطاولة — يمكنك إضافة أطباق جديدة.'
                                    : 'تم تفعيل الطاولة عبر QR — راجع طلبك وأرسله للمطبخ.'}
                            </p>
                        </div>
                    ) : (
                    <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            onClick={() => setServiceType('takeaway')}
                            className={`inline-flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm ${
                                serviceType === 'takeaway'
                                    ? 'bg-store-secondary text-store-primary shadow-sm'
                                    : 'border border-store-primary/15 bg-white text-store-primary'
                            }`}
                        >
                            <Icon name="shopping-bag" className="h-4 w-4 shrink-0" />
                            بيع سفر بدون طاولة
                        </button>
                        <button
                            type="button"
                            onClick={() => setServiceType('delivery')}
                            className={`inline-flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs font-bold transition sm:text-sm ${
                                serviceType === 'delivery'
                                    ? 'bg-store-secondary text-store-primary shadow-sm'
                                    : 'border border-store-primary/15 bg-white text-store-primary'
                            }`}
                        >
                            <Icon name="truck" className="h-4 w-4 shrink-0" />
                            اونلاين مع توصيل
                        </button>
                    </div>
                    )}

                    {serviceType === 'table' ? (
                        <div className="mt-5 space-y-3">
                            {tableInfo || tableContext?.label ? (
                                <div
                                    className={`rounded-xl border px-4 py-3 text-sm ${
                                        tableInfo?.is_owner
                                            ? 'border-amber-200 bg-amber-50 text-amber-900'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                    }`}
                                >
                                    <p className="flex items-center gap-1.5 font-bold">
                                        <Icon name="check-circle" className="h-4 w-4 shrink-0" />
                                        {tableInfo?.label || tableContext?.label}
                                    </p>
                                    <p className="mt-1 text-xs">
                                        {tableInfo?.is_owner
                                            ? 'طلبك على هذه الطاولة — يمكنك إضافة أطباق جديدة حتى يغلق الكاشير الحساب.'
                                            : 'تم تفعيل الطاولة — أكمل طلبك واضغط تأكيد الإرسال.'}
                                    </p>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-store-primary/10 bg-store-cream px-4 py-4 text-sm text-store-primary">
                                    <p className="font-bold">الطلب من الطاولة عبر QR</p>
                                    <ol className="mt-2 list-decimal space-y-1.5 ps-5 text-xs leading-relaxed text-store-muted">
                                        <li>افتح كاميرا جوالك وصوّر رمز QR الموجود على الطاولة.</li>
                                        <li>اضغط الرابط الذي يظهر — يفتح قائمة المطعم تلقائياً.</li>
                                        <li>أضف أطباقك ثم ارجع هنا لتأكيد الطلب.</li>
                                    </ol>
                                </div>
                            )}
                            {tableError ? (
                                <p className="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{tableError}</p>
                            ) : null}
                        </div>
                    ) : (
                        <div className="mt-5 space-y-4">
                            <div>
                                <label htmlFor="customer_name" className="block text-sm font-medium text-store-primary">
                                    الاسم الكامل *
                                </label>
                                <input
                                    id="customer_name"
                                    value={customerName}
                                    onChange={(e) => setCustomerName(e.target.value)}
                                    className={`${inputClass} mt-2`}
                                />
                            </div>

                            <PhoneField
                                countryKey={customerPhoneCountry}
                                localNumber={customerPhoneLocal}
                                onCountryChange={setCustomerPhoneCountry}
                                onLocalChange={setCustomerPhoneLocal}
                                countries={phoneCountries?.countries || []}
                                error={errors?.customer_phone_local || errors?.customer_phone}
                            />

                            {!isVerified ? (
                                <CustomerOtpVerification
                                    name={customerName}
                                    phoneCountry={customerPhoneCountry}
                                    phoneLocal={customerPhoneLocal}
                                    formattedPhone={formattedPhone}
                                    serviceType={serviceType}
                                    onVerified={() => setIsVerified(true)}
                                />
                            ) : null}

                            {serviceType === 'delivery' ? (
                                <>
                                    {savedAddresses.length > 0 ? (
                                        <div>
                                            <label className="block text-sm font-medium text-store-primary">
                                                موقع التوصيل المحفوظ
                                            </label>
                                            <div className="mt-2 grid gap-2">
                                                {savedAddresses.map((address) => (
                                                    <button
                                                        key={address.id}
                                                        type="button"
                                                        onClick={() => selectSavedAddress(address)}
                                                        className={`rounded-xl border px-4 py-3 text-start text-sm transition ${
                                                            selectedAddressId === address.id
                                                                ? 'border-store-secondary bg-store-secondary/15'
                                                                : 'border-store-primary/15 bg-white hover:border-store-secondary/40'
                                                        }`}
                                                    >
                                                        <span className="font-bold text-store-primary">{address.label}</span>
                                                        <span className="mt-1 block text-xs text-store-muted">
                                                            {address.location_note}
                                                        </span>
                                                    </button>
                                                ))}
                                                <button
                                                    type="button"
                                                    onClick={() => selectSavedAddress(null)}
                                                    className={`rounded-xl border px-4 py-3 text-sm font-semibold transition ${
                                                        selectedAddressId == null
                                                            ? 'border-store-secondary bg-store-secondary/15 text-store-primary'
                                                            : 'border-dashed border-store-primary/20 text-store-muted'
                                                    }`}
                                                >
                                                    + موقع جديد
                                                </button>
                                            </div>
                                        </div>
                                    ) : null}

                                    {selectedAddressId == null ? (
                                        <>
                                            <div>
                                                <label className="block text-sm font-medium text-store-primary">
                                                    موقع التوصيل *
                                                </label>
                                                <p className="mt-1 text-xs text-store-muted">اضغط على الخريطة لتحديد موقعك</p>
                                                <div className="mt-2">
                                                    <DeliveryMapPicker
                                                        latitude={deliveryLatitude}
                                                        longitude={deliveryLongitude}
                                                        onChange={({ latitude, longitude }) => {
                                                            setDeliveryLatitude(latitude);
                                                            setDeliveryLongitude(longitude);
                                                        }}
                                                    />
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    htmlFor="delivery_location_note"
                                                    className="block text-sm font-medium text-store-primary"
                                                >
                                                    وصف الموقع *
                                                </label>
                                                <textarea
                                                    id="delivery_location_note"
                                                    value={deliveryLocationNote}
                                                    onChange={(e) => setDeliveryLocationNote(e.target.value)}
                                                    rows={2}
                                                    placeholder="مثال: بناء أبيض، الطابق الثاني، باب أزرق…"
                                                    className={`${inputClass} mt-2`}
                                                />
                                            </div>

                                            <div className="rounded-xl border border-store-primary/10 bg-store-cream/40 px-4 py-3">
                                                <label className="flex cursor-pointer items-start gap-3">
                                                    <input
                                                        type="checkbox"
                                                        checked={saveAddress}
                                                        onChange={(e) => setSaveAddress(e.target.checked)}
                                                        className="mt-1"
                                                    />
                                                    <span>
                                                        <span className="block text-sm font-semibold text-store-primary">
                                                            احفظ هذا الموقع للمرة القادمة
                                                        </span>
                                                        <span className="mt-1 block text-xs text-store-muted">
                                                            سمِّ الموقع مثل «البيت» أو «العمل» لتختاره بسرعة لاحقاً.
                                                        </span>
                                                    </span>
                                                </label>
                                                {saveAddress ? (
                                                    <input
                                                        value={addressLabel}
                                                        onChange={(e) => setAddressLabel(e.target.value)}
                                                        placeholder="اسم الموقع (مثال: البيت)"
                                                        className={`${inputClass} mt-3`}
                                                    />
                                                ) : null}
                                            </div>
                                        </>
                                    ) : (
                                        <p className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                            تم اختيار موقع محفوظ — يمكنك تغييره من القائمة أعلاه.
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="rounded-xl border border-store-primary/10 bg-store-primary/[0.03] px-4 py-3 text-sm text-store-muted">
                                    هذا الطلب بيع سفر بدون طاولة — سنتواصل معك عند جاهزية الطلب.
                                </p>
                            )}
                        </div>
                    )}

                    <label htmlFor="customer_note" className="mt-4 block text-sm font-medium text-store-primary">
                        ملاحظات الطلب (اختياري)
                    </label>
                    <textarea
                        id="customer_note"
                        value={note}
                        onChange={(e) => setNote(e.target.value)}
                        rows={2}
                        placeholder="مثال: بدون بصل، صوص إضافي…"
                        className={`${inputClass} mt-2`}
                    />

                    <button
                        type="button"
                        disabled={
                            !cart.items.length ||
                            submitting ||
                            syncing ||
                            (serviceType === 'table'
                                ? !canSubmitTable
                                : serviceType === 'delivery'
                                  ? !canSubmitDelivery
                                  : !canSubmitPickup)
                        }
                        onClick={submit}
                        className={`${btnPrimary} mt-6`}
                    >
                        {submitting
                            ? 'جاري إرسال الطلب…'
                            : tableInfo?.is_owner
                              ? 'إضافة للطاولة'
                              : 'تأكيد وإرسال الطلب'}
                    </button>
                </div>
            </div>
        </>
    );
}
