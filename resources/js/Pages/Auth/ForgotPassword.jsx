import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="نسيت كلمة المرور" />

            <h1 className="font-display mb-4 text-center text-h4 text-store-primary">استعادة كلمة المرور</h1>

            <p className="mb-4 text-body text-store-muted">
                أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.
            </p>

            {status && (
                <div className="store-alert-success mb-4">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <InputLabel htmlFor="email" value="البريد الإلكتروني" />

                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1.5 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                    hasError={!!errors.email}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        إرسال رابط الاستعادة
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
