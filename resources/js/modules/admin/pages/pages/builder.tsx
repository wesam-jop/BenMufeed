import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Eye,
    EyeOff,
    GripVertical,
    LayoutGrid,
    MapPin,
    MessageCircle,
    MousePointerClick,
    Package,
    PenLine,
    Plus,
    Save,
    Settings2,
    ShieldCheck,
    Sparkles,
    Text,
    Trash2,
    Type,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import StorefrontPageRenderer from '@/components/storefront/page-renderer/StorefrontPageRenderer';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { StorefrontBusiness } from '@/layouts/storefront-layout';
import { cn } from '@/lib/utils';
import {
    CMS_SECTION_PROP_FIELDS,
    makeCmsSection,
    type CmsCatalogItem,
    type CmsPagePayload,
    type CmsPageTheme,
    type CmsSection,
    type CmsSectionType,
} from '@/types/cms-page';

type Category = {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    image_url?: string | null;
    products_count?: number;
};

type Product = {
    id: number;
    name: string;
    sale_price: number | string;
    description?: string | null;
    image?: string | null;
    image_url?: string | null;
    category?: { id: number; name: string; slug?: string } | null;
};

type Props = {
    page: CmsPagePayload;
    catalog: CmsCatalogItem[];
    fontOptions: Array<{ value: string; label: string }>;
    categories: Category[];
    featuredProducts: Product[];
    business: StorefrontBusiness & Record<string, unknown>;
};

const CATALOG_ICONS: Record<string, ReactNode> = {
    sparkles: <Sparkles className="size-5" />,
    shield: <ShieldCheck className="size-5" />,
    layout: <LayoutGrid className="size-5" />,
    package: <Package className="size-5" />,
    text: <Type className="size-5" />,
    mouse: <MousePointerClick className="size-5" />,
    message: <MessageCircle className="size-5" />,
    map: <MapPin className="size-5" />,
    pen: <PenLine className="size-5" />,
};

function SortableSectionItem({
    section,
    label,
    index,
    selected,
    onSelect,
    onToggle,
    onRemove,
}: {
    section: CmsSection;
    label: string;
    index: number;
    selected: boolean;
    onSelect: () => void;
    onToggle: () => void;
    onRemove: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: section.id,
    });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
            className={cn(
                'flex items-center gap-1 rounded-xl border bg-card px-1.5 py-2 text-sm transition',
                selected && 'border-primary bg-primary/5 ring-1 ring-primary/25',
                isDragging && 'z-10 opacity-80 shadow-lg',
                !section.enabled && 'opacity-50',
            )}
        >
            <button
                type="button"
                className="cursor-grab touch-none rounded-lg p-1.5 text-muted-foreground hover:bg-muted"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="size-4" />
            </button>
            <button
                type="button"
                className="min-w-0 flex-1 truncate px-1 text-start"
                onClick={onSelect}
            >
                <span className="me-1.5 inline-flex size-5 items-center justify-center rounded-md bg-muted text-[10px] font-semibold text-muted-foreground">
                    {index + 1}
                </span>
                <span className="font-medium">{label}</span>
            </button>
            <Button type="button" variant="ghost" size="icon" className="size-7" onClick={onToggle}>
                {section.enabled ? <Eye className="size-3.5" /> : <EyeOff className="size-3.5" />}
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-7 text-destructive"
                onClick={onRemove}
            >
                <Trash2 className="size-3.5" />
            </Button>
        </div>
    );
}

function BlockLibraryGrid({
    catalog,
    onAdd,
    compact = false,
}: {
    catalog: CmsCatalogItem[];
    onAdd: (type: CmsSectionType) => void;
    compact?: boolean;
}) {
    return (
        <div className={cn('grid gap-2', compact ? 'grid-cols-1' : 'sm:grid-cols-2 lg:grid-cols-3')}>
            {catalog.map((entry) => (
                <button
                    key={entry.type}
                    type="button"
                    onClick={() => onAdd(entry.type)}
                    className={cn(
                        'group flex items-start gap-3 rounded-xl border bg-card p-3 text-start transition',
                        'hover:border-primary hover:bg-primary/5 hover:shadow-sm',
                        'active:scale-[0.99]',
                    )}
                >
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-900 ring-1 ring-amber-100 group-hover:bg-primary group-hover:text-primary-foreground group-hover:ring-primary/20">
                        {CATALOG_ICONS[entry.icon ?? ''] ?? <Plus className="size-5" />}
                    </span>
                    <span className="min-w-0 flex-1">
                        <span className="flex items-center justify-between gap-2">
                            <span className="font-semibold">{entry.label}</span>
                            <Plus className="size-4 shrink-0 text-muted-foreground group-hover:text-primary" />
                        </span>
                        <span className="mt-0.5 block text-xs leading-relaxed text-muted-foreground">
                            {entry.description}
                        </span>
                        <span className="mt-1.5 block text-[10px] text-muted-foreground/80">
                            {entry.variants.length} تصاميم جاهزة
                        </span>
                    </span>
                </button>
            ))}
        </div>
    );
}

