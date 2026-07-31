import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="font-display text-h4 text-store-primary">
                    حذف الحساب
                </h2>

                <p className="mt-1 text-body text-store-muted">
                    عند حذف حسابك، ستُزال جميع بياناتك نهائياً. تأكد من حفظ أي معلومات تريد الاحتفاظ بها قبل المتابعة.
                </p>
            </header>

            <DangerButton onClick={confirmUserDeletion}>
                حذف الحساب
            </DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="font-display text-h4 text-store-primary">
                        هل أنت متأكد من حذف حسابك؟
                    </h2>

                    <p className="mt-2 text-body text-store-muted">
                        لا يمكن التراجع عن هذا الإجراء. أدخل كلمة المرور لتأكيد الحذف النهائي.
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value="كلمة المرور"
                            className="sr-only"
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1.5 block w-full sm:w-3/4"
                            isFocused
                            placeholder="كلمة المرور"
                            hasError={!!errors.password}
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>
                            إلغاء
                        </SecondaryButton>

                        <DangerButton disabled={processing}>
                            حذف الحساب
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
