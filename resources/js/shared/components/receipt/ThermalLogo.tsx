import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

type Props = {
    src: string;
    alt?: string;
    className?: string;
    size?: number;
};

function luminance(r: number, g: number, b: number): number {
    return 0.299 * r + 0.587 * g + 0.114 * b;
}

/**
 * Thermal-safe logo: clean white paper, tight crop, high-contrast B/W
 * without dither noise around circular marks.
 */
export default function ThermalLogo({ src, alt = '', className, size = 96 }: Props) {
    const [bwSrc, setBwSrc] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        const img = new Image();
        img.crossOrigin = 'anonymous';

        img.onload = () => {
            try {
                const nw = Math.max(1, img.naturalWidth || img.width);
                const nh = Math.max(1, img.naturalHeight || img.height);
                const maxSide = 320;
                const scale = Math.min(1, maxSide / Math.max(nw, nh));
                const w = Math.max(1, Math.round(nw * scale));
                const h = Math.max(1, Math.round(nh * scale));

                const srcCanvas = document.createElement('canvas');
                srcCanvas.width = w;
                srcCanvas.height = h;
                const sctx = srcCanvas.getContext('2d', { willReadFrequently: true });
                if (!sctx) {
                    if (!cancelled) setBwSrc(src);
                    return;
                }

                sctx.clearRect(0, 0, w, h);
                sctx.drawImage(img, 0, 0, w, h);
                const srcData = sctx.getImageData(0, 0, w, h);
                const px = srcData.data;

                // 1) Mark "paper" pixels: transparent OR edge-connected near-white / near-bg
                const isPaper = new Uint8Array(w * h);
                for (let i = 0; i < w * h; i++) {
                    const o = i * 4;
                    const a = px[o + 3];
                    if (a < 24) {
                        isPaper[i] = 1;
                        continue;
                    }
                    const lum = luminance(px[o], px[o + 1], px[o + 2]);
                    // Soft cream / near-white branding backgrounds
                    if (lum >= 235 && a > 200) {
                        isPaper[i] = 1;
                    }
                }

                // Flood-fill paper from all edges so circular logo keeps clean outside
                const queue: number[] = [];
                const pushIf = (x: number, y: number) => {
                    if (x < 0 || y < 0 || x >= w || y >= h) return;
                    const i = y * w + x;
                    if (isPaper[i] === 2) return;
                    const o = i * 4;
                    const a = px[o + 3];
                    const lum = a < 24 ? 255 : luminance(px[o], px[o + 1], px[o + 2]);
                    // Treat light edge-connected pixels as paper (kills square noise halo)
                    if (a < 24 || lum >= 200) {
                        isPaper[i] = 2;
                        queue.push(i);
                    }
                };

                for (let x = 0; x < w; x++) {
                    pushIf(x, 0);
                    pushIf(x, h - 1);
                }
                for (let y = 0; y < h; y++) {
                    pushIf(0, y);
                    pushIf(w - 1, y);
                }

                while (queue.length) {
                    const i = queue.pop()!;
                    const x = i % w;
                    const y = (i / w) | 0;
                    pushIf(x + 1, y);
                    pushIf(x - 1, y);
                    pushIf(x, y + 1);
                    pushIf(x, y - 1);
                }

                for (let i = 0; i < w * h; i++) {
                    if (isPaper[i] === 2) isPaper[i] = 1;
                    else if (isPaper[i] !== 1) isPaper[i] = 0;
                }

                // 2) Bounding box of logo content only
                let minX = w;
                let minY = h;
                let maxX = -1;
                let maxY = -1;
                for (let y = 0; y < h; y++) {
                    for (let x = 0; x < w; x++) {
                        if (isPaper[y * w + x]) continue;
                        if (x < minX) minX = x;
                        if (y < minY) minY = y;
                        if (x > maxX) maxX = x;
                        if (y > maxY) maxY = y;
                    }
                }

                if (maxX < 0) {
                    if (!cancelled) setBwSrc(src);
                    return;
                }

                const pad = 4;
                minX = Math.max(0, minX - pad);
                minY = Math.max(0, minY - pad);
                maxX = Math.min(w - 1, maxX + pad);
                maxY = Math.min(h - 1, maxY + pad);
                const cw = maxX - minX + 1;
                const ch = maxY - minY + 1;

                // 3) Build clean grayscale of content, pure white paper
                const gray = new Float32Array(cw * ch);
                let contentSum = 0;
                let contentCount = 0;

                for (let y = 0; y < ch; y++) {
                    for (let x = 0; x < cw; x++) {
                        const sx = minX + x;
                        const sy = minY + y;
                        const si = sy * w + sx;
                        const o = si * 4;
                        if (isPaper[si]) {
                            gray[y * cw + x] = 255;
                            continue;
                        }
                        const a = px[o + 3] / 255;
                        const r = px[o] * a + 255 * (1 - a);
                        const g = px[o + 1] * a + 255 * (1 - a);
                        const b = px[o + 2] * a + 255 * (1 - a);
                        let lum = luminance(r, g, b);
                        // Lift dark brand fills so faces/text inside stay readable
                        lum = 40 + lum * 0.78;
                        gray[y * cw + x] = Math.max(0, Math.min(255, lum));
                        contentSum += gray[y * cw + x];
                        contentCount += 1;
                    }
                }

                const mean = contentCount > 0 ? contentSum / contentCount : 128;
                // Adaptive threshold around content mean (cleaner than global 128)
                const threshold = Math.max(90, Math.min(170, mean * 0.92));

                const outCanvas = document.createElement('canvas');
                outCanvas.width = cw;
                outCanvas.height = ch;
                const octx = outCanvas.getContext('2d');
                if (!octx) {
                    if (!cancelled) setBwSrc(src);
                    return;
                }

                const out = octx.createImageData(cw, ch);
                const od = out.data;

                for (let i = 0; i < cw * ch; i++) {
                    const sx = (i % cw) + minX;
                    const sy = ((i / cw) | 0) + minY;
                    const paper = isPaper[sy * w + sx] === 1;
                    let v = 255;
                    if (!paper) {
                        // Soft 3-level look without speckled halo:
                        // black / mid-gray hatch / white
                        const g = gray[i];
                        if (g < threshold * 0.72) {
                            v = 0;
                        } else if (g < threshold * 1.05) {
                            // checker for midtones — denser detail, no error bleed
                            const x = i % cw;
                            const y = (i / cw) | 0;
                            v = (x + y) % 2 === 0 ? 0 : 255;
                        } else if (g < threshold * 1.25) {
                            const x = i % cw;
                            const y = (i / cw) | 0;
                            v = (x % 3 === 0 && y % 3 === 0) ? 0 : 255;
                        } else {
                            v = 255;
                        }
                    }
                    const p = i * 4;
                    od[p] = v;
                    od[p + 1] = v;
                    od[p + 2] = v;
                    od[p + 3] = 255;
                }

                octx.putImageData(out, 0, 0);

                // 4) Final pass: force any remaining edge-connected white noise off
                // (already paper-masked; just ensure corners pure white)
                const cleaned = octx.getImageData(0, 0, cw, ch);
                const cd = cleaned.data;
                for (let y = 0; y < ch; y++) {
                    for (let x = 0; x < cw; x++) {
                        const si = (minY + y) * w + (minX + x);
                        if (isPaper[si] !== 1) continue;
                        const p = (y * cw + x) * 4;
                        cd[p] = 255;
                        cd[p + 1] = 255;
                        cd[p + 2] = 255;
                    }
                }
                octx.putImageData(cleaned, 0, 0);

                const dataUrl = outCanvas.toDataURL('image/png');
                if (!cancelled) setBwSrc(dataUrl);
            } catch {
                if (!cancelled) setBwSrc(src);
            }
        };

        img.onerror = () => {
            if (!cancelled) setBwSrc(src);
        };

        img.src = src;

        return () => {
            cancelled = true;
        };
    }, [src]);

    if (!bwSrc) {
        return (
            <div
                className={cn('mx-auto mb-2 bg-white', className)}
                style={{ width: size, height: size }}
                aria-hidden
            />
        );
    }

    return (
        <img
            src={bwSrc}
            alt={alt}
            width={size}
            height={size}
            className={cn('receipt-thermal-logo mx-auto mb-2 bg-white object-contain', className)}
            style={{
                width: size,
                height: size,
                imageRendering: 'auto',
            }}
        />
    );
}
