import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="تأكيد البريد الإلكتروني" />

            <h1 className="font-display mb-4 text-center text-h4 text-store-primary">تأكيد البريد الإلكتروني</h1>

            <p className="mb-4 text-body text-store-muted">
                شكراً لتسجيلك! قبل البدء، يرجى تأكيد بريدك الإلكتروني عبر الرابط الذي أرسلناه. إذا لم يصلك البريد، يمكنك طلب إرسال جديد.
            </p>

            {status === 'verification-link-sent' && (
                <div className="store-alert-success mb-4">
                    تم إرسال رابط تحقق جديد إلى بريدك الإلكتروني.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <PrimaryButton disabled={processing}>
                        إعادة إرسال رابط التحقق
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-body text-store-muted underline-offset-2 transition duration-fast hover:text-store-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-store-secondary/40"
                    >
                        تسجيل الخروج
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
