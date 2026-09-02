import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import ProductCard from '@/components/domain/ProductCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCustomerFavorites } from '@/hooks/use-customer-favorites';

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    image?: string | null;
    image_url?: string | null;
};

type Props = {
    favorites?: Product[];
};

export default function CustomerFavorites({ favorites = [] }: Props) {
    const { isFavorite, toggleFavorite } = useCustomerFavorites();

    return (
        <>
            <Head title="المفضلة" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-amber-950">المفضلة</h1>
                    <p className="text-muted-foreground">منتجاتك المفضلة للطلب السريع</p>
                </div>

                {favorites.length === 0 ? (
                    <Card className="border-amber-100">
                        <CardContent className="py-12 text-center">
                            <Heart className="mx-auto mb-3 size-10 text-amber-200" />
                            <p className="text-muted-foreground">لا توجد منتجات مفضلة</p>
                            <Button asChild className="mt-4 bg-amber-800 hover:bg-amber-900">
                                <Link href="/products">تصفّح المنتجات</Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {favorites.map((product) => (
                            <ProductCard
                                key={product.id}
                                id={product.id}
                                name={product.name}
                                price={Number(product.sale_price)}
                                image={product.image}
                                imageUrl={product.image_url}
                                showFavorite
                                isFavorite={isFavorite(product.id)}
                                onToggleFavorite={toggleFavorite}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
