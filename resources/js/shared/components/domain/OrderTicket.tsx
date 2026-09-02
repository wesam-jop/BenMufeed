import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate, statusLabel } from '@/lib/format';

type OrderTicketItem = {
    id: number;
    name: string;
    quantity: number;
    price?: number;
    status?: string;
    notes?: string;
};

type OrderTicketProps = {
    orderNumber: string | number;
    status: string;
    createdAt: string;
    tableName?: string;
    items: OrderTicketItem[];
    total?: number;
    actions?: React.ReactNode;
};

export default function OrderTicket({
    orderNumber,
    status,
    createdAt,
    tableName,
    items,
    total,
    actions,
}: OrderTicketProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-2 pb-3">
                <div>
                    <CardTitle className="text-lg">طلب #{orderNumber}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {formatDate(createdAt)}
                        {tableName && ` • طاولة ${tableName}`}
                    </p>
                </div>
                <Badge variant="secondary">{statusLabel(status)}</Badge>
            </CardHeader>
            <CardContent className="space-y-3">
                {items.map((item) => (
                    <div
                        key={item.id}
                        className="flex items-start justify-between gap-2 text-sm"
                    >
                        <div>
                            <p className="font-medium">
                                {item.quantity}× {item.name}
                            </p>
                            {item.notes && (
                                <p className="text-muted-foreground">{item.notes}</p>
                            )}
                            {item.status && (
                                <Badge variant="outline" className="mt-1">
                                    {statusLabel(item.status)}
                                </Badge>
                            )}
                        </div>
                        {item.price !== undefined && (
                            <span>{formatCurrency(item.price * item.quantity)}</span>
                        )}
                    </div>
                ))}
                {total !== undefined && (
                    <div className="flex justify-between border-t pt-3 font-bold">
                        <span>الإجمالي</span>
                        <span>{formatCurrency(total)}</span>
                    </div>
                )}
                {actions && <div className="pt-2">{actions}</div>}
            </CardContent>
        </Card>
    );
}
