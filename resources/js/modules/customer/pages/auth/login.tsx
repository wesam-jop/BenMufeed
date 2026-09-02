import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    status?: string;
    canResetPassword?: boolean;
};

export default function CustomerLogin({
    business,
    navCategories = [],
    status,
    canResetPassword = true,
}: Props) {
    const { t } = useI18n();
    const pageBusiness = (usePage().props.business as StorefrontBusiness | undefined) ?? business;
    const { data, setData, post, processing, errors } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/auth/login');
    };

    return (
        <StorefrontLayout business={pageBusiness ?? business} navCategories={navCategories}>
            <Head title={t('auth.login.title', 'Sign in')} />

            <div className="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <Card className="w-full max-w-md border-amber-100/80 shadow-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl text-amber-950">
                            {t('auth.login.title', 'Sign in')}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                'auth.login.subtitle',
                                'Customers: WhatsApp number. Staff: username or email.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {status && (
                            <div className="mb-4 text-center text-sm font-medium text-green-600">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="login">
                                    {t(
                                        'auth.login.login_label',
                                        'WhatsApp / username / email',
                                    )}
                                </Label>
                                <Input
                                    id="login"
                                    value={data.login}
                                    onChange={(e) => setData('login', e.target.value)}
                                    autoFocus
                                    autoComplete="username"
                                    placeholder={t(
                                        'auth.login.login_placeholder',
                                        '+9639xxxxxxxx or username',
                                    )}
                                    required
                                    dir="ltr"
                                    className="text-left"
                                />
                                <InputError message={errors.login} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">
                                        {t('auth.password', 'Password')}
                                    </Label>
                                    {canResetPassword && (
                                        <Link
                                            href="/customer/password/forgot"
                                            className="ms-auto text-sm text-amber-800 hover:underline"
                                        >
                                            {t('auth.forgot_password', 'Forgot password?')}
                                        </Link>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="current-password"
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>

                            <label className="flex items-center gap-2">
                                <Checkbox
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData('remember', Boolean(checked))
                                    }
                                />
                                <span className="text-sm">
                                    {t('auth.remember', 'Remember me')}
                                </span>
                            </label>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-amber-800 hover:bg-amber-900"
                            >
                                {processing && <Spinner />}
                                {t('auth.login.submit', 'Sign in')}
                            </Button>

                            <p className="text-center text-sm text-muted-foreground">
                                {t('auth.login.no_account', 'New customer?')}{' '}
                                <Link
                                    href="/customer/register"
                                    className="text-amber-800 hover:underline"
                                >
                                    {t('auth.register.link', 'Create account')}
                                </Link>
                            </p>

                            <p className="text-center text-xs text-muted-foreground">
                                {t(
                                    'auth.login.hint',
                                    'Admin → dashboard | Cashier → POS | Customer → my account',
                                )}
                            </p>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </StorefrontLayout>
    );
}
