import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import WhatsAppPhoneInput from '@/components/whatsapp-phone-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
};

export default function CustomerRegister({ business, navCategories = [] }: Props) {
    const { t } = useI18n();
    const pageBusiness = (usePage().props.business as StorefrontBusiness | undefined) ?? business;
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        whatsapp: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/customer/register');
    };

    return (
        <StorefrontLayout business={pageBusiness ?? business} navCategories={navCategories}>
            <Head title={t('auth.register.title', 'Create account')} />

            <div className="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <Card className="w-full max-w-md border-amber-100/80 shadow-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl text-amber-950">
                            {t('auth.register.title', 'Create account')}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                'auth.register.subtitle',
                                'Register with your WhatsApp number to track orders and favorites',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t('auth.register.name', 'Name')}</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    autoComplete="name"
                                    placeholder={t('auth.register.name_placeholder', 'Your name')}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="whatsapp">
                                    {t('auth.register.whatsapp', 'WhatsApp number')}
                                </Label>
                                <WhatsAppPhoneInput
                                    id="whatsapp"
                                    value={data.whatsapp}
                                    onChange={(value) => setData('whatsapp', value)}
                                    defaultCountry="SY"
                                    placeholder={t(
                                        'auth.register.whatsapp_placeholder',
                                        '9xx xxx xxx',
                                    )}
                                    autoFocus
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t(
                                        'auth.register.whatsapp_hint',
                                        'International format. Default country: Syria (+963)',
                                    )}
                                </p>
                                <InputError message={errors.whatsapp} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">{t('auth.password', 'Password')}</Label>
                                <PasswordInput
                                    id="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="new-password"
                                    required
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
                                disabled={processing || !data.whatsapp}
                                className="w-full bg-amber-800 hover:bg-amber-900"
                            >
                                {processing && <Spinner />}
                                {t('auth.register.submit', 'Register')}
                            </Button>

                            <p className="text-center text-sm text-muted-foreground">
                                {t('auth.register.has_account', 'Already have an account?')}{' '}
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
