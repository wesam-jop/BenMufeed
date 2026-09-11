import { MapPin } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState, type ComponentType } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

const DEFAULT_CENTER: [number, number] = [33.5138, 36.2765];

type LeafletModules = {
    MapContainer: ComponentType<Record<string, unknown>>;
    TileLayer: ComponentType<Record<string, unknown>>;
    Marker: ComponentType<Record<string, unknown>>;
    useMap: (hook: () => void) => void;
    useMapEvents: (handlers: { click?: (e: { latlng: { lat: number; lng: number } }) => void }) => void;
    markerIcon: unknown;
};

type DeliveryLocationMapProps = {
    lat?: number | string | null;
    lng?: number | string | null;
    onLocationChange: (lat: number, lng: number, address?: string) => void;
    hint?: string;
};

async function reverseGeocode(lat: number, lng: number): Promise<string | undefined> {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'json');
        url.searchParams.set('lat', String(lat));
        url.searchParams.set('lon', String(lng));
        url.searchParams.set('accept-language', 'ar');

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return undefined;
        }

        const data = (await response.json()) as { display_name?: string };

        return data.display_name;
    } catch {
        return undefined;
    }
}

function InteractiveMap({
    modules,
    mapCenter,
    hasMarker,
    markerPosition,
    onSelect,
    locating,
    resolving,
}: {
    modules: LeafletModules;
    mapCenter: [number, number];
    hasMarker: boolean;
    markerPosition: [number, number] | null;
    onSelect: (lat: number, lng: number) => void;
    locating: boolean;
    resolving: boolean;
}) {
    const { MapContainer, TileLayer, Marker, useMap, useMapEvents, markerIcon } = modules;

    function MapClickHandler() {
        useMapEvents({
            click(e) {
                onSelect(e.latlng.lat, e.latlng.lng);
            },
        });

        return null;
    }

    function MapRecenter({ center }: { center: [number, number] }) {
        const map = useMap();

        useEffect(() => {
            map.setView(center, map.getZoom(), { animate: true });
        }, [center, map]);

        return null;
    }

    return (
        <div className="relative overflow-hidden rounded-xl border border-amber-100">
            <MapContainer
                center={mapCenter}
                zoom={hasMarker ? 16 : 12}
                className="z-0 h-[320px] w-full"
                scrollWheelZoom
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />
                <MapClickHandler />
                {markerPosition && (
                    <>
                        <Marker
                            position={markerPosition}
                            icon={markerIcon}
                            draggable
                            eventHandlers={{
                                dragend: (e: { target: { getLatLng: () => { lat: number; lng: number } } }) => {
                                    const { lat, lng } = e.target.getLatLng();
                                    onSelect(lat, lng);
                                },
                            }}
                        />
                        <MapRecenter center={markerPosition} />
                    </>
                )}
            </MapContainer>

            {(locating || resolving) && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/60">
                    <Spinner className="size-8 text-amber-800" />
                </div>
            )}
        </div>
    );
}

export default function DeliveryLocationMap({
    lat,
    lng,
    onLocationChange,
    hint = 'اضغط على الخريطة أو اسحب الدبوس لتحديد موقع التوصيل',
}: DeliveryLocationMapProps) {
    const [modules, setModules] = useState<LeafletModules | null>(null);
    const [locating, setLocating] = useState(false);
    const [resolving, setResolving] = useState(false);

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            const [reactLeaflet, L] = await Promise.all([
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

            setModules({
                MapContainer: reactLeaflet.MapContainer as ComponentType<Record<string, unknown>>,
                TileLayer: reactLeaflet.TileLayer as ComponentType<Record<string, unknown>>,
                Marker: reactLeaflet.Marker as ComponentType<Record<string, unknown>>,
                useMap: reactLeaflet.useMap,
                useMapEvents: reactLeaflet.useMapEvents,
                markerIcon,
            });
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    const parsedLat = lat !== '' && lat != null ? Number(lat) : null;
    const parsedLng = lng !== '' && lng != null ? Number(lng) : null;

    const hasMarker =
        parsedLat !== null &&
        parsedLng !== null &&
        !Number.isNaN(parsedLat) &&
        !Number.isNaN(parsedLng);

    const markerPosition = useMemo<[number, number] | null>(() => {
        if (!hasMarker) {
            return null;
        }

        return [parsedLat!, parsedLng!];
    }, [hasMarker, parsedLat, parsedLng]);

    const mapCenter = markerPosition ?? DEFAULT_CENTER;

    const selectLocation = useCallback(
        async (selectedLat: number, selectedLng: number) => {
            setResolving(true);
            const address = await reverseGeocode(selectedLat, selectedLng);
            onLocationChange(selectedLat, selectedLng, address);
            setResolving(false);
        },
        [onLocationChange],
    );

    const detectCurrentLocation = () => {
        if (typeof navigator === 'undefined' || !navigator.geolocation) {
            return;
        }

        setLocating(true);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                void selectLocation(pos.coords.latitude, pos.coords.longitude);
                setLocating(false);
            },
            () => setLocating(false),
            { enableHighAccuracy: true, timeout: 12000 },
        );
    };

    return (
        <div className="space-y-3">
            {!modules ? (
                <div className="flex h-[320px] items-center justify-center rounded-xl border border-amber-100 bg-muted">
                    <Spinner className="size-8 text-amber-800" />
                </div>
            ) : (
                <InteractiveMap
                    modules={modules}
                    mapCenter={mapCenter}
                    hasMarker={hasMarker}
                    markerPosition={markerPosition}
                    onSelect={selectLocation}
                    locating={locating}
                    resolving={resolving}
                />
            )}

            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">
                    <MapPin className="mb-0.5 inline size-4 text-amber-700" /> {hint}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={detectCurrentLocation}
                    disabled={locating || resolving || !modules}
                >
                    موقعي الحالي
                </Button>
            </div>
        </div>
    );
}
