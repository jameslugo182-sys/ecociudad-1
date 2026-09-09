import EstadoBadge from '@/Components/EstadoBadge';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { CircleMarker, MapContainer, Popup, TileLayer, useMap } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

const colores = {
    Pendiente: '#d97706',
    Asignado: '#2563eb',
    'En atención': '#7c3aed',
    Atendido: '#059669',
};

function AjustarLimites({ reportes }) {
    const map = useMap();

    useEffect(() => {
        if (reportes.length > 0) {
            map.fitBounds(
                reportes.map((reporte) => [reporte.latitud, reporte.longitud]),
                { padding: [40, 40], maxZoom: 16 },
            );
        }
    }, [map, reportes]);

    return null;
}

export default function Mapa({ reportes, routeNames }) {
    const navegacion = [
        { label: 'Listado de incidencias', href: route(routeNames.index), active: routeNames.index },
        { label: 'Mapa de incidencias', href: route(routeNames.mapa), active: routeNames.mapa },
    ];

    return (
        <ModuleLayout moduleName="Incidencias registradas" items={navegacion}>
            <Head title="Mapa de incidencias" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Monitoreo territorial</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Mapa de incidencias</h2>
                        </div>
                        <Link
                            href={route(routeNames.index)}
                            className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Ver listado
                        </Link>
                    </div>
                    <div className="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        {Object.entries(colores).map(([estado, color]) => (
                            <div key={estado} className="flex items-center gap-2 text-xs font-medium text-slate-600">
                                <span className="h-3 w-3 rounded-full" style={{ backgroundColor: color }} />
                                {estado}
                            </div>
                        ))}
                        <span className="ml-auto text-xs text-slate-500">{reportes.length} incidencias</span>
                    </div>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {reportes.length === 0 ? (
                            <div className="flex h-[65vh] items-center justify-center text-sm text-slate-500">
                                No existen incidencias georreferenciadas.
                            </div>
                        ) : (
                            <MapContainer
                                center={[-12.056, -75.221]}
                                zoom={13}
                                scrollWheelZoom
                                className="h-[65vh] min-h-[480px] w-full"
                            >
                                <TileLayer
                                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                />
                                <AjustarLimites reportes={reportes} />
                                {reportes.map((reporte) => (
                                    <CircleMarker
                                        key={reporte.id}
                                        center={[reporte.latitud, reporte.longitud]}
                                        radius={10}
                                        pathOptions={{
                                            color: '#ffffff',
                                            weight: 3,
                                            fillColor: colores[reporte.estado] || '#64748b',
                                            fillOpacity: 1,
                                        }}
                                    >
                                        <Popup minWidth={240}>
                                            <div className="space-y-3">
                                                {reporte.foto_url && (
                                                    <img
                                                        src={reporte.foto_url}
                                                        alt=""
                                                        className="h-28 w-full rounded-lg object-cover"
                                                    />
                                                )}
                                                <div className="flex items-center justify-between gap-3">
                                                    <strong>Reporte #{reporte.id}</strong>
                                                    <EstadoBadge estado={reporte.estado} />
                                                </div>
                                                <p className="text-sm text-slate-600">{reporte.descripcion}</p>
                                                <p className="text-xs text-slate-500">
                                                    Ciudadano: {reporte.user?.name}
                                                </p>
                                                {reporte.responsable && (
                                                    <p className="text-xs text-slate-500">
                                                        Responsable: {reporte.responsable.name}
                                                    </p>
                                                )}
                                            </div>
                                        </Popup>
                                    </CircleMarker>
                                ))}
                            </MapContainer>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
