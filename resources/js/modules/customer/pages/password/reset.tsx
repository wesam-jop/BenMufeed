import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/hooks/use-i18n';
import StorefrontLayout, {
    type NavCategory,
    type StorefrontBusiness,
} from '@/layouts/storefront-layout';

type Props = {
    business: StorefrontBusiness;
    navCategories?: NavCategory[];
    token: string;
    whatsapp?: string | null;
};

export default function CustomerResetPassword({
    business,
    navCategories = [],
    token,
    whatsapp,
}: Props) {
    const { t } = useI18n();
    const pageBusiness = (usePage().props.business as StorefrontBusiness | undefined) ?? business;
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/customer/password/reset/${token}`);
    };

    return (
        <StorefrontLayout business={pageBusiness ?? business} navCategories={navCategories}>
            <Head title={t('auth.reset.title', 'Reset password')} />

            <div className="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <Card className="w-full max-w-md border-amber-100/80 shadow-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl text-amber-950">
                            {t('auth.reset.title', 'Reset password')}
                        </CardTitle>
                        <CardDescription>
                            {whatsapp
                                ? t('auth.reset.subtitle_with_phone', 'Set a new password for :phone').replace(
                                      ':phone',
                                      whatsapp,
                                  )
                                : t('auth.reset.subtitle', 'Choose a new password for your account')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="password">{t('auth.password', 'Password')}</Label>
                                <PasswordInput
                                    id="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="new-password"
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t('auth.register.password_confirm', 'Confirm password')}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData('password_confirmation', e.target.value)
                                    }
                                    autoComplete="new-password"
                                    required
                                />
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-amber-800 hover:bg-amber-900"
                            >
                                {processing && <Spinner />}
                                {t('auth.reset.submit', 'Update password')}
                            </Button>

                            <p className="text-center text-sm text-muted-foreground">
                                <Link href="/auth/login" className="text-amber-800 hover:underline">
                                    {t('auth.login.link', 'Sign in')}
                                </Link>
                            </p>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </StorefrontLayout>
    );
}
