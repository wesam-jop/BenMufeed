import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Banknote,
    Building2,
    Coins,
    Eye,
    EyeOff,
    FileText,
    GripVertical,
    Info,
    LayoutTemplate,
    ListOrdered,
    Minus,
    Plus,
    Receipt,
    RotateCcw,
    Save,
    ShoppingBag,
    Trash2,
    Type,
    MoveVertical,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import PageHeader from '@/components/admin/page-header';
import ReceiptDocument from '@/components/receipt/ReceiptDocument';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    makeSection,
    PAPER_SIZE_OPTIONS,
    SECTION_FIELD_TOGGLES,
    SECTION_LABELS,
    SECTION_TEXT_FIELDS,
    showFlag,
    type ReceiptBusiness,
    type ReceiptInvoice,
    type ReceiptSection,
    type ReceiptSectionProps,
    type ReceiptSectionType,
    type ReceiptTemplate,
} from '@/types/receipt';

type CatalogItem = {
    type: ReceiptSectionType;
    label: string;
    description: string;
};

type Props = {
    template: ReceiptTemplate;
    defaultTemplate: ReceiptTemplate;
    receiptBusiness: ReceiptBusiness;
    sectionCatalog: CatalogItem[];
};

const SAMPLE_INVOICE: ReceiptInvoice = {
    invoice_number: 'INV-1001',
    status: 'paid',
    subtotal: 48,
    discount: 3,
    tax: 6.75,
    total: 51.75,
    paid: 51.75,
    created_at: new Date().toISOString(),
    cashier_name: 'أحمد',
    payment_method: 'cash',
    items: [
        { product_name: 'لاتيه', quantity: 2, unit_price: 14, total: 28 },
        { product_name: 'كرواسون', quantity: 1, unit_price: 10, total: 10 },
        { product_name: 'ماء', quantity: 2, unit_price: 5, total: 10 },
    ],
};

const SAMPLE_CONVERSIONS = [
    {
        code: 'USD',
        name: 'دولار',
        symbol: '$',
        amount: 13.8,
        formatted: '$13.80',
    },
];

const SECTION_ICONS: Record<ReceiptSectionType, typeof Building2> = {
    brand: Building2,
    header_meta: Info,
    invoice_meta: FileText,
    items: ShoppingBag,
    totals: ListOrdered,
    currency_conversions: Coins,
    payment: Banknote,
    custom_text: Type,
    divider: Minus,
    spacer: MoveVertical,
};

