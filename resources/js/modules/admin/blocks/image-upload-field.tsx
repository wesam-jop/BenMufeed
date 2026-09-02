import { ImagePlus, Trash2, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

type Props = {
    value: File | null;
    previewUrl?: string | null;
    onChange: (file: File | null) => void;
    onClearExisting?: () => void;
    title?: string;
    hint?: string;
    aspectClass?: string;
    objectFit?: 'cover' | 'contain';
    className?: string;
};

export default function ImageUploadField({
    value,
    previewUrl,
    onChange,
    onClearExisting,
    title,
    hint,
    aspectClass = 'aspect-[16/10]',
    objectFit = 'cover',
    className,
}: Props) {
    const { t } = useI18n();
    const resolvedTitle = title ?? t('common.image_drop');
    const resolvedHint = hint ?? t('common.image_or_click');
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [cleared, setCleared] = useState(false);
    const [objectUrl, setObjectUrl] = useState<string | null>(null);

    useEffect(() => {
        setCleared(false);
    }, [previewUrl]);

    useEffect(() => {
        if (!value) {
            setObjectUrl(null);
            return;
        }

        const url = URL.createObjectURL(value);
        setObjectUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [value]);

    const preview = cleared ? null : (objectUrl ?? previewUrl ?? null);

    const handleFile = (file: File | null) => {
        if (!file) {
            onChange(null);
            return;
        }

        if (!file.type.startsWith('image/')) {
            return;
        }

        onChange(file);
        setCleared(false);
    };

    const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(false);
        handleFile(event.dataTransfer.files?.[0] ?? null);
    };

    const clear = () => {
        onChange(null);
        setCleared(true);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
        if (!value && previewUrl) {
            onClearExisting?.();
        }
    };

    return (
        <div className={cn('grid gap-3', className)}>
            <input
                ref={inputRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={(e) => handleFile(e.target.files?.[0] ?? null)}
            />

            {preview ? (
                <div className="group relative overflow-hidden rounded-xl border bg-muted/20">
                    <div className={cn(aspectClass, 'w-full bg-stone-100')}>
                        <img
                            src={preview}
                            alt=""
                            className={cn(
                                'size-full',
                                objectFit === 'contain' ? 'object-contain p-3' : 'object-cover',
                            )}
                        />
                    </div>
                    <div className="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-black/70 to-transparent p-3">
                        <span className="truncate text-xs text-white/90">
                            {value?.name ?? t('common.image_current')}
                        </span>
                        <div className="flex shrink-0 gap-1.5">
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                className="h-8"
                                onClick={() => inputRef.current?.click()}
                            >
                                <Upload className="size-3.5" />
                                {t('common.change')}
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="destructive"
                                className="h-8"
                                onClick={clear}
                            >
                                <Trash2 className="size-3.5" />
                                {t('common.delete')}
                            </Button>
                        </div>
                    </div>
                </div>
            ) : (
                <div
                    role="button"
                    tabIndex={0}
                    onClick={() => inputRef.current?.click()}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            inputRef.current?.click();
                        }
                    }}
                    onDragOver={(e) => {
                        e.preventDefault();
                        setIsDragging(true);
                    }}
                    onDragLeave={() => setIsDragging(false)}
                    onDrop={handleDrop}
                    className={cn(
                        'flex min-h-[132px] cursor-pointer items-center gap-4 rounded-xl border border-dashed px-4 py-4 text-start transition-colors',
                        isDragging
                            ? 'border-primary bg-primary/5'
                            : 'border-border bg-muted/20 hover:border-primary/40 hover:bg-muted/40',
                    )}
                >
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-background ring-1 ring-border">
                        <ImagePlus className="size-5 text-muted-foreground" />
                    </div>
                    <div className="min-w-0 flex-1 space-y-0.5">
                        <p className="truncate text-sm font-medium">{resolvedTitle}</p>
                        <p className="text-xs text-muted-foreground">{resolvedHint}</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" className="pointer-events-none shrink-0">
                        <Upload className="size-3.5" />
                        {t('common.choose')}
                    </Button>
                </div>
            )}
        </div>
    );
}
