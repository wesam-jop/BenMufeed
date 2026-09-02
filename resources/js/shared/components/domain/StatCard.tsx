import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatNumber } from '@/lib/format';

type StatCardProps = {
    title: string;
    value: number | string;
    description?: string;
    icon?: LucideIcon;
    format?: 'number' | 'currency' | 'text';
    currency?: string;
    trend?: string;
};

export default function StatCard({
    title,
    value,
    description,
    icon: Icon,
    format = 'text',
    currency = 'SAR',
    trend,
}: StatCardProps) {
    const displayValue =
        format === 'currency' && typeof value === 'number'
            ? formatCurrency(value, currency)
            : format === 'number' && typeof value === 'number'
              ? formatNumber(value)
              : value;

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                {Icon && <Icon className="size-4 text-muted-foreground" />}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{displayValue}</div>
                {description && (
                    <p className="text-xs text-muted-foreground">{description}</p>
                )}
                {trend && (
                    <p className="mt-1 text-xs text-muted-foreground">{trend}</p>
                )}
            </CardContent>
        </Card>
    );
}
