import { Head, Link } from '@inertiajs/react';
import CartPanel, { type CartItem } from '@/components/domain/CartPanel';
import { useState } from 'react';

export default function CustomerCart() {
    const [items, setItems] = useState<CartItem[]>([
        { id: 1, name: 'لاتيه', price: 16, quantity: 1 },
    ]);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-lg p-4">
            <Head title="السلة" />
            <CartPanel items={items} onCheckout={() => alert('تأكيد الطلب')} checkoutLabel="تأكيد الطلب" />
            <Link href="/customer/menu" className="mt-4 block text-center text-sm text-primary">متابعة التسوق</Link>
        </div>
    );
}