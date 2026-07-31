import { Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import CustomerOtpVerification from '@/Components/Store/CustomerOtpVerification';
import PhoneField from '@/Components/Store/PhoneField';
import { withStoreLayout } from '@/Layouts/StoreLayout';
import { formatPhone, splitCountryKey } from '@/lib/phoneCountries';

export default withStoreLayout(AccountLoginPage);

function AccountLoginPage() {
    const { phoneCountries } = usePage().props;
    const defaultCountryKey = phoneCountries?.defaultKey || '963|SY';

    const [customerName, setCustomerName] = useState('');
    const [customerPhoneCountry, setCustomerPhoneCountry] = useState(defaultCountryKey);
    const [customerPhoneLocal, setCustomerPhoneLocal] = useState('');
    const [verified, setVerified] = useState(false);

    const formattedPhone = useMemo(() => {
        const { dial } = splitCountryKey(customerPhoneCountry);

        return formatPhone(dial, customerPhoneLocal);
    }, [customerPhoneCountry, customerPhoneLocal]);

    return (
        <>
            <h1 className="font-display text-3xl font-bold text-store-primary">تسجيل الدخول</h1>
            <p className="mt-2 text-sm text-store-muted">
                أدخل اسمك ورقم واتساب — سنرسل لك رمز تحقق. نفس الخطوة تنشئ حساباً جديداً أو تدخّلك لحسابك
                السابق.
            </p>

            <div className="store-card mx-auto mt-8 max-w-lg p-6">
                <div className="space-y-4">
                    <div>
                        <label htmlFor="login_name" className="block text-sm font-medium text-store-primary">
                            الاسم الكامل *
                        </label>
                        <input
                            id="login_name"
                            value={customerName}
                            onChange={(e) => setCustomerName(e.target.value)}
                            className="store-input mt-2"
                            placeholder="مثال: محمد أحمد"
                        />
                    </div>

                    <PhoneField
                        countryKey={customerPhoneCountry}
                        localNumber={customerPhoneLocal}
                        onCountryChange={setCustomerPhoneCountry}
                        onLocalChange={setCustomerPhoneLocal}
                        countries={phoneCountries?.countries || []}
                    />

                    {!verified ? (
                        <CustomerOtpVerification
                            name={customerName}
                            phoneCountry={customerPhoneCountry}
                            phoneLocal={customerPhoneLocal}
                            formattedPhone={formattedPhone}
                            serviceType="takeaway"
                            purpose="account"
                            successRedirect="/account"
                            introTitle="تحقق من واتساب"
                            introText="اضغط إرسال الرمز — يصلك على واتساب خلال ثوانٍ."
                            onVerified={() => setVerified(true)}
                        />
                    ) : (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                            <p className="font-bold">تم تسجيل الدخول بنجاح</p>
                            <button
                                type="button"
                                onClick={() => router.visit('/account')}
                                className="store-btn-primary mt-4"
                            >
                                الذهاب إلى حسابي
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <p className="mt-6 text-center text-sm text-store-muted">
                <Link href="/products" className="font-semibold text-store-primary hover:text-store-secondary">
                    ← العودة للقائمة
                </Link>
            </p>
        </>
    );
}
