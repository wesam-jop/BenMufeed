import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import WhatsAppPhoneInput from '@/components/whatsapp-phone-input';
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
};

export default function CustomerForgotPassword({ business, navCategories = [] }: Props) {
    const { t } = useI18n();
    const page = usePage<{ status?: string; flash?: { error?: string } }>();
    const pageBusiness = (page.props.business as StorefrontBusiness | undefined) ?? business;
    const status = page.props.status as string | undefined;
    const { data, setData, post, processing, errors } = useForm({ whatsapp: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/customer/password/forgot');
    };

    return (
        <StorefrontLayout business={pageBusiness ?? business} navCategories={navCategories}>
            <Head title={t('auth.forgot.title', 'Forgot password')} />

            <div className="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <Card className="w-full max-w-md border-amber-100/80 shadow-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl text-amber-950">
                            {t('auth.forgot.title', 'Forgot password')}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                'auth.forgot.subtitle',
                                'Enter your WhatsApp number to reset your customer password',
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
                                <Label htmlFor="whatsapp">
                                    {t('auth.register.whatsapp', 'WhatsApp number')}
                                </Label>
                                <WhatsAppPhoneInput
                                    id="whatsapp"
                                    value={data.whatsapp}
                                    onChange={(value) => setData('whatsapp', value)}
                                    defaultCountry="SY"
                                    autoFocus
                                />
                                <InputError message={errors.whatsapp} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing || !data.whatsapp}
                                className="w-full bg-amber-800 hover:bg-amber-900"
                            >
                                {processing && <Spinner />}
                                {t('auth.forgot.submit', 'Continue')}
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
