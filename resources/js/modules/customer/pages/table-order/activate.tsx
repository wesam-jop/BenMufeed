import { Head, useForm } from '@inertiajs/react';
import { Camera, CheckCircle2, Coffee, KeyRound, ScanLine, Users, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useI18n } from '@/hooks/use-i18n';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type TableOption = {
    id: number;
    name: string;
    zone?: string | null;
    status: string;
    capacity: number;
    is_available?: boolean;
    availability_label?: string;
};

type Props = {
    business: { name: string; logo?: string | null; tagline?: string | null };
    tables: TableOption[];
};

declare class BarcodeDetector {
    constructor(options?: { formats?: string[] });
    detect(source: ImageBitmapSource): Promise<Array<{ rawValue: string }>>;
}

/** Extract encrypted token `t` from QR payload (never the plain code). */
function extractAccessToken(raw: string): string | null {
    const value = raw.trim();

    try {
        const url = new URL(value);
        const token = url.searchParams.get('t');
        if (token) {
            return token.trim();
        }
    } catch {
        // not a full URL
    }

    const match = value.match(/[?&#]t=([^&#\s]+)/i);
    if (match?.[1]) {
        return decodeURIComponent(match[1]).trim();
    }

    return null;
}

export default function TableOrderActivate({ business, tables }: Props) {
    const { t } = useI18n();

    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const scanLoopRef = useRef<number | null>(null);

    const [scanning, setScanning] = useState(false);
    const [scanError, setScanError] = useState<string | null>(null);
    const [tokenReady, setTokenReady] = useState(false);
    const [showManual, setShowManual] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        table_id: '' as string | number,
        access_token: '',
        access_code: '',
        guest_count: '' as string | number,
    });

    // First scan may open /table-order?t=... — strip secrets from the address bar.
    useEffect(() => {
        const url = new URL(window.location.href);
        let changed = false;
        for (const key of ['t', 'code']) {
            if (url.searchParams.has(key)) {
                url.searchParams.delete(key);
                changed = true;
            }
        }
        if (changed) {
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }
    }, []);

    const stopScanner = () => {
        if (scanLoopRef.current !== null) {
            window.clearInterval(scanLoopRef.current);
            scanLoopRef.current = null;
        }
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        setScanning(false);
    };

    useEffect(() => () => stopScanner(), []);

    const applyScannedToken = (token: string) => {
        setData('access_token', token);
        setData('access_code', '');
        setTokenReady(true);
        stopScanner();
        toast.success(t('toast.barcode_read'));
    };

    const startScanner = async () => {
        if (!data.table_id) {
            toast.error(t('toast.select_table_first'));
            return;
        }

        setScanError(null);

        if (!('BarcodeDetector' in window)) {
            setScanError(
                'جهازك لا يدعم مسح الباركود من المتصفح. استخدم جهاز آخر أو أدخل الرمز يدوياً من الكاشير.',
            );
            setShowManual(true);
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });
            streamRef.current = stream;
            setScanning(true);

            requestAnimationFrame(() => {
                if (videoRef.current) {
                    videoRef.current.srcObject = stream;
                    void videoRef.current.play();
                }
            });

            const detector = new BarcodeDetector({ formats: ['qr_code'] });

            scanLoopRef.current = window.setInterval(async () => {
                const video = videoRef.current;
                if (!video || video.readyState < 2) {
                    return;
                }

                try {
                    const codes = await detector.detect(video);
                    const raw = codes[0]?.rawValue;
                    if (!raw) {
                        return;
                    }

                    const token = extractAccessToken(raw);
                    if (token) {
                        applyScannedToken(token);
                    }
                } catch {
                    // keep scanning
                }
            }, 500);
        } catch {
            setScanError('تعذر فتح الكاميرا. اسمح بالوصول للكاميرا أو أدخل الرمز يدوياً.');
            setShowManual(true);
            stopScanner();
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/table-order/activate');
    };

    const canSubmit = Boolean(data.table_id && (data.access_token || data.access_code));

    return (
        <div dir="rtl" lang="ar" className="min-h-screen bg-[#faf8f5]">
            <Head title={`طلب الطاولة — ${business.name}`} />

            <section className="relative overflow-hidden bg-stone-950 text-white">
                <div className="storefront-hero-glow absolute inset-0" />
                <div className="relative mx-auto max-w-lg px-4 py-12 text-center">
                    {business.logo ? (
                        <img src={business.logo} alt="" className="mx-auto mb-4 size-14 rounded-xl object-cover" />
                    ) : (
                        <Coffee className="mx-auto mb-4 size-10 text-amber-300" />
                    )}
                    <h1 className="text-3xl font-bold">{business.name}</h1>
                    <p className="mt-3 text-stone-300">اختر طاولتك ثم صوّر الباركود مرة ثانية للتفعيل</p>
                    <p className="mt-2 text-sm text-amber-200/80">مدة الجلسة: ساعة واحدة</p>
                </div>
            </section>

            <form onSubmit={submit} className="mx-auto max-w-lg px-4 py-8">
                <div className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-stone-200/80">
                    <Label className="mb-3 block text-base font-semibold text-stone-900">1) اختر الطاولة</Label>
                    <div className="grid max-h-64 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-3">
                        {tables.map((table) => {
                            const selected = String(data.table_id) === String(table.id);
                            const available = table.is_available !== false;

                            return (
                                <button
                                    key={table.id}
                                    type="button"
                                    disabled={!available}
                                    onClick={() => {
                                        if (!available) {
                                            toast.error(t('toast.table_already_open'));
                                            return;
                                        }
                                        setData('table_id', table.id);
                                    }}
                                    className={cn(
                                        'rounded-2xl border px-3 py-4 text-center transition-all',
                                        !available &&
                                            'cursor-not-allowed border-stone-200 bg-stone-100 opacity-60',
                                        available &&
                                            selected &&
                                            'border-stone-900 bg-stone-900 text-white shadow-md',
                                        available &&
                                            !selected &&
                                            'border-stone-200 bg-stone-50 hover:border-amber-300',
                                    )}
                                >
                                    <p className="font-bold">{table.name}</p>
                                    {table.zone && (
                                        <p
                                            className={cn(
                                                'mt-1 text-xs',
                                                selected && available ? 'text-stone-300' : 'text-stone-500',
                                            )}
                                        >
                                            {table.zone}
                                        </p>
                                    )}
                                    <p
                                        className={cn(
                                            'mt-2 text-[11px] font-medium',
                                            available
                                                ? selected
                                                    ? 'text-amber-200'
                                                    : 'text-emerald-700'
                                                : 'text-rose-600',
                                        )}
                                    >
                                        {table.availability_label ?? (available ? 'متاحة' : 'مشغولة')}
                                    </p>
                                </button>
                            );
                        })}
                    </div>
                    <InputError message={errors.table_id} className="mt-2" />

                    <div className="mt-8 border-t border-stone-100 pt-6">
                        <Label className="mb-3 flex items-center gap-2 text-base font-semibold text-stone-900">
                            <ScanLine className="size-4" />
                            2) صوّر الباركود للتفعيل
                        </Label>
                        <p className="mb-4 text-sm text-stone-500">
                            الرمز داخل الباركود مشفّر — لازم تصوّره من هنا، ما بينفع تنسخه من الرابط.
                        </p>

                        {tokenReady && (
                            <div className="mb-4 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                                <CheckCircle2 className="size-5 shrink-0" />
                                تم قراءة الباركود بنجاح — يمكنك تفعيل الجلسة
                            </div>
                        )}

                        {!scanning ? (
                            <Button
                                type="button"
                                size="lg"
                                variant="outline"
                                className="h-12 w-full border-amber-200 bg-amber-50 text-amber-950 hover:bg-amber-100"
                                onClick={() => void startScanner()}
                                disabled={!data.table_id}
                            >
                                <Camera className="size-4" />
                                {tokenReady ? 'إعادة تصوير الباركود' : 'فتح الكاميرا وتصوير الباركود'}
                            </Button>
                        ) : (
                            <div className="space-y-3">
                                <div className="relative overflow-hidden rounded-2xl bg-stone-900">
                                    <video
                                        ref={videoRef}
                                        className="aspect-[3/4] w-full object-cover"
                                        muted
                                        playsInline
                                    />
                                    <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                        <div className="h-40 w-40 rounded-2xl border-2 border-amber-300/90 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]" />
                                    </div>
                                </div>
                                <Button type="button" variant="outline" className="w-full" onClick={stopScanner}>
                                    <X className="size-4" />
                                    إيقاف الكاميرا
                                </Button>
                            </div>
                        )}

                        {scanError && <p className="mt-2 text-sm text-destructive">{scanError}</p>}
                        <InputError message={errors.access_token} className="mt-2" />

                        <button
                            type="button"
                            className="mt-4 text-sm text-stone-500 underline-offset-2 hover:text-stone-800 hover:underline"
                            onClick={() => setShowManual((v) => !v)}
                        >
                            {showManual ? 'إخفاء الإدخال اليدوي' : 'إدخال الرمز يدوياً (للكاشير)'}
                        </button>

                        {showManual && (
                            <div className="mt-3 grid gap-2">
                                <Label htmlFor="access_code" className="flex items-center gap-2 text-sm text-stone-600">
                                    <KeyRound className="size-4" />
                                    رمز التفعيل اليدوي
                                </Label>
                                <Input
                                    id="access_code"
                                    value={data.access_code}
                                    onChange={(e) => {
                                        setData('access_code', e.target.value);
                                        if (e.target.value) {
                                            setData('access_token', '');
                                            setTokenReady(false);
                                        }
                                    }}
                                    placeholder="يُعطى من الكاشير عند الحاجة"
                                    className="h-12 border-stone-200 text-center text-lg tracking-[0.3em]"
                                    dir="ltr"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                />
                                <InputError message={errors.access_code} />
                            </div>
                        )}
                    </div>

                    <div className="mt-4 grid gap-2">
                        <Label htmlFor="guest_count" className="flex items-center gap-2">
                            <Users className="size-4" />
                            عدد الضيوف (اختياري)
                        </Label>
                        <Input
                            id="guest_count"
                            type="number"
                            min={1}
                            max={99}
                            value={data.guest_count}
                            onChange={(e) => setData('guest_count', e.target.value)}
                            className="h-11 border-stone-200"
                        />
                        <InputError message={errors.guest_count} />
                    </div>

                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing || !canSubmit}
                        className="mt-6 h-12 w-full bg-stone-900 hover:bg-stone-800"
                    >
                        {processing && <Spinner />}
                        تفعيل الجلسة وبدء الطلب
                    </Button>
                </div>
            </form>
        </div>
    );
}
