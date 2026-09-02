import QRCode from 'qrcode';
import { router } from '@inertiajs/react';
import { Download, ImagePlus, Loader2, Palette, RefreshCw, Upload } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type PaletteColors = {
    primary: string;
    secondary: string;
    accent: string;
    dark: string;
    light: string;
};

type Props = {
    url: string;
    businessName: string;
    defaultLogoUrl?: string | null;
    hasCustomQrLogo?: boolean;
    className?: string;
};

const DEFAULT_PALETTE: PaletteColors = {
    primary: '#92400e',
    secondary: '#78350f',
    accent: '#f59e0b',
    dark: '#1c1917',
    light: '#fffbeb',
};

function rgbToHex(r: number, g: number, b: number): string {
    return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

function relativeLuminance(r: number, g: number, b: number): number {
    const [rs, gs, bs] = [r, g, b].map((c) => {
        const s = c / 255;
        return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

async function extractPaletteFromImage(src: string): Promise<PaletteColors> {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const size = 64;
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                resolve(DEFAULT_PALETTE);
                return;
            }

            ctx.drawImage(img, 0, 0, size, size);
            const { data } = ctx.getImageData(0, 0, size, size);
            const buckets = new Map<string, { count: number; r: number; g: number; b: number }>();

            for (let i = 0; i < data.length; i += 4) {
                const a = data[i + 3];
                if (a < 128) {
                    continue;
                }
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                // Skip near-white / near-black noise
                const lum = relativeLuminance(r, g, b);
                if (lum > 0.92 || lum < 0.05) {
                    continue;
                }
                const key = `${Math.round(r / 24)}_${Math.round(g / 24)}_${Math.round(b / 24)}`;
                const existing = buckets.get(key);
                if (existing) {
                    existing.count += 1;
                    existing.r += r;
                    existing.g += g;
                    existing.b += b;
                } else {
                    buckets.set(key, { count: 1, r, g, b });
                }
            }

            const sorted = [...buckets.values()]
                .map((bucket) => ({
                    count: bucket.count,
                    r: Math.round(bucket.r / bucket.count),
                    g: Math.round(bucket.g / bucket.count),
                    b: Math.round(bucket.b / bucket.count),
                }))
                .sort((a, b) => b.count - a.count);

            if (sorted.length === 0) {
                resolve(DEFAULT_PALETTE);
                return;
            }

            const primary = sorted[0];
            const secondary = sorted[1] ?? primary;
            const accent = sorted.find((c) => relativeLuminance(c.r, c.g, c.b) > 0.35) ?? secondary;

            const darkEnough =
                relativeLuminance(primary.r, primary.g, primary.b) < 0.35
                    ? primary
                    : { r: Math.max(0, primary.r - 80), g: Math.max(0, primary.g - 80), b: Math.max(0, primary.b - 80) };

            resolve({
                primary: rgbToHex(primary.r, primary.g, primary.b),
                secondary: rgbToHex(secondary.r, secondary.g, secondary.b),
                accent: rgbToHex(accent.r, accent.g, accent.b),
                dark: rgbToHex(darkEnough.r, darkEnough.g, darkEnough.b),
                light: '#fffdf8',
            });
        };
        img.onerror = () => resolve(DEFAULT_PALETTE);
        img.src = src;
    });
}

function roundRect(
    ctx: CanvasRenderingContext2D,
    x: number,
    y: number,
    w: number,
    h: number,
    r: number,
): void {
    const radius = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + w, y, x + w, y + h, radius);
    ctx.arcTo(x + w, y + h, x, y + h, radius);
    ctx.arcTo(x, y + h, x, y, radius);
    ctx.arcTo(x, y, x + w, y, radius);
    ctx.closePath();
}

