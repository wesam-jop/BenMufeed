import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    FilePlus2,
    LayoutTemplate,
    Link2,
    Sparkles,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import PageHeader from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

type LocaleOption = { value: string; label: string };

type Props = {
    blockedPaths: string[];
    usedPathsByLocale?: Record<string, string[]>;
    locales: LocaleOption[];
};

function normalizePath(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/^\/+/, '')
        .replace(/[^a-z0-9\-\/]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function CmsPageCreate({
    blockedPaths,
    usedPathsByLocale = {},
    locales,
}: Props) {
    const { t } = useI18n();
    const form = useForm({
        title: '',
        path: '',
        locale: 'ar',
        is_published: true,
    });

    const [pathTouched, setPathTouched] = useState(false);

    const blockedSet = useMemo(
        () => new Set(blockedPaths.map((p) => p.toLowerCase())),
        [blockedPaths],
    );

    const usedInLocale = useMemo(
        () => new Set((usedPathsByLocale[form.data.locale] ?? []).map((p) => p.toLowerCase())),
        [usedPathsByLocale, form.data.locale],
    );

    const normalizedPath = normalizePath(form.data.path);
    const firstSegment = normalizedPath.split('/')[0] ?? '';
    const pathConflict =
        Boolean(normalizedPath) &&
        (blockedSet.has(normalizedPath) ||
            blockedSet.has(firstSegment) ||
            usedInLocale.has(normalizedPath));
    const pathOk = Boolean(normalizedPath) && !pathConflict;
    const canSubmit = Boolean(form.data.title.trim()) && pathOk && !form.processing;

    const pathHint = !normalizedPath
        ? 'حروف إنجليزية صغيرة فقط — مثال: summer-offers'
        : pathConflict
          ? form.data.locale === 'en'
              ? 'This path is reserved or already used for this language'
              : 'هذا المسار محجوز أو مستخدم مسبقاً لهذه اللغة'
          : `الرابط النهائي: /${normalizedPath}`;

    const steps = [
        {
            n: 1,
            title: t('admin.pages.locale'),
            desc: t('admin.pages.locale_hint'),
            icon: FilePlus2,
        },
        {
            n: 2,
            title: t('admin.pages.create_title'),
            desc: t('admin.pages.path'),
            icon: LayoutTemplate,
        },
        {
            n: 3,
            title: t('admin.pages.builder'),
            desc: t('admin.pages.create_desc'),
            icon: Sparkles,
        },
    ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!canSubmit) return;
        form.transform((data) => ({
            ...data,
            path: normalizePath(data.path),
        }));
        form.post('/admin/pages');
    };

    return (
        <>
            <Head title={t('admin.pages.create_title')} />
            <PageHeader
                title={t('admin.pages.create_title')}
                description={t('admin.pages.create_desc')}
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/pages">
                            <ArrowRight className="size-4" />
                            {t('common.back')}
                        </Link>
                    </Button>
                }
            />

            <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[280px_1fr]">
                <aside className="space-y-4">
                    <Card>
                        <CardContent className="space-y-4 p-5">
                            <p className="text-sm font-semibold">{t('admin.pages.create_title')}</p>
                            <ol className="space-y-4">
                                {steps.map((step) => {
                                    const Icon = step.icon;
                                    return (
                                        <li key={step.n} className="flex gap-3">
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-900 ring-1 ring-amber-100">
                                                <Icon className="size-4" />
                                            </span>
                                            <span>
                                                <span className="block text-sm font-medium">
                                                    {step.n}. {step.title}
                                                </span>
                                                <span className="mt-0.5 block text-xs text-muted-foreground">
                                                    {step.desc}
                                                </span>
                                            </span>
                                        </li>
                                    );
                                })}
                            </ol>
                        </CardContent>
                    </Card>
                </aside>

                <Card>
                    <CardContent className="p-6 md:p-8">
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label className="text-sm">{t('admin.pages.locale')}</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    {locales.map((opt) => (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() => form.setData('locale', opt.value)}
                                            className={cn(
                                                'rounded-xl border px-4 py-3 text-sm font-medium transition-colors',
                                                form.data.locale === opt.value
                                                    ? 'border-stone-900 bg-stone-900 text-white'
                                                    : 'bg-muted/20 hover:bg-muted/40',
                                            )}
                                        >
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {t('admin.pages.locale_hint')}
                                </p>
                                {form.errors.locale ? (
                                    <p className="text-xs text-destructive">{form.errors.locale}</p>
                                ) : null}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title" className="text-sm">
                                    {t('admin.pages.page_title')}
                                </Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder={
                                        form.data.locale === 'en'
                                            ? 'e.g. Ramadan offers'
                                            : 'مثال: عروض رمضان'
                                    }
                                    className="h-11 text-base"
                                    autoFocus
                                />
                                {form.errors.title ? (
                                    <p className="text-xs text-destructive">{form.errors.title}</p>
                                ) : null}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="path" className="text-sm">
                                    {t('admin.pages.path')}
                                </Label>
                                <div className="flex overflow-hidden rounded-lg border focus-within:ring-2 focus-within:ring-ring/40">
                                    <span
                                        className="flex items-center gap-1.5 border-e bg-muted/50 px-3 text-sm text-muted-foreground"
                                        dir="ltr"
                                    >
                                        <Link2 className="size-3.5" />
                                        /
                                    </span>
                                    <Input
                                        id="path"
                                        dir="ltr"
                                        value={form.data.path}
                                        onChange={(e) => {
                                            setPathTouched(true);
                                            form.setData('path', e.target.value);
                                        }}
                                        placeholder="ramadan-offers"
                                        className={cn(
                                            'h-11 rounded-none border-0 text-base shadow-none focus-visible:ring-0',
                                            pathTouched && pathConflict && 'text-destructive',
                                        )}
                                    />
                                </div>
                                <p
                                    className={cn(
                                        'flex items-start gap-1.5 text-xs',
                                        pathConflict
                                            ? 'text-destructive'
                                            : pathOk
                                              ? 'text-emerald-700'
                                              : 'text-muted-foreground',
                                    )}
                                >
                                    {pathOk ? (
                                        <CheckCircle2 className="mt-0.5 size-3.5 shrink-0" />
                                    ) : null}
                                    <span>{form.errors.path || pathHint}</span>
                                </p>
                            </div>

                            <div className="flex items-center justify-between gap-4 rounded-xl border bg-muted/20 px-4 py-3">
                                <div>
                                    <Label htmlFor="is_published" className="text-sm font-medium">
                                        {t('admin.pages.publish_now')}
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        {t('admin.pages.publish_hint')}
                                    </p>
                                </div>
                                <Checkbox
                                    id="is_published"
                                    checked={form.data.is_published}
                                    onCheckedChange={(v) =>
                                        form.setData('is_published', v === true)
                                    }
                                />
                            </div>

                            <div className="flex flex-col-reverse gap-2 border-t pt-5 sm:flex-row sm:justify-end">
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/admin/pages">{t('common.cancel')}</Link>
                                </Button>
                                <Button type="submit" className="min-w-44" disabled={!canSubmit}>
                                    {form.processing
                                        ? t('admin.pages.creating')
                                        : t('admin.pages.create_submit')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