function TrustItemsEditor({
    items,
    onChange,
}: {
    items: Array<{ title?: string; description?: string }>;
    onChange: (items: Array<{ title?: string; description?: string }>) => void;
}) {
    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <Label className="text-xs">عناصر الثقة / المميزات</Label>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="h-7 text-xs"
                    onClick={() => onChange([...items, { title: 'ميزة جديدة', description: '' }])}
                >
                    <Plus className="size-3.5" />
                    إضافة عنصر
                </Button>
            </div>
            {items.length === 0 ? (
                <p className="rounded-lg border border-dashed px-3 py-4 text-center text-xs text-muted-foreground">
                    لا توجد عناصر — اضغط «إضافة عنصر»
                </p>
            ) : (
                items.map((item, index) => (
                    <div key={index} className="space-y-2 rounded-xl border bg-muted/20 p-3">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-medium text-muted-foreground">
                                عنصر {index + 1}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-7 text-destructive"
                                onClick={() => onChange(items.filter((_, i) => i !== index))}
                            >
                                <Trash2 className="size-3.5" />
                            </Button>
                        </div>
                        <Input
                            placeholder="العنوان"
                            value={item.title ?? ''}
                            onChange={(e) => {
                                const next = [...items];
                                next[index] = { ...next[index], title: e.target.value };
                                onChange(next);
                            }}
                        />
                        <Input
                            placeholder="وصف قصير (اختياري)"
                            value={item.description ?? ''}
                            onChange={(e) => {
                                const next = [...items];
                                next[index] = { ...next[index], description: e.target.value };
                                onChange(next);
                            }}
                        />
                    </div>
                ))
            )}
        </div>
    );
}