export default function ReceiptDesignerPage({
    template: initialTemplate,
    defaultTemplate,
    receiptBusiness,
    sectionCatalog,
}: Props) {
    const { t } = useI18n();
    const [sections, setSections] = useState<ReceiptSection[]>(
        () => initialTemplate.sections ?? [],
    );
    const [paperWidthMm, setPaperWidthMm] = useState<number>(
        () => initialTemplate.paper_width_mm ?? 0,
    );
    const [selectedId, setSelectedId] = useState<string | null>(
        () => initialTemplate.sections[0]?.id ?? null,
    );
    const [saving, setSaving] = useState(false);

    const template: ReceiptTemplate = useMemo(
        () => ({
            paper_width_mm: paperWidthMm,
            sections,
        }),
        [paperWidthMm, sections],
    );

    const previewLabel =
        PAPER_SIZE_OPTIONS.find((o) => o.value === paperWidthMm)?.label ??
        (paperWidthMm > 0 ? `${paperWidthMm}مم` : 'تلقائي');

    const selected = sections.find((s) => s.id === selectedId) ?? null;
    const enabledCount = sections.filter((s) => s.enabled).length;

    const updateSection = (id: string, patch: Partial<ReceiptSection>) => {
        setSections((prev) =>
            prev.map((section) => (section.id === id ? { ...section, ...patch } : section)),
        );
    };

    const updateProps = (id: string, props: Record<string, unknown>) => {
        setSections((prev) =>
            prev.map((section) =>
                section.id === id
                    ? { ...section, props: { ...section.props, ...props } }
                    : section,
            ),
        );
    };

    const addSection = (type: ReceiptSectionType) => {
        const section = makeSection(type);
        setSections((prev) => [...prev, section]);
        setSelectedId(section.id);
        toast.success(t('toast.section_added').replace(':name', SECTION_LABELS[type]));
    };

    const removeSection = (id: string) => {
        setSections((prev) => {
            const next = prev.filter((s) => s.id !== id);
            if (selectedId === id) {
                setSelectedId(next[0]?.id ?? null);
            }
            return next;
        });
    };

    const moveSection = (id: string, direction: -1 | 1) => {
        setSections((prev) => {
            const index = prev.findIndex((s) => s.id === id);
            if (index < 0) {
                return prev;
            }
            const nextIndex = index + direction;
            if (nextIndex < 0 || nextIndex >= prev.length) {
                return prev;
            }
            const copy = [...prev];
            const [item] = copy.splice(index, 1);
            copy.splice(nextIndex, 0, item);
            return copy;
        });
    };

    const save = () => {
        setSaving(true);
        router.put(
            '/admin/settings/receipt-designer',
            { paper_width_mm: paperWidthMm, sections },
            {
                preserveScroll: true,
                onError: () => toast.error(t('toast.receipt_save_failed')),
                onFinish: () => setSaving(false),
            },
        );
    };

    const resetDefault = () => {
        router.post(
            '/admin/settings/receipt-designer/reset',
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const next = (page.props as { template?: ReceiptTemplate }).template;
                    if (next?.sections) {
                        setSections(next.sections);
                        setPaperWidthMm(next.paper_width_mm ?? 0);
                        setSelectedId(next.sections[0]?.id ?? null);
                    } else {
                        setSections(defaultTemplate.sections);
                        setPaperWidthMm(defaultTemplate.paper_width_mm ?? 0);
                        setSelectedId(defaultTemplate.sections[0]?.id ?? null);
                    }
                },
            },
        );
    };

    return (
        <>
            <Head title="تصميم الفاتورة" />
            <PageHeader
                title="تصميم الفاتورة"
                description="تصميم واحد لكل الطابعات — عند الطباعة يتكيّف تلقائياً مع مقاس الورق المختار"
                actions={
                    <div className="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" onClick={resetDefault}>
                            <RotateCcw className="size-4" />
                            إعادة الافتراضي
                        </Button>
                        <Button type="button" onClick={save} disabled={saving}>
                            <Save className="size-4" />
                            {saving ? 'جاري الحفظ...' : 'حفظ التصميم'}
                        </Button>
                    </div>
                }
            />

            <div className="mb-5 flex flex-wrap items-center gap-2 rounded-xl border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                <Receipt className="size-4 shrink-0 text-foreground" />
                <span>
                    اضغط قسماً لتعديله، رتّب بالأسهم، والمعاينة تتحدّث فوراً. الطباعة الفعلية تملأ
                    عرض ورق الطابعة مهما كان المقاس.
                </span>
                <Badge variant="secondary" className="ms-auto">
                    {enabledCount} ظاهر / {sections.length} قسم
                </Badge>
                <Badge variant="outline">أبيض وأسود · متكيّف مع الطابعة</Badge>
            </div>

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                {/* Editor column */}
                <div className="space-y-5">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">مقاس المعاينة</CardTitle>
                            <p className="text-xs text-muted-foreground">
                                للتجربة فقط — عند الطباعة الحقيقية التصميم يتكيّف مع ورق الطابعة
                                (58مم / 80مم / A4 وغيرها)
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                {PAPER_SIZE_OPTIONS.map((opt) => {
                                    const active = paperWidthMm === opt.value;
                                    return (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() => setPaperWidthMm(opt.value)}
                                            className={cn(
                                                'rounded-xl border px-3 py-3 text-right transition',
                                                active
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'bg-background hover:border-primary/40 hover:bg-primary/5',
                                            )}
                                        >
                                            <span className="block text-sm font-semibold">
                                                {opt.label}
                                            </span>
                                            <span
                                                className={cn(
                                                    'mt-0.5 block text-[11px]',
                                                    active
                                                        ? 'text-primary-foreground/80'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {opt.description}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Plus className="size-4" />
                                إضافة قسم جديد
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                اختر نوع القسم ليُضاف أسفل قائمة الفاتورة
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {sectionCatalog.map((item) => {
                                    const Icon = SECTION_ICONS[item.type] ?? LayoutTemplate;
                                    return (
                                        <button
                                            key={item.type}
                                            type="button"
                                            onClick={() => addSection(item.type)}
                                            className="flex items-start gap-3 rounded-xl border bg-background px-3 py-3 text-right transition hover:border-primary/40 hover:bg-primary/5"
                                        >
                                            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                                                <Icon className="size-4 text-muted-foreground" />
                                            </span>
                                            <span className="min-w-0">
                                                <span className="block text-sm font-medium">
                                                    {item.label}
                                                </span>
                                                <span className="mt-0.5 block text-xs text-muted-foreground">
                                                    {item.description}
                                                </span>
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <LayoutTemplate className="size-4" />
                                ترتيب أقسام الفاتورة
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                من الأعلى إلى الأسفل كما ستظهر على ورقة الطباعة
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {sections.length === 0 ? (
                                <div className="rounded-xl border border-dashed py-10 text-center">
                                    <p className="text-sm text-muted-foreground">
                                        لا توجد أقسام بعد — أضف قسماً من الأعلى
                                    </p>
                                </div>
                            ) : (
                                sections.map((section, index) => {
                                    const Icon = SECTION_ICONS[section.type] ?? LayoutTemplate;
                                    const isSelected = selectedId === section.id;

                                    return (
                                        <div
                                            key={section.id}
                                            className={cn(
                                                'rounded-xl border transition',
                                                isSelected
                                                    ? 'border-primary shadow-sm ring-1 ring-primary/15'
                                                    : 'bg-background',
                                                !section.enabled && 'opacity-60',
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    'flex items-center gap-2 px-3 py-2.5',
                                                    isSelected && 'bg-primary/5',
                                                )}
                                            >
                                                <GripVertical className="size-4 shrink-0 text-muted-foreground/50" />
                                                <button
                                                    type="button"
                                                    className="flex min-w-0 flex-1 items-center gap-2 text-right"
                                                    onClick={() => setSelectedId(section.id)}
                                                >
                                                    <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted">
                                                        <Icon className="size-3.5 text-muted-foreground" />
                                                    </span>
                                                    <span className="min-w-0">
                                                        <span className="flex items-center gap-2">
                                                            <span className="text-sm font-medium">
                                                                {SECTION_LABELS[section.type]}
                                                            </span>
                                                            <span className="text-[11px] text-muted-foreground">
                                                                #{index + 1}
                                                            </span>
                                                            {!section.enabled && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="text-[10px]"
                                                                >
                                                                    مخفي
                                                                </Badge>
                                                            )}
                                                        </span>
                                                        {section.type === 'custom_text' &&
                                                            section.props?.text && (
                                                                <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                                                                    {section.props.text}
                                                                </span>
                                                            )}
                                                    </span>
                                                </button>

                                                <div className="flex shrink-0 items-center gap-0.5">
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-8"
                                                        disabled={index === 0}
                                                        title="أعلى"
                                                        onClick={() => moveSection(section.id, -1)}
                                                    >
                                                        <ArrowUp className="size-3.5" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-8"
                                                        disabled={index === sections.length - 1}
                                                        title="أسفل"
                                                        onClick={() => moveSection(section.id, 1)}
                                                    >
                                                        <ArrowDown className="size-3.5" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-8"
                                                        title={
                                                            section.enabled ? 'إخفاء' : 'إظهار'
                                                        }
                                                        onClick={() =>
                                                            updateSection(section.id, {
                                                                enabled: !section.enabled,
                                                            })
                                                        }
                                                    >
                                                        {section.enabled ? (
                                                            <Eye className="size-3.5" />
                                                        ) : (
                                                            <EyeOff className="size-3.5" />
                                                        )}
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="size-8 text-destructive hover:text-destructive"
                                                        title="حذف"
                                                        onClick={() => removeSection(section.id)}
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </div>
                                            </div>

                                            {isSelected && (
                                                <div className="space-y-4 border-t bg-stone-50/80 px-4 py-4 dark:bg-muted/20">
                                                    <label
                                                        htmlFor={`enabled-${section.id}`}
                                                        className="flex cursor-pointer items-center justify-between gap-3 rounded-xl border bg-background px-3.5 py-3 shadow-sm"
                                                    >
                                                        <div>
                                                            <p className="text-sm font-medium">
                                                                القسم كامل ظاهر
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                إخفاء القسم بالكامل من الفاتورة
                                                            </p>
                                                        </div>
                                                        <Checkbox
                                                            id={`enabled-${section.id}`}
                                                            checked={section.enabled}
                                                            onCheckedChange={(checked) =>
                                                                updateSection(section.id, {
                                                                    enabled: checked === true,
                                                                })
                                                            }
                                                        />
                                                    </label>

                                                    {(SECTION_FIELD_TOGGLES[section.type]?.length ??
                                                        0) > 0 && (
                                                        <div className="space-y-2 rounded-xl border bg-background p-4 shadow-sm">
                                                            <p className="text-sm font-semibold">
                                                                إظهار / إخفاء الحقول
                                                            </p>
                                                            <div className="grid gap-2 sm:grid-cols-2">
                                                                {SECTION_FIELD_TOGGLES[
                                                                    section.type
                                                                ]!.map((field) => {
                                                                    const checked = showFlag(
                                                                        section.props,
                                                                        field.key,
                                                                        field.key ===
                                                                            'show_unit_price'
                                                                            ? false
                                                                            : true,
                                                                    );
                                                                    return (
                                                                        <label
                                                                            key={field.key}
                                                                            htmlFor={`${section.id}-${field.key}`}
                                                                            className="flex cursor-pointer items-center justify-between gap-2 rounded-lg border px-3 py-2.5"
                                                                        >
                                                                            <span className="text-sm">
                                                                                {field.label}
                                                                            </span>
                                                                            <Checkbox
                                                                                id={`${section.id}-${field.key}`}
                                                                                checked={checked}
                                                                                onCheckedChange={(
                                                                                    value,
                                                                                ) =>
                                                                                    updateProps(
                                                                                        section.id,
                                                                                        {
                                                                                            [field.key]:
                                                                                                value ===
                                                                                                true,
                                                                                        } as ReceiptSectionProps,
                                                                                    )
                                                                                }
                                                                            />
                                                                        </label>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {(SECTION_TEXT_FIELDS[section.type]?.length ??
                                                        0) > 0 && (
                                                        <div className="space-y-3 rounded-xl border bg-background p-4 shadow-sm">
                                                            <p className="text-sm font-semibold">
                                                                تعديل النصوص والتسميات
                                                            </p>
                                                            <div className="grid gap-3 sm:grid-cols-2">
                                                                {SECTION_TEXT_FIELDS[
                                                                    section.type
                                                                ]!.map((field) => {
                                                                    const value =
                                                                        (section.props?.[
                                                                            field.key
                                                                        ] as string | undefined) ??
                                                                        '';
                                                                    return (
                                                                        <div
                                                                            key={field.key}
                                                                            className={cn(
                                                                                'grid gap-1.5',
                                                                                field.multiline &&
                                                                                    'sm:col-span-2',
                                                                            )}
                                                                        >
                                                                            <Label
                                                                                htmlFor={`${section.id}-${field.key}`}
                                                                                className="text-xs font-medium"
                                                                            >
                                                                                {field.label}
                                                                            </Label>
                                                                            {field.multiline ? (
                                                                                <textarea
                                                                                    id={`${section.id}-${field.key}`}
                                                                                    rows={3}
                                                                                    className="w-full rounded-xl border border-input bg-muted/20 px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"
                                                                                    value={value}
                                                                                    placeholder={
                                                                                        field.placeholder
                                                                                    }
                                                                                    onChange={(e) =>
                                                                                        updateProps(
                                                                                            section.id,
                                                                                            {
                                                                                                [field.key]:
                                                                                                    e
                                                                                                        .target
                                                                                                        .value,
                                                                                            } as ReceiptSectionProps,
                                                                                        )
                                                                                    }
                                                                                />
                                                                            ) : (
                                                                                <Input
                                                                                    id={`${section.id}-${field.key}`}
                                                                                    className="h-10"
                                                                                    value={value}
                                                                                    placeholder={
                                                                                        field.placeholder
                                                                                    }
                                                                                    onChange={(e) =>
                                                                                        updateProps(
                                                                                            section.id,
                                                                                            {
                                                                                                [field.key]:
                                                                                                    e
                                                                                                        .target
                                                                                                        .value,
                                                                                            } as ReceiptSectionProps,
                                                                                        )
                                                                                    }
                                                                                />
                                                                            )}
                                                                        </div>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {section.type === 'custom_text' && (
                                                        <div className="space-y-4 rounded-xl border bg-background p-4 shadow-sm">
                                                            <div className="space-y-2">
                                                                <Label
                                                                    htmlFor={`text-${section.id}`}
                                                                    className="text-sm font-medium"
                                                                >
                                                                    نص التذييل / الملاحظة
                                                                </Label>
                                                                <textarea
                                                                    id={`text-${section.id}`}
                                                                    rows={3}
                                                                    className="w-full resize-y rounded-xl border border-input bg-muted/20 px-3.5 py-3 text-sm leading-6 outline-none transition focus:border-primary focus:bg-background focus:ring-2 focus:ring-primary/15"
                                                                    value={section.props?.text ?? ''}
                                                                    onChange={(e) =>
                                                                        updateProps(section.id, {
                                                                            text: e.target.value,
                                                                        })
                                                                    }
                                                                    placeholder="مثال: شكراً لزيارتكم — نتمنى لكم يوماً سعيداً"
                                                                />
                                                            </div>

                                                            <div className="space-y-2">
                                                                <p className="text-sm font-medium">
                                                                    المحاذاة
                                                                </p>
                                                                <div className="grid grid-cols-3 gap-2">
                                                                    {(
                                                                        [
                                                                            {
                                                                                value: 'right',
                                                                                label: 'يمين',
                                                                            },
                                                                            {
                                                                                value: 'center',
                                                                                label: 'وسط',
                                                                            },
                                                                            {
                                                                                value: 'left',
                                                                                label: 'يسار',
                                                                            },
                                                                        ] as const
                                                                    ).map((opt) => {
                                                                        const active =
                                                                            (section.props
                                                                                ?.align ??
                                                                                'center') ===
                                                                            opt.value;
                                                                        return (
                                                                            <button
                                                                                key={opt.value}
                                                                                type="button"
                                                                                onClick={() =>
                                                                                    updateProps(
                                                                                        section.id,
                                                                                        {
                                                                                            align: opt.value,
                                                                                        },
                                                                                    )
                                                                                }
                                                                                className={cn(
                                                                                    'rounded-lg border px-3 py-2.5 text-sm font-medium transition',
                                                                                    active
                                                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                                                        : 'bg-background hover:bg-muted/60',
                                                                                )}
                                                                            >
                                                                                {opt.label}
                                                                            </button>
                                                                        );
                                                                    })}
                                                                </div>
                                                            </div>

                                                            <div className="space-y-2">
                                                                <p className="text-sm font-medium">
                                                                    الحجم
                                                                </p>
                                                                <div className="grid grid-cols-3 gap-2">
                                                                    {(
                                                                        [
                                                                            {
                                                                                value: 'sm',
                                                                                label: 'صغير',
                                                                            },
                                                                            {
                                                                                value: 'md',
                                                                                label: 'متوسط',
                                                                            },
                                                                            {
                                                                                value: 'lg',
                                                                                label: 'كبير',
                                                                            },
                                                                        ] as const
                                                                    ).map((opt) => {
                                                                        const active =
                                                                            (section.props?.size ??
                                                                                'sm') ===
                                                                            opt.value;
                                                                        return (
                                                                            <button
                                                                                key={opt.value}
                                                                                type="button"
                                                                                onClick={() =>
                                                                                    updateProps(
                                                                                        section.id,
                                                                                        {
                                                                                            size: opt.value,
                                                                                        },
                                                                                    )
                                                                                }
                                                                                className={cn(
                                                                                    'rounded-lg border px-3 py-2.5 text-sm font-medium transition',
                                                                                    active
                                                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                                                        : 'bg-background hover:bg-muted/60',
                                                                                )}
                                                                            >
                                                                                {opt.label}
                                                                            </button>
                                                                        );
                                                                    })}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}

                                                    {section.type === 'spacer' && (
                                                        <div className="space-y-2 rounded-xl border bg-background p-4 shadow-sm">
                                                            <Label
                                                                htmlFor={`height-${section.id}`}
                                                                className="text-sm font-medium"
                                                            >
                                                                ارتفاع المسافة
                                                            </Label>
                                                            <div className="flex items-center gap-3">
                                                                <Input
                                                                    id={`height-${section.id}`}
                                                                    type="number"
                                                                    min={4}
                                                                    max={48}
                                                                    className="h-11"
                                                                    value={
                                                                        section.props?.height ?? 12
                                                                    }
                                                                    onChange={(e) =>
                                                                        updateProps(section.id, {
                                                                            height:
                                                                                Number(
                                                                                    e.target.value,
                                                                                ) || 12,
                                                                        })
                                                                    }
                                                                />
                                                                <span className="shrink-0 text-sm text-muted-foreground">
                                                                    بكسل
                                                                </span>
                                                            </div>
                                                            <div className="flex flex-wrap gap-2 pt-1">
                                                                {[8, 12, 16, 24].map((h) => (
                                                                    <button
                                                                        key={h}
                                                                        type="button"
                                                                        onClick={() =>
                                                                            updateProps(
                                                                                section.id,
                                                                                { height: h },
                                                                            )
                                                                        }
                                                                        className={cn(
                                                                            'rounded-lg border px-3 py-1.5 text-xs font-medium transition',
                                                                            (section.props
                                                                                ?.height ??
                                                                                12) === h
                                                                                ? 'border-primary bg-primary/10 text-primary'
                                                                                : 'hover:bg-muted/60',
                                                                        )}
                                                                    >
                                                                        {h}px
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {section.type === 'divider' && (
                                                        <p className="rounded-xl border border-dashed bg-background/60 px-3.5 py-3 text-xs leading-5 text-muted-foreground">
                                                            فاصل بصري بين الأقسام — رتّبه أو أخفه حسب
                                                            الحاجة.
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Sticky preview */}
                <div className="xl:sticky xl:top-4 xl:self-start">
                    <Card className="overflow-hidden">
                        <CardHeader className="border-b bg-muted/30 pb-3">
                            <CardTitle className="text-base">معاينة مباشرة</CardTitle>
                            <p className="text-xs text-muted-foreground">
                                محاكاة {previewLabel} · الطباعة تملأ ورق الطابعة فعلياً
                            </p>
                        </CardHeader>
                        <CardContent className="bg-[linear-gradient(180deg,#e7e5e4_0%,#f5f5f4_40%,#fafaf9_100%)] px-4 py-8">
                            <div className="mx-auto w-fit max-w-full">
                                <div className="mb-2 text-center text-[11px] font-medium tracking-wide text-stone-500">
                                    {previewLabel}
                                    {paperWidthMm === 0 ? ' (محاكاة 80مم)' : ''}
                                </div>
                                <div className="max-w-full overflow-x-auto overflow-hidden rounded-sm border border-stone-300 bg-white shadow-[0_12px_40px_-12px_rgba(0,0,0,0.35)] ring-1 ring-black/5">
                                    <ReceiptDocument
                                        mode="preview"
                                        template={template}
                                        invoice={SAMPLE_INVOICE}
                                        business={receiptBusiness}
                                        currencyConversions={SAMPLE_CONVERSIONS}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="mt-3 flex gap-2">
                        <Button
                            type="button"
                            className="flex-1"
                            onClick={save}
                            disabled={saving}
                        >
                            <Save className="size-4" />
                            {saving ? 'جاري الحفظ...' : 'حفظ'}
                        </Button>
                        <Button type="button" variant="outline" onClick={resetDefault}>
                            <RotateCcw className="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
