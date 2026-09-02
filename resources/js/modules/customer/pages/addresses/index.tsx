import { Head, router, useForm } from '@inertiajs/react';
import { MapPin, Pencil, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';
import DeliveryLocationMap from '@/components/domain/DeliveryLocationMap';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCustomerAddress } from '@/lib/format';

type Address = {
    id: number;
    label?: string | null;
    city?: string | null;
    district?: string | null;
    street?: string | null;
    building?: string | null;
    floor?: string | null;
    apartment?: string | null;
    notes?: string | null;
    lat?: number | string | null;
    lng?: number | string | null;
    is_default?: boolean;
    formatted?: string;
};

type Props = {
    addresses: Address[];
};

const emptyForm = {
    label: 'موقع التوصيل',
    city: '',
    district: '',
    street: '',
    building: '',
    floor: '',
    apartment: '',
    notes: '',
    lat: '',
    lng: '',
    is_default: false,
};

export default function CustomerAddresses({ addresses = [] }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm({ ...emptyForm });
    const editForm = useForm({ ...emptyForm, id: 0 });

    const startEdit = (address: Address) => {
        setEditingId(address.id);
        editForm.setData({
            id: address.id,
            label: address.label ?? 'موقع التوصيل',
            city: address.city ?? '',
            district: address.district ?? '',
            street: address.street ?? '',
            building: address.building ?? '',
            floor: address.floor ?? '',
            apartment: address.apartment ?? '',
            notes: address.notes ?? '',
            lat: address.lat != null ? String(address.lat) : '',
            lng: address.lng != null ? String(address.lng) : '',
            is_default: Boolean(address.is_default),
        });
    };

    const handleMapChange = (
        form: typeof createForm,
        lat: number,
        lng: number,
        address?: string,
    ) => {
        form.setData((current) => ({
            ...current,
            lat: lat.toFixed(7),
            lng: lng.toFixed(7),
            street: address ?? current.street,
        }));
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/customer/addresses', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createForm.setData({ ...emptyForm });
            },
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/customer/addresses/${editForm.data.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const removeAddress = (id: number) => {
        if (!confirm('حذف هذا الموقع؟')) return;
        router.delete(`/customer/addresses/${id}`, { preserveScroll: true });
    };

    const setDefault = (id: number) => {
        router.post(`/customer/addresses/${id}/default`, {}, { preserveScroll: true });
    };

    const AddressForm = ({
        form,
        onSubmit,
        submitLabel,
    }: {
        form: typeof createForm;
        onSubmit: (e: React.FormEvent) => void;
        submitLabel: string;
    }) => (
        <form onSubmit={onSubmit} className="grid gap-4">
            <DeliveryLocationMap
                lat={form.data.lat}
                lng={form.data.lng}
                onLocationChange={(lat, lng, address) => handleMapChange(form, lat, lng, address)}
            />

            <div className="grid gap-2 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={`${submitLabel}-label`}>اسم الموقع</Label>
                    <Input
                        id={`${submitLabel}-label`}
                        value={form.data.label}
                        onChange={(e) => form.setData('label', e.target.value)}
                        placeholder="مثال: البيت، العمل"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={`${submitLabel}-district`}>الحي</Label>
                    <Input
                        id={`${submitLabel}-district`}
                        value={form.data.district}
                        onChange={(e) => form.setData('district', e.target.value)}
                    />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${submitLabel}-street`}>العنوان *</Label>
                <Input
                    id={`${submitLabel}-street`}
                    value={form.data.street}
                    onChange={(e) => form.setData('street', e.target.value)}
                    required
                />
                <InputError message={form.errors.street} />
            </div>

            <div className="grid gap-2 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label>المبنى</Label>
                    <Input value={form.data.building} onChange={(e) => form.setData('building', e.target.value)} />
                </div>
                <div className="grid gap-2">
                    <Label>الطابق</Label>
                    <Input value={form.data.floor} onChange={(e) => form.setData('floor', e.target.value)} />
                </div>
                <div className="grid gap-2">
                    <Label>الشقة</Label>
                    <Input value={form.data.apartment} onChange={(e) => form.setData('apartment', e.target.value)} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label>ملاحظات</Label>
                <textarea
                    rows={2}
                    className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={form.data.notes}
                    onChange={(e) => form.setData('notes', e.target.value)}
                />
            </div>

            <Button
                type="submit"
                disabled={form.processing || !form.data.street}
                className="bg-amber-800 hover:bg-amber-900"
            >
                {form.processing && <Spinner />}
                {submitLabel}
            </Button>
        </form>
    );

    return (
        <>
            <Head title="مواقع التوصيل" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-amber-950">مواقع التوصيل</h1>
                    <p className="text-muted-foreground">احفظ مواقعك لتسريع الطلبات القادمة</p>
                </div>

                <div className="grid gap-4">
                    {addresses.length === 0 ? (
                        <Card className="border-amber-100">
                            <CardContent className="py-10 text-center text-muted-foreground">
                                <MapPin className="mx-auto mb-2 size-8 opacity-50" />
                                لا توجد مواقع محفوظة بعد
                            </CardContent>
                        </Card>
                    ) : (
                        addresses.map((address) => (
                            <Card key={address.id} className="border-amber-100">
                                <CardContent className="p-4">
                                    {editingId === address.id ? (
                                        <AddressForm
                                            form={editForm}
                                            onSubmit={submitEdit}
                                            submitLabel="حفظ التعديلات"
                                        />
                                    ) : (
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-semibold">
                                                        {address.label ?? 'موقع التوصيل'}
                                                    </p>
                                                    {address.is_default && (
                                                        <Badge variant="outline" className="border-amber-300 text-amber-800">
                                                            افتراضي
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {address.formatted ?? formatCustomerAddress(address)}
                                                </p>
                                                {address.lat && address.lng && (
                                                    <p className="mt-1 text-xs text-muted-foreground" dir="ltr">
                                                        {Number(address.lat).toFixed(5)}, {Number(address.lng).toFixed(5)}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex shrink-0 gap-1">
                                                {!address.is_default && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="تعيين كافتراضي"
                                                        onClick={() => setDefault(address.id)}
                                                    >
                                                        <Star className="size-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => startEdit(address)}
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive"
                                                    onClick={() => removeAddress(address.id)}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                {editingId === null && (
                    <Card className="border-amber-100">
                        <CardHeader>
                            <CardTitle>إضافة موقع جديد</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <AddressForm form={createForm} onSubmit={submitCreate} submitLabel="حفظ الموقع" />
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
