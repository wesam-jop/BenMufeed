import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="تأكيد كلمة المرور" />

            <h1 className="font-display mb-4 text-center text-h4 text-store-primary">تأكيد كلمة المرور</h1>

            <p className="mb-4 text-body text-store-muted">
                هذه منطقة آمنة. يرجى تأكيد كلمة المرور قبل المتابعة.
            </p>

            <form onSubmit={submit}>
                <div className="mt-4">
                    <InputLabel htmlFor="password" value="كلمة المرور" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1.5 block w-full"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        hasError={!!errors.password}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <PrimaryButton disabled={processing}>
                        تأكيد
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