export default function CmsPageBuilder({
    page,
    catalog,
    fontOptions,
    categories,
    featuredProducts,
    business,
}: Props) {
    const { t } = useI18n();
    const [title, setTitle] = useState(page.title);
    const [path, setPath] = useState(page.path);
    const [theme, setTheme] = useState<CmsPageTheme>({ ...page.theme });
    const [seoTitle, setSeoTitle] = useState(page.seo_title ?? '');
    const [seoDescription, setSeoDescription] = useState(page.seo_description ?? '');
    const [isPublished, setIsPublished] = useState(page.is_published);
    const [sections, setSections] = useState<CmsSection[]>(
        Array.isArray(page.sections) ? [...page.sections] : [],
    );
    const [selectedId, setSelectedId] = useState<string | null>(sections[0]?.id ?? null);
    const [leftTab, setLeftTab] = useState<'blocks' | 'page' | 'theme'>(
        sections.length === 0 ? 'blocks' : 'page',
    );
    const [saving, setSaving] = useState(false);
    const propsTopRef = useRef<HTMLDivElement>(null);

    const catalogByType = useMemo(() => {
        const map = new Map<string, CmsCatalogItem>();
        catalog.forEach((c) => map.set(c.type, c));
        return map;
    }, [catalog]);

    const selected = sections.find((s) => s.id === selectedId) ?? null;

    useEffect(() => {
        if (selectedId && propsTopRef.current) {
            propsTopRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, [selectedId]);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const onDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) return;
        setSections((items) => {
            const oldIndex = items.findIndex((i) => i.id === active.id);
            const newIndex = items.findIndex((i) => i.id === over.id);
            if (oldIndex < 0 || newIndex < 0) return items;
            return arrayMove(items, oldIndex, newIndex);
        });
    };

    const addSection = (type: CmsSectionType) => {
        const entry = catalogByType.get(type);
        const variant = entry?.variants[0]?.id ?? 'design-1';
        const section = makeCmsSection(type, variant);
        setSections((prev) => [...prev, section]);
        setSelectedId(section.id);
        setLeftTab('page');
        toast.success(t('toast.block_added').replace(':name', entry?.label ?? type));
    };

    const updateSelected = (patch: Partial<CmsSection>) => {
        if (!selectedId) return;
        setSections((prev) => prev.map((s) => (s.id === selectedId ? { ...s, ...patch } : s)));
    };

    const updateProp = (key: string, value: unknown) => {
        if (!selected) return;
        updateSelected({ props: { ...selected.props, [key]: value } });
    };

    const save = () => {
        setSaving(true);
        router.put(
            `/admin/pages/${page.id}/builder`,
            {
                title,
                path,
                theme,
                seo_title: seoTitle,
                seo_description: seoDescription,
                sections,
                is_published: isPublished,
            } as never,
            {
                preserveScroll: true,
                onSuccess: () => toast.success(t('toast.page_saved_fe')),
                onError: () => toast.error(t('toast.page_save_failed')),
                onFinish: () => setSaving(false),
            },
        );
    };

    const propFields = selected
        ? (CMS_SECTION_PROP_FIELDS[selected.type as CmsSectionType] ?? [])
        : [];

    const trustItems = Array.isArray(selected?.props?.items)
        ? (selected!.props.items as Array<{ title?: string; description?: string }>)
        : [];

    return (
        <>
            <Head title={`محرّر: ${page.title}`} />
            <div className="flex h-[calc(100vh-4rem)] min-h-[640px] flex-col gap-3">
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border bg-card px-4 py-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/admin/pages">
                                <ArrowRight className="size-4" />
                                الصفحات
                            </Link>
                        </Button>
                        <Input
                            className="h-9 max-w-xs font-semibold"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                        />
                        <span className="text-xs text-muted-foreground" dir="ltr">
                            {page.display_path ?? (page.path === 'home' ? '/' : `/${page.path}`)}
                        </span>
                        <span className="inline-flex items-center rounded-full border bg-muted/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide">
                            {(page.locale || 'ar') === 'en' ? 'EN' : 'AR'}
                        </span>
                        {page.is_system ? (
                            <span className="inline-flex items-center gap-1 rounded-full border bg-muted/50 px-2.5 py-1 text-[11px] text-muted-foreground">
                                صفحة نظام محمية
                            </span>
                        ) : null}
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            type="button"
                            onClick={() => setLeftTab('blocks')}
                        >
                            <Plus className="size-4" />
                            إضافة بلوك
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={page.public_url} target="_blank" rel="noreferrer">
                                معاينة الموقع
                            </a>
                        </Button>
                        {!page.is_system ? (
                            <Button
                                variant="outline"
                                size="sm"
                                type="button"
                                className="border-destructive/40 text-destructive hover:bg-destructive/10"
                                onClick={() => {
                                    if (
                                        confirm(
                                            `حذف صفحة «${title}» نهائياً؟\nلا يمكن التراجع.`,
                                        )
                                    ) {
                                        router.delete(`/admin/pages/${page.id}`, {
                                            onSuccess: () =>
                                                toast.success(t('toast.page_deleted')),
                                        });
                                    }
                                }}
                            >
                                <Trash2 className="size-4" />
                                حذف الصفحة
                            </Button>
                        ) : null}
                        <Button size="sm" onClick={save} disabled={saving}>
                            <Save className="size-4" />
                            {saving ? 'جاري الحفظ…' : 'حفظ'}
                        </Button>
                    </div>
                </div>

                <div className="grid min-h-0 flex-1 gap-3 lg:grid-cols-[300px_1fr_320px]">
                    {/* Left: blocks / page sections / theme */}
                    <aside className="flex min-h-0 flex-col overflow-hidden rounded-xl border bg-card">
                        <div className="flex border-b">
                            {(
                                [
                                    ['blocks', 'البلوكات'],
                                    ['page', `الصفحة (${sections.length})`],
                                    ['theme', 'الثيم'],
                                ] as const
                            ).map(([id, label]) => (
                                <button
                                    key={id}
                                    type="button"
                                    className={cn(
                                        'flex-1 px-2 py-2.5 text-xs font-medium',
                                        leftTab === id
                                            ? 'border-b-2 border-primary text-primary'
                                            : 'text-muted-foreground',
                                    )}
                                    onClick={() => setLeftTab(id)}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>

                        <div className="min-h-0 flex-1 overflow-y-auto p-3">
                            {leftTab === 'blocks' && (
                                <div className="space-y-3">
                                    <div className="rounded-xl bg-amber-50/80 px-3 py-2.5 text-xs leading-relaxed text-amber-950 ring-1 ring-amber-100">
                                        اضغط على أي بلوك لإضافته للصفحة، ثم عبّئ النصوص من اللوحة
                                        اليمنى واختر التصميم.
                                    </div>
                                    <BlockLibraryGrid
                                        catalog={catalog}
                                        onAdd={addSection}
                                        compact
                                    />
                                </div>
                            )}

                            {leftTab === 'page' && (
                                <div className="space-y-3">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        onClick={() => setLeftTab('blocks')}
                                    >
                                        <Plus className="size-4" />
                                        إضافة بلوك جديد
                                    </Button>

                                    {sections.length === 0 ? (
                                        <div className="rounded-xl border border-dashed px-3 py-8 text-center">
                                            <LayoutGrid className="mx-auto size-8 text-muted-foreground/50" />
                                            <p className="mt-3 text-sm font-medium">الصفحة فارغة</p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                ابدأ بإضافة بلوك من تبويب «البلوكات»
                                            </p>
                                            <Button
                                                type="button"
                                                size="sm"
                                                className="mt-4"
                                                onClick={() => setLeftTab('blocks')}
                                            >
                                                عرض كل البلوكات
                                            </Button>
                                        </div>
                                    ) : (
                                        <DndContext
                                            sensors={sensors}
                                            collisionDetection={closestCenter}
                                            onDragEnd={onDragEnd}
                                        >
                                            <SortableContext
                                                items={sections.map((s) => s.id)}
                                                strategy={verticalListSortingStrategy}
                                            >
                                                <div className="space-y-2">
                                                    {sections.map((section, index) => (
                                                        <SortableSectionItem
                                                            key={section.id}
                                                            section={section}
                                                            index={index}
                                                            label={
                                                                catalogByType.get(section.type)
                                                                    ?.label ?? section.type
                                                            }
                                                            selected={selectedId === section.id}
                                                            onSelect={() =>
                                                                setSelectedId(section.id)
                                                            }
                                                            onToggle={() =>
                                                                setSections((prev) =>
                                                                    prev.map((s) =>
                                                                        s.id === section.id
                                                                            ? {
                                                                                  ...s,
                                                                                  enabled:
                                                                                      !s.enabled,
                                                                              }
                                                                            : s,
                                                                    ),
                                                                )
                                                            }
                                                            onRemove={() => {
                                                                setSections((prev) =>
                                                                    prev.filter(
                                                                        (s) => s.id !== section.id,
                                                                    ),
                                                                );
                                                                if (selectedId === section.id) {
                                                                    setSelectedId(null);
                                                                }
                                                            }}
                                                        />
                                                    ))}
                                                </div>
                                            </SortableContext>
                                        </DndContext>
                                    )}
                                    {sections.length > 0 ? (
                                        <p className="text-center text-[11px] text-muted-foreground">
                                            اسحب للترتيب · اضغط للتعديل
                                        </p>
                                    ) : null}
                                </div>
                            )}

                            {leftTab === 'theme' && (
                                <div className="space-y-4">
                                    {!page.is_system && (
                                        <div className="grid gap-1.5">
                                            <Label>المسار</Label>
                                            <Input
                                                dir="ltr"
                                                value={path}
                                                onChange={(e) => setPath(e.target.value)}
                                            />
                                        </div>
                                    )}
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="published"
                                            checked={isPublished}
                                            disabled={page.is_system}
                                            onCheckedChange={(v) => setIsPublished(v === true)}
                                        />
                                        <Label htmlFor="published">منشورة</Label>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>اللون الأساسي</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                type="color"
                                                className="h-9 w-12 p-1"
                                                value={theme.primary || '#78350f'}
                                                onChange={(e) =>
                                                    setTheme((t) => ({
                                                        ...t,
                                                        primary: e.target.value,
                                                    }))
                                                }
                                            />
                                            <Input
                                                dir="ltr"
                                                value={theme.primary}
                                                onChange={(e) =>
                                                    setTheme((t) => ({
                                                        ...t,
                                                        primary: e.target.value,
                                                    }))
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>اللون الثانوي</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                type="color"
                                                className="h-9 w-12 p-1"
                                                value={theme.secondary || '#a16207'}
                                                onChange={(e) =>
                                                    setTheme((t) => ({
                                                        ...t,
                                                        secondary: e.target.value,
                                                    }))
                                                }
                                            />
                                            <Input
                                                dir="ltr"
                                                value={theme.secondary}
                                                onChange={(e) =>
                                                    setTheme((t) => ({
                                                        ...t,
                                                        secondary: e.target.value,
                                                    }))
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>الخط</Label>
                                        <select
                                            className="h-9 w-full rounded-md border bg-background px-2 text-sm"
                                            value={theme.font_family}
                                            onChange={(e) =>
                                                setTheme((t) => ({
                                                    ...t,
                                                    font_family: e.target.value,
                                                }))
                                            }
                                        >
                                            {fontOptions.map((f) => (
                                                <option key={f.value} value={f.value}>
                                                    {f.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>حجم الخط (px)</Label>
                                        <Input
                                            type="number"
                                            min={12}
                                            max={22}
                                            value={theme.base_font_size}
                                            onChange={(e) =>
                                                setTheme((t) => ({
                                                    ...t,
                                                    base_font_size: Number(e.target.value) || 16,
                                                }))
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>SEO — العنوان</Label>
                                        <Input
                                            value={seoTitle}
                                            onChange={(e) => setSeoTitle(e.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>SEO — الوصف</Label>
                                        <Textarea
                                            rows={3}
                                            value={seoDescription}
                                            onChange={(e) => setSeoDescription(e.target.value)}
                                        />
                                    </div>
                                    {!page.is_system ? (
                                        <div className="rounded-xl border border-destructive/30 bg-destructive/5 p-3">
                                            <p className="text-xs font-medium text-destructive">
                                                منطقة خطر
                                            </p>
                                            <p className="mt-1 text-[11px] text-muted-foreground">
                                                حذف الصفحة المخصصة نهائي ولا يمكن استرجاعها.
                                            </p>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                className="mt-3"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `حذف صفحة «${title}» نهائياً؟`,
                                                        )
                                                    ) {
                                                        router.delete(
                                                            `/admin/pages/${page.id}`,
                                                        );
                                                    }
                                                }}
                                            >
                                                <Trash2 className="size-3.5" />
                                                حذف هذه الصفحة
                                            </Button>
                                        </div>
                                    ) : null}
                                </div>
                            )}
                        </div>
                    </aside>

                    {/* Center preview / empty picker */}
                    <div className="min-h-0 overflow-y-auto rounded-xl border bg-muted/30">
                        <div className="sticky top-0 z-10 flex items-center justify-between border-b bg-background/90 px-3 py-2 text-xs text-muted-foreground backdrop-blur">
                            <span>معاينة حية</span>
                            <span>{sections.filter((s) => s.enabled).length} بلوك ظاهر</span>
                        </div>

                        {sections.length === 0 ? (
                            <div className="p-5">
                                <div className="mx-auto mb-5 max-w-xl text-center">
                                    <Text className="mx-auto size-10 text-amber-800/70" />
                                    <h2 className="mt-3 text-xl font-bold">ابنِ الصفحة بالبلوكات</h2>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        اختر بلوكاً من القائمة — كل بلوك له عدة تصاميم، وبعد الإضافة
                                        عبّئ النصوص مباشرة من اليمين.
                                    </p>
                                </div>
                                <BlockLibraryGrid catalog={catalog} onAdd={addSection} />
                            </div>
                        ) : (
                            <div className="p-4">
                                <StorefrontPageRenderer
                                    theme={theme}
                                    sections={sections}
                                    categories={categories}
                                    featuredProducts={featuredProducts}
                                    business={business}
                                />
                                <div className="mt-6 flex justify-center pb-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="border-dashed"
                                        onClick={() => setLeftTab('blocks')}
                                    >
                                        <Plus className="size-4" />
                                        إضافة بلوك آخر
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right: fill texts */}
                    <aside className="flex min-h-0 flex-col overflow-hidden rounded-xl border bg-card">
                        <div
                            ref={propsTopRef}
                            className="flex items-center gap-2 border-b px-3 py-2.5 text-sm font-semibold"
                        >
                            <Settings2 className="size-4" />
                            {selected ? 'تعبئة النصوص والتصميم' : 'خصائص البلوك'}
                        </div>
                        <div className="min-h-0 flex-1 overflow-y-auto p-3">
                            {!selected ? (
                                <div className="flex h-full flex-col items-center justify-center gap-3 px-2 py-10 text-center">
                                    <MousePointerClick className="size-8 text-muted-foreground/40" />
                                    <p className="text-sm font-medium">لا يوجد بلوك محدد</p>
                                    <p className="text-xs text-muted-foreground">
                                        أضف بلوكاً أو اختر بلوكاً من قائمة الصفحة لتعديل نصوصه
                                        وتصميمه
                                    </p>
                                    <Button
                                        type="button"
                                        size="sm"
                                        onClick={() => setLeftTab('blocks')}
                                    >
                                        <Plus className="size-4" />
                                        إضافة بلوك
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-5">
                                    <div className="rounded-xl bg-muted/40 px-3 py-2.5">
                                        <p className="text-xs text-muted-foreground">البلوك الحالي</p>
                                        <p className="font-semibold">
                                            {catalogByType.get(selected.type)?.label ??
                                                selected.type}
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-muted-foreground">
                                            {catalogByType.get(selected.type)?.description}
                                        </p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>اختر التصميم</Label>
                                        <div className="grid gap-2">
                                            {(catalogByType.get(selected.type)?.variants ?? []).map(
                                                (v) => (
                                                    <button
                                                        key={v.id}
                                                        type="button"
                                                        className={cn(
                                                            'rounded-xl border px-3 py-2.5 text-start transition',
                                                            selected.variant === v.id
                                                                ? 'border-primary bg-primary/10 ring-1 ring-primary/20'
                                                                : 'hover:bg-muted/60',
                                                        )}
                                                        onClick={() =>
                                                            updateSelected({ variant: v.id })
                                                        }
                                                    >
                                                        <span className="block text-sm font-medium">
                                                            {v.label}
                                                        </span>
                                                        {v.hint ? (
                                                            <span className="mt-0.5 block text-[11px] text-muted-foreground">
                                                                {v.hint}
                                                            </span>
                                                        ) : null}
                                                    </button>
                                                ),
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 rounded-lg border px-3 py-2">
                                        <Checkbox
                                            id="sec-enabled"
                                            checked={selected.enabled}
                                            onCheckedChange={(v) =>
                                                updateSelected({ enabled: v === true })
                                            }
                                        />
                                        <Label htmlFor="sec-enabled">إظهار البلوك في الصفحة</Label>
                                    </div>

                                    <div className="space-y-3 border-t pt-4">
                                        <p className="text-xs font-semibold tracking-wide text-muted-foreground">
                                            النصوص والمحتوى
                                        </p>

                                        {selected.type === 'trust' ? (
                                            <TrustItemsEditor
                                                items={trustItems}
                                                onChange={(items) => updateProp('items', items)}
                                            />
                                        ) : null}

                                        {propFields.map((field) => {
                                            const val = selected.props[field.key];

                                            if (field.kind === 'toggle') {
                                                return (
                                                    <div
                                                        key={field.key}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <Checkbox
                                                            id={`prop-${field.key}`}
                                                            checked={Boolean(val)}
                                                            onCheckedChange={(v) =>
                                                                updateProp(field.key, v === true)
                                                            }
                                                        />
                                                        <Label htmlFor={`prop-${field.key}`}>
                                                            {field.label}
                                                        </Label>
                                                    </div>
                                                );
                                            }

                                            if (field.kind === 'number') {
                                                return (
                                                    <div key={field.key} className="grid gap-1.5">
                                                        <Label>{field.label}</Label>
                                                        <Input
                                                            type="number"
                                                            value={Number(val ?? 0)}
                                                            onChange={(e) =>
                                                                updateProp(
                                                                    field.key,
                                                                    Number(e.target.value) || 0,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                );
                                            }

                                            if (field.kind === 'textarea') {
                                                return (
                                                    <div key={field.key} className="grid gap-1.5">
                                                        <Label>{field.label}</Label>
                                                        <Textarea
                                                            rows={4}
                                                            value={String(val ?? '')}
                                                            onChange={(e) =>
                                                                updateProp(
                                                                    field.key,
                                                                    e.target.value,
                                                                )
                                                            }
                                                            placeholder={`اكتب ${field.label} هنا…`}
                                                        />
                                                    </div>
                                                );
                                            }

                                            return (
                                                <div key={field.key} className="grid gap-1.5">
                                                    <Label>{field.label}</Label>
                                                    <Input
                                                        value={String(val ?? '')}
                                                        onChange={(e) =>
                                                            updateProp(field.key, e.target.value)
                                                        }
                                                        placeholder={`اكتب ${field.label} هنا…`}
                                                    />
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
