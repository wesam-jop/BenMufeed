import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SupplierFormData = {
    name: string;
    contact_person: string;
    phone: string;
    email: string;
    address: string;
    tax_number: string;
    is_active: boolean;
};

type Props = {
    data: SupplierFormData;
    errors: Partial<Record<keyof SupplierFormData, string>>;
    setData: <K extends keyof SupplierFormData>(key: K, value: SupplierFormData[K]) => void;
};

function OptionalLabel({ htmlFor, children }: { htmlFor: string; children: React.ReactNode }) {
    return (
        <Label htmlFor={htmlFor} className="font-normal">
            {children}{' '}
            <span className="text-xs text-muted-foreground">(اختياري)</span>
        </Label>
    );
}

export default function SupplierFormFields({ data, errors, setData }: Props) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">اسم المورد *</Label>
                <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="مثال: شركة الألبان"
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="rounded-lg border bg-muted/30 p-4">
                <p className="mb-4 text-sm font-medium text-muted-foreground">
                    بيانات إضافية — كلها اختيارية
                </p>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <OptionalLabel htmlFor="contact_person">جهة الاتصال</OptionalLabel>
                        <Input
                            id="contact_person"
                            type="text"
                            value={data.contact_person}
                            onChange={(e) => setData('contact_person', e.target.value)}
                            placeholder="اسم الشخص المسؤول"
                        />
                        <InputError message={errors.contact_person} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <OptionalLabel htmlFor="phone">الهاتف</OptionalLabel>
                            <Input
                                id="phone"
                                type="text"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                                placeholder="05xxxxxxxx"
                                dir="ltr"
                                className="text-left"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <OptionalLabel htmlFor="email">البريد</OptionalLabel>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="supplier@example.com"
                                dir="ltr"
                                className="text-left"
                            />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <OptionalLabel htmlFor="address">العنوان</OptionalLabel>
                        <Input
                            id="address"
                            type="text"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            placeholder="المدينة، الحي، الشارع"
                        />
                        <InputError message={errors.address} />
                    </div>

                    <div className="grid gap-2">
                        <OptionalLabel htmlFor="tax_number">الرقم الضريبي</OptionalLabel>
                        <Input
                            id="tax_number"
                            type="text"
                            value={data.tax_number}
                            onChange={(e) => setData('tax_number', e.target.value)}
                            placeholder="3xxxxxxxxxx0003"
                            dir="ltr"
                            className="text-left"
                        />
                        <InputError message={errors.tax_number} />
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) => setData('is_active', checked === true)}
                />
                <Label htmlFor="is_active" className="font-normal">
                    مورد نشط (يظهر في أوامر الشراء)
                </Label>
            </div>
            <InputError message={errors.is_active} />
        </>
    );
}