export default function BrandedTableQrCard({
    url,
    businessName,
    defaultLogoUrl = null,
    hasCustomQrLogo = false,
    className,
}: Props) {
    const { t } = useI18n();
    const previewRef = useRef<HTMLCanvasElement>(null);
    const downloadRef = useRef<HTMLCanvasElement>(null);
    const fileRef = useRef<HTMLInputElement>(null);

    const [logoUrl, setLogoUrl] = useState<string | null>(defaultLogoUrl);
    const [palette, setPalette] = useState<PaletteColors>(DEFAULT_PALETTE);
    const [rendering, setRendering] = useState(false);
    const [uploading, setUploading] = useState(false);

    useEffect(() => {
        setLogoUrl(defaultLogoUrl);
    }, [defaultLogoUrl]);

    useEffect(() => {
        let cancelled = false;

        const run = async () => {
            if (!logoUrl) {
                setPalette(DEFAULT_PALETTE);
                return;
            }
            const next = await extractPaletteFromImage(logoUrl);
            if (!cancelled) {
                setPalette(next);
            }
        };

        void run();

        return () => {
            cancelled = true;
        };
    }, [logoUrl]);

    const renderCanvases = useCallback(async () => {
        const preview = previewRef.current;
        const download = downloadRef.current;
        if (!preview || !download) {
            return;
        }

        setRendering(true);

        try {
            const qrSize = 512;
            const qrCanvas = document.createElement('canvas');
            await QRCode.toCanvas(qrCanvas, url, {
                errorCorrectionLevel: 'H',
                margin: 1,
                width: qrSize,
                color: {
                    dark: palette.dark,
                    light: '#ffffff',
                },
            });

            // Embed logo in QR center
            if (logoUrl) {
                await new Promise<void>((resolve) => {
                    const logo = new Image();
                    logo.crossOrigin = 'anonymous';
                    logo.onload = () => {
                        const ctx = qrCanvas.getContext('2d');
                        if (!ctx) {
                            resolve();
                            return;
                        }
                        const logoBox = qrSize * 0.22;
                        const x = (qrSize - logoBox) / 2;
                        const y = (qrSize - logoBox) / 2;
                        const pad = logoBox * 0.12;

                        ctx.fillStyle = '#ffffff';
                        roundRect(ctx, x - pad / 2, y - pad / 2, logoBox + pad, logoBox + pad, 18);
                        ctx.fill();

                        ctx.save();
                        roundRect(ctx, x, y, logoBox, logoBox, 14);
                        ctx.clip();
                        ctx.drawImage(logo, x, y, logoBox, logoBox);
                        ctx.restore();
                        resolve();
                    };
                    logo.onerror = () => resolve();
                    logo.src = logoUrl;
                });
            }

            // Preview (simple QR)
            preview.width = 280;
            preview.height = 280;
            const pctx = preview.getContext('2d');
            if (pctx) {
                pctx.fillStyle = '#ffffff';
                pctx.fillRect(0, 0, 280, 280);
                pctx.drawImage(qrCanvas, 16, 16, 248, 248);
            }

            // Download card (branded poster)
            const W = 900;
            const H = 1200;
            download.width = W;
            download.height = H;
            const ctx = download.getContext('2d');
            if (!ctx) {
                return;
            }

            const gradient = ctx.createLinearGradient(0, 0, W, H);
            gradient.addColorStop(0, palette.secondary);
            gradient.addColorStop(0.45, palette.primary);
            gradient.addColorStop(1, palette.dark);
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, W, H);

            // Soft glow orb
            const orb = ctx.createRadialGradient(W * 0.8, H * 0.15, 20, W * 0.8, H * 0.15, 280);
            orb.addColorStop(0, `${palette.accent}66`);
            orb.addColorStop(1, 'transparent');
            ctx.fillStyle = orb;
            ctx.fillRect(0, 0, W, H);

            // Card panel
            const panelX = 70;
            const panelY = 120;
            const panelW = W - 140;
            const panelH = H - 240;
            ctx.fillStyle = palette.light;
            roundRect(ctx, panelX, panelY, panelW, panelH, 40);
            ctx.fill();

            // Title
            ctx.fillStyle = palette.dark;
            ctx.textAlign = 'center';
            ctx.font = '700 48px Cairo, Tajawal, sans-serif';
            ctx.fillText(businessName || 'Kasher Cafe', W / 2, panelY + 90);

            ctx.fillStyle = `${palette.primary}`;
            ctx.font = '500 26px Cairo, Tajawal, sans-serif';
            ctx.fillText('امسح للطلب من الطاولة', W / 2, panelY + 140);

            // QR white frame
            const qrDraw = 520;
            const qrX = (W - qrDraw) / 2;
            const qrY = panelY + 190;
            ctx.fillStyle = '#ffffff';
            roundRect(ctx, qrX - 24, qrY - 24, qrDraw + 48, qrDraw + 48, 28);
            ctx.fill();
            ctx.drawImage(qrCanvas, qrX, qrY, qrDraw, qrDraw);

            // Footer tips
            ctx.fillStyle = palette.secondary;
            ctx.font = '500 22px Cairo, Tajawal, sans-serif';
            ctx.fillText('١) صوّر الباركود واختر طاولتك', W / 2, qrY + qrDraw + 80);
            ctx.fillText('٢) صوّره مرة ثانية للتفعيل', W / 2, qrY + qrDraw + 115);

            ctx.fillStyle = `${palette.dark}99`;
            ctx.font = '400 18px Cairo, Tajawal, sans-serif';
            ctx.fillText('باركود موحد لكل الطاولات', W / 2, panelY + panelH - 40);
        } catch {
            toast.error(t('toast.qr_generate_failed'));
        } finally {
            setRendering(false);
        }
    }, [url, logoUrl, palette, businessName]);

    useEffect(() => {
        void renderCanvases();
    }, [renderCanvases]);

    const onUpload = (file: File | null) => {
        if (!file) {
            return;
        }
        if (!file.type.startsWith('image/')) {
            toast.error(t('toast.invalid_image'));
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        setLogoUrl(objectUrl);
        setUploading(true);

        router.post(
            '/admin/restaurant-tables/qr-logo',
            { logo: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(t('toast.logo_saved_colors'));
                },
                onError: () => {
                    toast.error(t('toast.logo_save_failed'));
                    setLogoUrl(defaultLogoUrl);
                },
                onFinish: () => {
                    setUploading(false);
                    URL.revokeObjectURL(objectUrl);
                    if (fileRef.current) {
                        fileRef.current.value = '';
                    }
                },
            },
        );
    };

    const removeCustomLogo = () => {
        setUploading(true);
        router.delete('/admin/restaurant-tables/qr-logo', {
            preserveScroll: true,
            onSuccess: () => toast.success(t('toast.logo_deleted')),
            onError: () => toast.error(t('toast.logo_delete_failed')),
            onFinish: () => setUploading(false),
        });
    };

    const downloadImage = () => {
        const canvas = downloadRef.current;
        if (!canvas) {
            return;
        }
        const link = document.createElement('a');
        link.download = `${(businessName || 'qr').replace(/\s+/g, '-')}-table-qr.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        toast.success(t('toast.qr_downloaded'));
    };

    return (
        <div className={cn('grid gap-5 lg:grid-cols-[280px_1fr]', className)}>
            <div className="space-y-3">
                <canvas
                    ref={previewRef}
                    className="mx-auto size-[280px] rounded-2xl border bg-white shadow-sm"
                />
                <canvas ref={downloadRef} className="hidden" />
                <div className="flex flex-wrap justify-center gap-2">
                    {Object.entries({
                        أساسي: palette.primary,
                        ثانوي: palette.secondary,
                        تمييز: palette.accent,
                    }).map(([label, color]) => (
                        <div
                            key={label}
                            className="flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs"
                        >
                            <span
                                className="size-3 rounded-full ring-1 ring-black/10"
                                style={{ backgroundColor: color }}
                            />
                            {label}
                        </div>
                    ))}
                </div>
            </div>

            <div className="space-y-4">
                <div>
                    <Label className="mb-2 flex items-center gap-2">
                        <ImagePlus className="size-4" />
                        شعار الباركود
                    </Label>
                    <div className="flex flex-wrap items-center gap-3">
                        {logoUrl ? (
                            <img
                                src={logoUrl}
                                alt=""
                                className="size-16 rounded-xl border object-cover"
                            />
                        ) : (
                            <div className="flex size-16 items-center justify-center rounded-xl border border-dashed text-muted-foreground">
                                <Upload className="size-5" />
                            </div>
                        )}
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={uploading}
                                onClick={() => fileRef.current?.click()}
                            >
                                {uploading ? (
                                    <Loader2 className="size-4 animate-spin" />
                                ) : (
                                    <Upload className="size-4" />
                                )}
                                {uploading ? 'جاري الحفظ...' : 'رفع شعار'}
                            </Button>
                            {hasCustomQrLogo && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    disabled={uploading}
                                    onClick={removeCustomLogo}
                                >
                                    <RefreshCw className="size-4" />
                                    حذف المخصص
                                </Button>
                            )}
                        </div>
                        <input
                            ref={fileRef}
                            type="file"
                            accept="image/*"
                            className="hidden"
                            onChange={(e) => onUpload(e.target.files?.[0] ?? null)}
                        />
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                        الصورة تُحفظ على السيرفر وتُستخدم تلقائياً في كل مرة. نقرأ ألوانها ونطبّقها على الباركود.
                    </p>
                </div>

                <div className="rounded-xl border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                    <p className="mb-1 flex items-center gap-2 font-medium text-foreground">
                        <Palette className="size-4" />
                        التصميم النهائي للطباعة
                    </p>
                    بطاقة عمودية أنيقة بالاسم + الباركود الملون والشعار في الوسط — جاهزة للطباعة أو المشاركة.
                </div>

                <Button
                    type="button"
                    size="lg"
                    className="h-12 w-full bg-stone-900 hover:bg-stone-800 sm:w-auto"
                    onClick={downloadImage}
                    disabled={rendering}
                >
                    <Download className="size-4" />
                    تحميل صورة الباركود
                </Button>
            </div>
        </div>
    );
}
