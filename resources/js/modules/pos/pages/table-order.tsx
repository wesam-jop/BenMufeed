import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CartPanel, { type CartItem } from '@/components/domain/CartPanel';
import ProductCard from '@/components/domain/ProductCard';

type Props = { table: { id: number; name: string }; products?: Array<{ id: number; name: string; sale_price: number }> };

export default function TableOrder({ table, products = [] }: Props) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const items = products.length ? products : [
        { id: 1, name: 'قهوة', sale_price: 12 },
        { id: 2, name: 'شاي', sale_price: 8 },
    ];
    return (
        <div dir="rtl" className="grid h-screen grid-cols-1 lg:grid-cols-3 gap-4 p-4">
            <Head title={`طاولة ${table.name}`} />
            <div className="lg:col-span-2">
                <h1 className="mb-4 text-xl font-bold">طاولة {table.name}</h1>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {items.map((p) => (
                        <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={(id) => {
                            const product = items.find((x) => x.id === id)!;
                            setCart((prev) => {
                                const ex = prev.find((i) => i.id === id);
                                if (ex) return prev.map((i) => i.id === id ? { ...i, quantity: i.quantity + 1 } : i);
                                return [...prev, { id, name: product.name, price: product.sale_price, quantity: 1 }];
                            });
                        }} />
                    ))}
                </div>
            </div>
            <CartPanel items={cart} checkoutLabel="إرسال للمطبخ" onCheckout={() => alert('تم إرسال الطلب')} />
        </div>
    );
}