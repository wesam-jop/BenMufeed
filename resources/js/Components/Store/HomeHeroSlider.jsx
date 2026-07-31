import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

const AUTO_MS = 5500;

function resolveHref(url) {
    if (!url || typeof url !== 'string') {
        return null;
    }

    const trimmed = url.trim();

    if (trimmed === '') {
        return null;
    }

    if (trimmed.startsWith('/') || trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        return trimmed;
    }

    return `/${trimmed}`;
}

function isExternal(url) {
    return url?.startsWith('http://') || url?.startsWith('https://');
}

/**
 * @param {{ heroSlides: Array<{ id: number, title?: string|null, subtitle?: string|null, button_text?: string|null, button_url?: string|null, image_url?: string|null }> }} props
 */
export default function HomeHeroSlider({ heroSlides }) {
    const count = heroSlides.length;
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);

    const goTo = useCallback(
        (next) => {
            if (count <= 0) {
                return;
            }

            setIndex((next + count) % count);
        },
        [count],
    );

    const next = useCallback(() => goTo(index + 1), [goTo, index]);
    const prev = useCallback(() => goTo(index - 1), [goTo, index]);

    useEffect(() => {
        if (count <= 1 || paused) {
            return undefined;
        }

        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % count);
        }, AUTO_MS);

        return () => window.clearInterval(id);
    }, [count, paused]);

    if (count === 0) {
        return null;
    }

    const slide = heroSlides[index];
    const href = resolveHref(slide.button_url);

    return (
        <section
            className="relative overflow-hidden rounded-3xl border border-store-border bg-restaurant-hero text-store-surface shadow-lg"
            aria-roledescription="carousel"
            aria-label="معرض صور الكافيتيريا"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocusCapture={() => setPaused(true)}
            onBlurCapture={() => setPaused(false)}
        >
            <div className="relative aspect-[16/7] w-full min-h-[220px] sm:aspect-[16/6] sm:min-h-[280px] lg:min-h-[360px]">
                {heroSlides.map((s, i) => (
                    <SlideImage key={s.id} slide={s} active={i === index} priority={i === 0} />
                ))}
            </div>

            <SlideOverlay slide={slide} href={href} />

            {count > 1 ? (
                <>
                    <button
                        type="button"
                        onClick={prev}
                        className="absolute start-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-store-primary/50 text-store-surface shadow-md backdrop-blur-md transition-all duration-fast hover:border-store-secondary/50 hover:bg-store-secondary hover:text-store-primary sm:start-4 sm:h-11 sm:w-11"
                        aria-label="الشريحة السابقة"
                    >
                        <span aria-hidden className="text-lg leading-none">
                        ‹
                        </span>
                    </button>
                    <button
                        type="button"
                        onClick={next}
                        className="absolute end-3 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-store-primary/50 text-store-surface shadow-md backdrop-blur-md transition-all duration-fast hover:border-store-secondary/50 hover:bg-store-secondary hover:text-store-primary sm:end-4 sm:h-11 sm:w-11"
                        aria-label="الشريحة التالية"
                    >
                        <span aria-hidden className="text-lg leading-none">
                            
                            ›
                        </span>
                    </button>
                    <SlideDots count={count} index={index} onSelect={goTo} />
                </>
            ) : null}
        </section>
    );
}

function SlideImage({ slide, active, priority = false }) {
    return (
        <div
            className={`absolute inset-0 transition-opacity duration-700 ease-in-out ${
                active ? 'opacity-100' : 'pointer-events-none opacity-0'
            }`}
            aria-hidden={!active}
        >
            {slide.image_url ? (
                <img
                    src={slide.image_url}
                    alt=""
                    className="h-full w-full object-cover"
                    loading={priority ? 'eager' : 'lazy'}
                    fetchPriority={priority ? 'high' : 'auto'}
                    decoding={priority ? 'sync' : 'async'}
                />
            ) : (
                <div className="h-full w-full bg-store-primary" />
            )}
        </div>
    );
}

function SlideOverlay({ slide, href }) {
    const hasText = slide.title || slide.subtitle || (slide.button_text && href);

    if (!hasText) {
        return <SlideGradientOverlay />;
    }

    return (
        <>
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-store-primary via-store-primary/50 to-transparent" />
            <div className="absolute inset-x-0 bottom-0 z-10 px-5 pb-12 pt-20 sm:px-10 sm:pb-14">
                {slide.title ? (
                    <h1 className="font-display text-2xl font-bold leading-tight text-store-surface drop-shadow-sm sm:text-4xl lg:text-5xl">
                        {slide.title}
                    </h1>
                ) : null}
                {slide.subtitle ? (
                    <p className="mt-3 max-w-2xl text-sm leading-relaxed text-store-surface/90 sm:text-base">{slide.subtitle}</p>
                ) : null}
                {slide.button_text && href ? (
                    isExternal(href) ? (
                        <a href={href} target="_blank" rel="noreferrer" className="store-btn-primary mt-6">
                            {slide.button_text}
                        </a>
                    ) : (
                        <Link href={href} className="store-btn-primary mt-6">
                            {slide.button_text}
                        </Link>
                    )
                ) : null}
            </div>
        </>
    );
}

function SlideGradientOverlay() {
    return (
        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-store-primary/80 via-store-primary/25 to-transparent" />
    );
}

function SlideDots({ count, index, onSelect }) {
    return (
        <div className="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 gap-2 rounded-full bg-store-primary/30 px-3 py-2 backdrop-blur-sm">
            {Array.from({ length: count }, (_, i) => (
                <button
                    key={i}
                    type="button"
                    onClick={() => onSelect(i)}
                    className={`h-2 rounded-full transition-all ${
                        i === index ? 'w-6 bg-store-secondary' : 'w-2 bg-store-surface/50 hover:bg-store-surface'
                    }`}
                    aria-label={`الشريحة ${i + 1}`}
                    aria-current={i === index ? 'true' : undefined}
                />
            ))}
        </div>
    );
}
