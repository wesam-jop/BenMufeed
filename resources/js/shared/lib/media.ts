/**
 * Resolve a product/category image for <img src>.
 * Prefers image_url; falls back to storage path.
 */
export function resolveMediaUrl(
    imageUrl?: string | null,
    image?: string | null,
): string | null {
    if (imageUrl && imageUrl.trim() !== '') {
        return imageUrl;
    }

    if (!image || image.trim() === '') {
        return null;
    }

    if (
        image.startsWith('http://')
        || image.startsWith('https://')
        || image.startsWith('data:')
        || image.startsWith('/storage/')
        || image.startsWith('/images/')
    ) {
        return image;
    }

    return `/storage/${image.replace(/^\/+/, '')}`;
}
