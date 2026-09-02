import { ExternalLink, MapPin } from 'lucide-react';
import { useEffect, useState, type ComponentType } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type LeafletModules = {
    MapContainer: ComponentType<Record<string, unknown>>;
    TileLayer: ComponentType<Record<string, unknown>>;
    Marker: ComponentType<Record<string, unknown>>;
    markerIcon: unknown;
};

type Props = {
    lat?: number | string | null;
    lng?: number | string | null;
    title?: string | null;
    subtitle?: string | null;
    address?: string | null;
    mapUrl?: string | null;
    embedHtml?: string | null;
    className?: string;
};

export default function CafeLocationSection({
    lat,
    lng,
    title = 'موقعنا على الخريطة',
    subtitle = 'تفضل بزيارتنا في أقرب وقت',
    address,
    mapUrl,
    embedHtml,
    className,
}: Props) {
    const [map, setMap] = useState<LeafletModules | null>(null);
    const parsedLat = lat != null && lat !== '' ? Number(lat) : null;
    const parsedLng = lng != null && lng !== '' ? Number(lng) : null;
    const hasCoords =
        parsedLat !== null &&
        parsedLng !== null &&
        !Number.isNaN(parsedLat) &&
        !Number.isNaN(parsedLng);
    const googleUrl =
        mapUrl ??
        (hasCoords ? `https://www.google.com/maps?q=${parsedLat},${parsedLng}` : null);

    useEffect(() => {
        if (!hasCoords) {
            return;
        }

        let cancelled = false;

        void (async () => {
            const [{ MapContainer, Marker, TileLayer }, L] = await Promise.all([
                import('react-leaflet'),
                import('leaflet'),
                import('leaflet/dist/leaflet.css'),
            ]);

            if (cancelled) {
                return;
            }

            const markerIcon = L.default.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41],
            });

            setMap({
                MapContainer: MapContainer as ComponentType<Record<string, unknown>>,
                TileLayer: TileLayer as ComponentType<Record<string, unknown>>,
                Marker: Marker as ComponentType<Record<string, unknown>>,
                markerIcon,
            });
        })();

        return () => {
            cancelled = true;
        };
    }, [hasCoords]);

    if (!hasCoords && !embedHtml) {
        return null;
    }

    return (
        <section className={cn('mx-auto max-w-6xl px-4', className)}>
            {(title || subtitle || address) && (
                <div className="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        {title ? (
                            <h2 className="text-2xl font-bold text-stone-900 md:text-3xl">{title}</h2>
                        ) : null}
                        {subtitle && <p className="mt-1 text-sm text-stone-500 md:text-base">{subtitle}</p>}
                        {address && (
                            <p className="mt-2 inline-flex items-center gap-1.5 text-sm text-stone-600">
                                <MapPin className="size-4 text-amber-800" />
                                {address}
                            </p>
                        )}
                    </div>
                    {googleUrl && (
                        <Button variant="outline" className="border-stone-200" asChild>
                            <a href={googleUrl} target="_blank" rel="noreferrer">
                                <ExternalLink className="size-4" />
                                فتح في خرائط جوجل
                            </a>
                        </Button>
                    )}
                </div>
            )}
            {!title && !subtitle && !address && googleUrl ? (
                <div className="mb-3 flex justify-end">
                    <Button variant="outline" className="border-stone-200" asChild>
                        <a href={googleUrl} target="_blank" rel="noreferrer">
                            <ExternalLink className="size-4" />
                            فتح في خرائط جوجل
                        </a>
                    </Button>
                </div>
            ) : null}

            <div className="overflow-hidden rounded-3xl bg-stone-100 shadow-sm ring-1 ring-stone-200/80">
                {hasCoords && map ? (
                    <map.MapContainer
                        center={[parsedLat!, parsedLng!]}
                        zoom={16}
                        className="z-0 h-[280px] w-full md:h-[360px]"
                        scrollWheelZoom={false}
                        dragging
                    >
                        <map.TileLayer
                            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        />
                        <map.Marker position={[parsedLat!, parsedLng!]} icon={map.markerIcon} />
                    </map.MapContainer>
                ) : embedHtml ? (
                    <div
                        className="aspect-[16/7] min-h-[280px] w-full [&>iframe]:size-full [&>iframe]:border-0"
                        dangerouslySetInnerHTML={{ __html: embedHtml }}
                    />
                ) : (
                    <div className="flex h-[280px] items-center justify-center text-stone-400 md:h-[360px]">
                        جاري تحميل الخريطة...
                    </div>
                )}
            </div>
        </section>
    );
}

export function shouldShowCafeMap(
    placements: string[] | null | undefined,
    page: 'home' | 'about' | 'contact',
): boolean {
    const list = placements ?? ['contact'];

    return list.includes(page);
}
