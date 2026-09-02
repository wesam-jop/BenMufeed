import { Badge } from '@/components/ui/badge';
import { orderStatusLabel, orderStatusVariant } from '@/lib/format';

type OrderStatusBadgeProps = {
    status: string;
    className?: string;
};

export default function OrderStatusBadge({ status, className }: OrderStatusBadgeProps) {
    return (
        <Badge variant={orderStatusVariant(status)} className={className}>
            {orderStatusLabel(status)}
        </Badge>
    );
}
