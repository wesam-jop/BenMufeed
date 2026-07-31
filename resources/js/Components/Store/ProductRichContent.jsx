/**
 * عرض HTML من محرر الوصف الكامل (TipTap / Filament) بشكل منسق وآمن.
 *
 * @param {{ html: string, className?: string }} props
 */
export default function ProductRichContent({ html, className = '' }) {
    if (!html?.trim()) {
        return null;
    }

    return (
        <div
            className={`product-rich-content ${className}`.trim()}
            dir="rtl"
            lang="ar"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
