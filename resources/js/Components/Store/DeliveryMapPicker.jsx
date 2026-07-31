import { useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { geolocationErrorMessage } from '@/lib/tableOrdering';

const FALLBACK_CENTER = [33.5138, 36.2765];

export default function DeliveryMapPicker({ latitude, longitude, onChange, defaultCenter = null }) {
    const { tableGeofence } = usePage().props;
    const mapRef = useRef(null);
    const mapInstance = useRef(null);
    const markerRef = useRef(null);
    const [geoError, setGeoError] = useState(null);

    const mapCenter = useMemo(() => {
        if (defaultCenter?.latitude != null && defaultCenter?.longitude != null) {
            return [defaultCenter.latitude, defaultCenter.longitude];
        }

        if (tableGeofence?.latitude != null && tableGeofence?.longitude != null) {
            return [tableGeofence.latitude, tableGeofence.longitude];
        }

        return FALLBACK_CENTER;
    }, [defaultCenter, tableGeofence]);

    useEffect(() => {
        let map = null;

        if (mapInstance.current || !mapRef.current) {
            return undefined;
        }

        const lat = latitude ?? mapCenter[0];
        const lng = longitude ?? mapCenter[1];

        map = L.map(mapRef.current).setView([lat, lng], latitude != null && longitude != null ? 16 : 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        if (latitude != null && longitude != null) {
            markerRef.current = L.marker([latitude, longitude]).addTo(map);
        }

        map.on('click', (event) => {
            const { lat: clickLat, lng: clickLng } = event.latlng;
            setGeoError(null);

            if (markerRef.current) {
                markerRef.current.setLatLng(event.latlng);
            } else {
                markerRef.current = L.marker(event.latlng).addTo(map);
            }

            onChange?.({ latitude: clickLat, longitude: clickLng });
        });

        mapInstance.current = map;

        return () => {
            if (mapInstance.current) {
                mapInstance.current.remove();
                mapInstance.current = null;
                markerRef.current = null;
            }
        };
    }, [mapCenter]);

    useEffect(() => {
        if (!mapInstance.current || latitude == null || longitude == null) {
            return;
        }

        const latLng = L.latLng(latitude, longitude);

        if (markerRef.current) {
            markerRef.current.setLatLng(latLng);
        } else {
            markerRef.current = L.marker(latLng).addTo(mapInstance.current);
        }

        mapInstance.current.setView(latLng, 16);
    }, [latitude, longitude]);

    const useMyLocation = () => {
        setGeoError(null);

        if (!navigator.geolocation) {
            setGeoError(geolocationErrorMessage(new Error('geolocation_unsupported')));

            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                onChange?.({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                });
            },
            (error) => {
                if (error.code === 1) {
                    setGeoError('اسمح بالوصول للموقع لتحديد نقطة التوصيل على الخريطة.');
                } else {
                    setGeoError(geolocationErrorMessage(error));
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
        );
    };

    return (
        <div>
            <div className="delivery-map-picker">
                <div ref={mapRef} className="h-56 w-full" />
            </div>
            <button
                type="button"
                onClick={useMyLocation}
                className="mt-2 text-xs font-semibold text-store-secondary hover:underline"
            >
                استخدام موقعي الحالي
            </button>
            {geoError ? <p className="mt-2 text-xs text-red-600">{geoError}</p> : null}
            {latitude == null || longitude == null ? (
                <p className="mt-2 text-xs text-store-muted">اضغط على الخريطة أو استخدم موقعك الحالي لتحديد نقطة التوصيل.</p>
            ) : null}
        </div>
    );
}
