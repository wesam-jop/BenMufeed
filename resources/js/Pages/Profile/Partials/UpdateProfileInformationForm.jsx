import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="font-display text-h4 text-store-primary">
                    معلومات الملف الشخصي
                </h2>

                <p className="mt-1 text-body text-store-muted">
                    حدّث اسمك وعنوان بريدك الإلكتروني.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Name" />

                    <TextInput
                        id="name"
                        className="mt-1.5 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                        hasError={!!errors.name}
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1.5 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                        hasError={!!errors.email}
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-body text-store-primary">
                            بريدك الإلكتروني غير موثّق.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="ms-1 text-body font-medium text-store-secondary underline-offset-2 transition duration-fast hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-store-secondary/40"
                            >
                                أعد إرسال رابط التحقق.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="store-alert-success mt-2">
                                تم إرسال رابط تحقق جديد إلى بريدك.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>حفظ</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-fast"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out duration-fast"
                        leaveTo="opacity-0"
                    >
                        <p className="text-body text-semantic-success">
                            تم الحفظ.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
