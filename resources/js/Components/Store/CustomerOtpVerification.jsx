import { useState } from 'react';
import { router } from '@inertiajs/react';
import { sendStorefrontOtp, verifyStorefrontOtp } from '@/lib/storefrontAccount';

const btnPrimary = 'store-btn-primary w-full disabled:cursor-not-allowed disabled:opacity-40';

export default function CustomerOtpVerification({
    name,
    phoneCountry,
    phoneLocal,
    formattedPhone,
    serviceType,
    onVerified,
    successRedirect = null,
    introTitle = 'تحقق من رقم الواتساب',
    introText = 'أدخل اسمك الحقيقي ورقمك، ثم سنرسل لك رمزاً على واتساب لتأكيد طلبك.',
}) {
    const [otp, setOtp] = useState('');
    const [sent, setSent] = useState(false);
    const [sending, setSending] = useState(false);
    const [verifying, setVerifying] = useState(false);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);

    const canSend = name.trim().length >= 2 && formattedPhone.length >= 10;
    const canVerify = sent && otp.trim().length === 6 && canSend;

    const send = async () => {
        if (!canSend || sending) {
            return;
        }

        setSending(true);
        setError(null);
        setMessage(null);

        try {
            const result = await sendStorefrontOtp({
                name: name.trim(),
                phoneCountry,
                phoneLocal,
                phone: formattedPhone,
            });
            setSent(true);
            setMessage(result.message || 'تم إرسال الرمز إلى واتساب.');
        } catch (err) {
            setError(err.message);
        } finally {
            setSending(false);
        }
    };

    const verify = async () => {
        if (!canVerify || verifying) {
            return;
        }

        setVerifying(true);
        setError(null);

        try {
            const result = await verifyStorefrontOtp({
                name: name.trim(),
                phoneCountry,
                phoneLocal,
                phone: formattedPhone,
                otp: otp.trim(),
                serviceType,
            });
            setMessage(result.message || 'تم التحقق بنجاح.');
            onVerified?.(result.customer);

            if (successRedirect) {
                router.visit(successRedirect);
            } else {
                router.reload({ only: ['storefrontCustomer'] });
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setVerifying(false);
        }
    };

    return (
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950">
            <p className="font-bold">{introTitle}</p>
            <p className="mt-1 text-xs leading-relaxed text-amber-900/80">{introText}</p>

            {message ? (
                <p className="mt-3 rounded-lg bg-white/70 px-3 py-2 text-xs text-emerald-800">{message}</p>
            ) : null}

            {error ? (
                <p className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{error}</p>
            ) : null}

            <div className="mt-4 space-y-3">
                {!sent ? (
                    <button type="button" disabled={!canSend || sending} onClick={send} className={btnPrimary}>
                        {sending ? 'جاري الإرسال…' : 'إرسال رمز التحقق'}
                    </button>
                ) : (
                    <>
                        <div>
                            <label htmlFor="otp_code" className="block text-xs font-semibold">
                                رمز التحقق (6 أرقام)
                            </label>
                            <input
                                id="otp_code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                value={otp}
                                onChange={(e) => setOtp(e.target.value.replace(/\D/g, '').slice(0, 6))}
                                className="store-input mt-2 text-center text-lg tracking-[0.35em]"
                            />
                        </div>
                        <button type="button" disabled={!canVerify || verifying} onClick={verify} className={btnPrimary}>
                            {verifying ? 'جاري التحقق…' : 'تأكيد الرمز'}
                        </button>
                        <button
                            type="button"
                            disabled={sending}
                            onClick={send}
                            className="w-full text-xs font-semibold text-amber-900 underline-offset-2 hover:underline"
                        >
                            إعادة إرسال الرمز
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}
