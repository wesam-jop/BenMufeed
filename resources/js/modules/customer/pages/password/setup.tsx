import { Head, useForm, usePage } from '@inertiajs/react';
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
    customer: {
        name?: string | null;
        phone: string;
    };
    business?: StorefrontBusiness;
    navCategories?: NavCategory[];
};

export default function CustomerPasswordSetup({ customer, business, navCategories = [] }: Props) {
    const { t } = useI18n();
    const sharedBusiness = usePage().props.business as StorefrontBusiness | undefined;
    const layoutBusiness = business ?? sharedBusiness ?? { name: 'Kasher' };
    const form = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/customer/password/setup');
    };

    return (
        <StorefrontLayout business={layoutBusiness} navCategories={navCategories}>
            <Head title={t('auth.setup.title', 'Activate password')} />

            <div className="flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <Card className="w-full max-w-md border-amber-100/80 shadow-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-2xl text-amber-950">
                            {t('auth.setup.title', 'Activate password')}
                        </CardTitle>
                        <CardDescription>
                            {t('auth.setup.subtitle', 'Welcome :name! Set a password for :phone')
                                .replace(':name', customer.name ?? t('auth.setup.guest', 'there'))
                                .replace(':phone', customer.phone)}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="password">{t('auth.password', 'Password')}</Label>
                                <PasswordInput
                                    id="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    required
                                    autoFocus
                                />
                                <InputError message={form.errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t('auth.register.password_confirm', 'Confirm password')}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    value={form.data.password_confirmation}
                                    onChange={(e) =>
                                        form.setData('password_confirmation', e.target.value)
                                    }
                                    required
                                />
                            </div>

                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="w-full bg-amber-800 hover:bg-amber-900"
                            >
                                {form.processing && <Spinner />}
                                {t('auth.setup.submit', 'Activate and continue')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </StorefrontLayout>
    );
}
