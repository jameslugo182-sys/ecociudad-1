import { BASE_MUNICIPAL, posicionesDeRuta } from '@/rutaMapa';
import L from 'leaflet';
import { useEffect } from 'react';
import { MapContainer, Marker, Polyline, Popup, TileLayer, useMap } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

function iconoParada(orden, color = '#059669') {
    return L.divIcon({
        className: 'ruta-parada-icono',
        html: `<div style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:${color};color:#fff;font-size:12px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)">${orden}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });
}

function iconoBase() {
    return L.divIcon({
        className: 'ruta-parada-icono',
        html: '<div style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#111827;color:#fff;font-size:11px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)">B</div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });
}

function AjustarMapa({ puntos }) {
    const map = useMap();

    useEffect(() => {
        if (puntos.length) {
            map.fitBounds(puntos, { padding: [36, 36], maxZoom: 16 });
        }
    }, [map, puntos]);

    return null;
}

export default function RutaOperativaMapa({
    ruta = null,
    paradas = [],
    color = '#059669',
    altura = 'h-[420px]',
    mostrarBase = true,
}) {
    const reportes = ruta?.reportes ?? paradas;
    const geometria = posicionesDeRuta(ruta, reportes);
    const puntosAjuste = [
        ...(mostrarBase ? [BASE_MUNICIPAL] : []),
        ...geometria,
        ...reportes
            .filter((reporte) => reporte.latitud != null && reporte.longitud != null)
            .map((reporte) => [Number(reporte.latitud), Number(reporte.longitud)]),
    ];

    return (
        <MapContainer center={BASE_MUNICIPAL} zoom={13} className={`${altura} w-full`}>
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            <AjustarMapa puntos={puntosAjuste} />

            {geometria.length >= 2 && (
                <Polyline positions={geometria} pathOptions={{ color, weight: 6, opacity: 0.88 }} />
            )}

            {mostrarBase && (
                <Marker position={BASE_MUNICIPAL} icon={iconoBase()}>
                    <Popup>
                        <strong>Base municipal</strong>
                        <p>Punto de partida del recorrido.</p>
                    </Popup>
                </Marker>
            )}

            {reportes.map((reporte) => (
                <Marker
                    key={reporte.id}
                    position={[Number(reporte.latitud), Number(reporte.longitud)]}
                    icon={iconoParada(reporte.pivot?.orden ?? reporte.ruta_orden ?? '', color)}
                >
                    <Popup>
                        <strong>
                            Parada {reporte.pivot?.orden ?? reporte.ruta_orden ?? ''} · Incidencia #{reporte.id}
                        </strong>
                        <p>{reporte.descripcion}</p>
                    </Popup>
                </Marker>
            ))}
        </MapContainer>
    );
}
