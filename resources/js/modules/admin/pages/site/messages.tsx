import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Eye,
    Filter,
    Mail,
    MailOpen,
    MessageSquare,
    RotateCcw,
} from 'lucide-react';
import { useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import PaginationLinks from '@/components/admin/pagination-links';
import ResourceActions from '@/components/admin/resource-actions';
import StatCard from '@/components/domain/StatCard';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useI18n } from '@/hooks/use-i18n';
import { formatDate } from '@/lib/format';
import type { Paginated } from '@/types/admin';

type ContactMessage = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
    message: string;
    message_preview: string;
    is_read: boolean;
    whatsapp_url?: string | null;
    created_at: string | null;
};

type Summary = {
    total_count: number;
    unread_count: number;
    read_count: number;
};

type ListSummary = {
    count: number;
    unread_count: number;
};

type FilterOption = { value: string; label: string };

type Filters = {
    search: string;
    status: string;
};

type Props = {
    messages: Paginated<ContactMessage>;
    summary: Summary;
    listSummary: ListSummary;
    filters: Filters;
    filterOptions: { statuses: FilterOption[] };
};

export default function SiteMessagesPage({
    messages,
    summary,
    listSummary,
    filters,
    filterOptions,
}: Props) {
    const { t } = useI18n();
    const [localFilters, setLocalFilters] = useState(filters);
    const rows = messages.data ?? [];

    const applyFilters = () => {
        router.get('/admin/site/messages', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const reset: Filters = { search: '', status: 'all' };
        setLocalFilters(reset);
        router.get('/admin/site/messages', reset, { preserveState: true });
    };

    const quickFilter = (status: string) => {
        const next = { ...localFilters, status };
        setLocalFilters(next);
        router.get('/admin/site/messages', next, { preserveState: true });
    };

    const markAllRead = () => {
        router.patch('/admin/site/messages/read-all', {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('admin.messages.title')} />
            <PageHeader
                title={t('admin.messages.title')}
                description={t('admin.messages.description')}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/site">
                                <ArrowRight className="size-4" />
                                {t('admin.messages.site_settings')}
                            </Link>
                        </Button>
                        {summary.unread_count > 0 && (
                            <Button variant="outline" onClick={markAllRead}>
                                <MailOpen className="size-4" />
                                {t('admin.messages.mark_all_read')}
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard
                    title={t('admin.messages.stat_total')}
                    value={summary.total_count}
                    format="number"
                    icon={MessageSquare}
                />
                <StatCard
                    title={t('admin.messages.stat_unread')}
                    value={summary.unread_count}
                    format="number"
                    icon={Mail}
                />
                <StatCard
                    title={t('admin.messages.stat_read')}
                    value={summary.read_count}
                    format="number"
                    icon={MailOpen}
                />
            </div>

            <div className="mb-4 flex flex-wrap gap-2">
                {filterOptions.statuses.map((option) => (
                    <Button
                        key={option.value}
                        type="button"
                        size="sm"
                        variant={filters.status === option.value ? 'default' : 'outline'}
                        onClick={() => quickFilter(option.value)}
                    >
                        {option.label}
                        {option.value === 'unread' && summary.unread_count > 0 && (
                            <Badge variant="secondary" className="ms-2">
                                {summary.unread_count}
                            </Badge>
                        )}
                    </Button>
                ))}
            </div>

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Filter className="size-4" />
                        {t('common.filters')}
                        {(filters.search || filters.status !== 'all') && (
                            <Badge variant="outline">
                                {t('admin.messages.results_count').replace(
                                    ':count',
                                    String(listSummary.count),
                                )}
                            </Badge>
                        )}
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto]">
                    <div className="grid gap-2">
                        <Label htmlFor="status">{t('common.status')}</Label>
                        <Select
                            value={localFilters.status}
                            onValueChange={(value) =>
                                setLocalFilters((prev) => ({ ...prev, status: value }))
                            }
                        >
                            <SelectTrigger id="status" className="h-11 w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {filterOptions.statuses.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="search">{t('common.search')}</Label>
                        <Input
                            id="search"
                            type="search"
                            value={localFilters.search}
                            onChange={(e) =>
                                setLocalFilters((prev) => ({ ...prev, search: e.target.value }))
                            }
                            placeholder={t('admin.messages.search_placeholder')}
                            onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), applyFilters())}
                        />
                    </div>
                    <Button className="self-end" onClick={applyFilters}>
                        {t('common.apply')}
                    </Button>
                    <Button className="self-end" variant="outline" onClick={resetFilters}>
                        <RotateCcw className="size-4" />
                        {t('common.reset')}
                    </Button>
                </CardContent>
            </Card>

            {rows.length === 0 ? (
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-16 text-center">
                        <MessageSquare className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">{t('admin.messages.empty')}</p>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-right">
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.messages.col_sender')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.messages.col_contact')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('admin.messages.col_message')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">{t('common.date')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.status')}</th>
                                        <th className="px-4 py-3 font-medium">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((message) => (
                                        <tr
                                            key={message.id}
                                            className={`border-b hover:bg-muted/20 ${
                                                !message.is_read ? 'bg-amber-50/40 dark:bg-amber-950/10' : ''
                                            }`}
                                        >
                                            <td className="px-4 py-3 font-medium">{message.name}</td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                <div className="grid gap-0.5">
                                                    {message.phone && (
                                                        <span dir="ltr">{message.phone}</span>
                                                    )}
                                                    {message.email && (
                                                        <span dir="ltr" className="text-xs">
                                                            {message.email}
                                                        </span>
                                                    )}
                                                    {!message.phone && !message.email && '—'}
                                                </div>
                                            </td>
                                            <td className="max-w-xs px-4 py-3 text-muted-foreground">
                                                {message.message_preview}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {message.created_at
                                                    ? formatDate(message.created_at)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={message.is_read ? 'secondary' : 'default'}
                                                >
                                                    {message.is_read
                                                        ? t('admin.messages.status_read')
                                                        : t('admin.messages.status_new')}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/site/messages/${message.id}`}>
                                                            <Eye className="size-4" />
                                                            {t('common.view')}
                                                        </Link>
                                                    </Button>
                                                    <ResourceActions
                                                        deleteHref={`/admin/site/messages/${message.id}`}
                                                        deletePermission="settings.update"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="px-4 pb-4">
                            <PaginationLinks paginator={messages} />
                        </div>
                    </CardContent>
                </Card>
            )}
        </>
    );
}
