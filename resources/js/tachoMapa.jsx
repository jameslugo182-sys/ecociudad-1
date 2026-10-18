import { CircleMarker, useMap, useMapEvents } from 'react-leaflet';
import { useEffect } from 'react';

const CENTRO_TAMBO = [-12.056, -75.221];

export function InvalidarMapaModal() {
    const map = useMap();

    useEffect(() => {
        const temporizador = window.setTimeout(() => map.invalidateSize(), 250);
        return () => window.clearTimeout(temporizador);
    }, [map]);

    return null;
}

export function CapturarClicMapa({ onSelect }) {
    useMapEvents({
        click(event) {
            onSelect(Number(event.latlng.lat.toFixed(7)), Number(event.latlng.lng.toFixed(7)));
        },
    });

    return null;
}

export function AjustarVistaTachos({ tachos, punto }) {
    const map = useMap();

    useEffect(() => {
        const puntos = tachos
            .filter((tacho) => tacho.latitud && tacho.longitud)
            .map((tacho) => [tacho.latitud, tacho.longitud]);

        if (punto) {
            puntos.push(punto);
        }

        if (puntos.length > 1) {
            map.fitBounds(puntos, { padding: [40, 40], maxZoom: 16 });
            return;
        }

        if (puntos.length === 1) {
            map.setView(puntos[0], 16);
            return;
        }

        map.setView(CENTRO_TAMBO, 13);
    }, [map, tachos, punto]);

    return null;
}

export function colorTacho(tacho) {
    if (!tacho.activo || tacho.estado === 'Inactivo') {
        return '#64748b';
    }

    if (tacho.estado === 'Lleno' || tacho.alerta_activa) {
        return '#dc2626';
    }

    if ((tacho.nivel_porcentaje ?? 0) >= 50) {
        return '#d97706';
    }

    return '#059669';
}

export function MarcadorTacho({ tacho, children, radius = 10 }) {
    return (
        <CircleMarker
            center={[tacho.latitud, tacho.longitud]}
            radius={radius}
            pathOptions={{
                color: '#ffffff',
                weight: 3,
                fillColor: colorTacho(tacho),
                fillOpacity: 1,
            }}
        >
            {children}
        </CircleMarker>
    );
}

export { CENTRO_TAMBO };
