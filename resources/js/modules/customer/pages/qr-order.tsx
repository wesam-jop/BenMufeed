import { Head, useForm } from '@inertiajs/react';
import ProductCard from '@/components/domain/ProductCard';
import { Button } from '@/components/ui/button';

type Product = { id: number; name: string; sale_price: number };

type Props = { table: { id: number; name: string }; products: Product[]; qr_token: string };

export default function QROrder({ table, products, qr_token }: Props) {
    const { data, setData, post, processing } = useForm<{ items: Array<{ product_id: number; quantity: number }> }>({ items: [] });
    const addItem = (id: number) => setData('items', [...data.items.filter((i) => i.product_id !== id), { product_id: id, quantity: (data.items.find((i) => i.product_id === id)?.quantity ?? 0) + 1 }]);
    const submit = () => post(`/order/${qr_token}`);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-4xl p-4">
            <Head title={`طاولة ${table.name}`} />
            <h1 className="mb-2 text-2xl font-bold">طاولة {table.name}</h1>
            <p className="mb-6 text-muted-foreground">اطلب مباشرة من طاولتك</p>
            <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                {products.map((p) => <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={addItem} />)}
            </div>
            <Button onClick={submit} disabled={processing || data.items.length === 0}>إرسال الطلب ({data.items.length})</Button>
        </div>
    );
}