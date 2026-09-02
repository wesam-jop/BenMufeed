import { Head } from '@inertiajs/react';
import ProductCard from '@/components/domain/ProductCard';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

type Product = { id: number; name: string; sale_price: number; description?: string };

type Props = { categories?: Array<{ id: number; name: string; products: Product[] }>; products?: Product[] };

export default function CustomerMenu({ categories = [], products = [] }: Props) {
    const allProducts = products.length ? products : categories.flatMap((c) => c.products);
    const [cartCount, setCartCount] = useState(0);
    return (
        <div dir="rtl" className="mx-auto min-h-screen max-w-5xl p-4">
            <Head title="القائمة" />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold">القائمة</h1>
                <Button asChild><Link href="/customer/cart">السلة ({cartCount})</Link></Button>
            </div>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                {(allProducts.length ? allProducts : [{ id: 1, name: 'لاتيه', sale_price: 16 }, { id: 2, name: 'كابتشino', sale_price: 15 }]).map((p) => (
                    <ProductCard key={p.id} id={p.id} name={p.name} price={p.sale_price} onAdd={() => setCartCount((c) => c + 1)} />
                ))}
            </div>
        </div>
    );
}