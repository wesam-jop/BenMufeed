import { useState } from 'react';
import ProductCard from '@/components/domain/ProductCard';
import { useCustomerFavorites } from '@/hooks/use-customer-favorites';
import { useStorefrontCart } from '@/hooks/use-storefront-cart';

type StorefrontProductCardProps = {
    id: number;
    name: string;
    price: number;
    image?: string | null;
    imageUrl?: string | null;
    category?: string;
    description?: string | null;
    currency?: string;
};

export default function StorefrontProductCard({
    currency = 'SAR',
    image,
    imageUrl,
    ...props
}: StorefrontProductCardProps) {
    const { addItem } = useStorefrontCart();
    const { isFavorite, toggleFavorite } = useCustomerFavorites();
    const [adding, setAdding] = useState(false);

    const handleAdd = (id: number) => {
        setAdding(true);
        addItem(id);
        window.setTimeout(() => setAdding(false), 400);
    };

    return (
        <ProductCard
            {...props}
            image={image}
            imageUrl={imageUrl}
            currency={currency}
            showFavorite
            isFavorite={isFavorite(props.id)}
            onToggleFavorite={toggleFavorite}
            onAdd={handleAdd}
            adding={adding}
            addLabel="أضف للسلة"
        />
    );
}
