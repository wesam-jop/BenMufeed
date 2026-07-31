/**
 * صورة مع تحميل كسول وتخزين مؤقت عبر رؤوس HTTP من السيرفر.
 *
 * @param {{ src?: string|null, alt: string, className?: string }} props
 */
export default function CachedImage({ src, alt, className = '' }) {
    if (!src) {
        return null;
    }

    return (
        <img
            src={src}
            alt={alt}
            className={className}
            loading="lazy"
            decoding="async"
        />
    );
}
