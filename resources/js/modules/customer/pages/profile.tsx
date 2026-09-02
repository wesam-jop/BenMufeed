import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Customer = {
    name: string;
    email?: string | null;
    phone?: string;
    has_password?: boolean;
};

type Props = {
    customer: Customer;
};

export default function CustomerProfile({ customer }: Props) {
    const profileForm = useForm({
        name: customer.name ?? '',
        email: customer.email ?? '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.patch('/customer/profile');
    };

    const submitPassword = (e: React.FormEvent) => {
        e.preventDefault();
        passwordForm.patch('/customer/profile/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <>
            <Head title="الملف الشخصي" />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-amber-950">الملف الشخصي</h1>
                    <p className="text-muted-foreground">إدارة بيانات حسابك وكلمة المرور</p>
                </div>

                <Card className="border-amber-100">
                    <CardHeader>
                        <CardTitle>البيانات الأساسية</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitProfile} className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">الاسم</Label>
                                <Input
                                    id="name"
                                    value={profileForm.data.name}
                                    onChange={(e) => profileForm.setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={profileForm.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">البريد الإلكتروني</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={profileForm.data.email}
                                    onChange={(e) => profileForm.setData('email', e.target.value)}
                                />
                                <InputError message={profileForm.errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label>رقم الجوال</Label>
                                <Input value={customer.phone ?? ''} disabled dir="ltr" />
                                <p className="text-xs text-muted-foreground">رقم الجوال مرتبط بحسابك ولا يمكن تغييره</p>
                            </div>

                            <Button
                                type="submit"
                                disabled={profileForm.processing}
                                className="w-fit bg-amber-800 hover:bg-amber-900"
                            >
                                {profileForm.processing && <Spinner />}
                                حفظ التغييرات
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card className="border-amber-100">
                    <CardHeader>
                        <CardTitle>{customer.has_password ? 'تغيير كلمة المرور' : 'تفعيل كلمة المرور'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitPassword} className="grid gap-4">
                            {customer.has_password && (
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">كلمة المرور الحالية</Label>
                                    <Input
                                        id="current_password"
                                        type="password"
                                        value={passwordForm.data.current_password}
                                        onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                        required
                                    />
                                    <InputError message={passwordForm.errors.current_password} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="password">كلمة المرور الجديدة</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={passwordForm.data.password}
                                    onChange={(e) => passwordForm.setData('password', e.target.value)}
                                    required
                                />
                                <InputError message={passwordForm.errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">تأكيد كلمة المرور</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordForm.data.password_confirmation}
                                    onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                    required
                                />
                            </div>

                            <Button type="submit" disabled={passwordForm.processing} variant="outline" className="w-fit">
                                {passwordForm.processing && <Spinner />}
                                {customer.has_password ? 'تحديث كلمة المرور' : 'تفعيل كلمة المرور'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
