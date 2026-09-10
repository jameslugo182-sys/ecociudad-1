import FlashMessage from '@/Components/FlashMessage';
import { navegacionRutas } from '@/horarioRuta';
import { BASE_MUNICIPAL, posicionesDeRuta } from '@/rutaMapa';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { CircleMarker, MapContainer, Polyline, Popup, TileLayer, useMap } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

const coloresEstado = {
    Pendiente: '#d97706',
    Asignado: '#2563eb',
    'En atención': '#7c3aed',
};
const coloresRuta = ['#059669', '#2563eb', '#7c3aed', '#dc2626', '#0891b2'];

function AjustarMapa({ puntos }) {
    const map = useMap();

    useEffect(() => {
        if (puntos.length) {
            map.fitBounds(puntos, { padding: [35, 35], maxZoom: 16 });
        }
    }, [map, puntos]);

    return null;
}

export default function Index({ reportes, equipos, rutas, routeNames }) {
    const form = useForm({
        camion_id: '',
        fecha: new Date().toISOString().slice(0, 10),
        reporte_ids: [],
    });
    const rutasActivas = rutas.filter((rutaItem) => ['Planificada', 'En curso'].includes(rutaItem.estado));
    const todosLosPuntos = [
        BASE_MUNICIPAL,
        ...reportes.map((reporte) => [reporte.latitud, reporte.longitud]),
        ...rutasActivas.flatMap((rutaItem) => posicionesDeRuta(rutaItem)),
    ];

    const alternarReporte = (id) => {
        form.setData(
            'reporte_ids',
            form.data.reporte_ids.includes(id)
                ? form.data.reporte_ids.filter((reporteId) => reporteId !== id)
                : [...form.data.reporte_ids, id],
        );
    };

    const generar = (event) => {
        event.preventDefault();
        form.post(route(routeNames.store), {
            preserveScroll: true,
            onSuccess: () => form.reset('camion_id', 'reporte_ids'),
        });
    };

    const cancelar = (rutaId) => {
        if (window.confirm('¿Cancelar esta ruta y liberar sus incidencias?')) {
            router.patch(route(routeNames.cancelar, rutaId), {}, { preserveScroll: true });
        }
    };

    return (
        <ModuleLayout moduleName="Gestión de rutas" items={navegacionRutas(routeNames)}>
            <Head title="Gestión de rutas" />

            <div className="min-h-screen bg-slate-50 px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Planificación operativa</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Gestión de rutas</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Selecciona incidencias, asigna un vehículo y genera el recorrido más rápido por las calles.
                        </p>
                    </div>

                    <FlashMessage />

                    <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                        <section className="overflow-hidden border border-slate-300 bg-white shadow-sm">
                            <div className="flex flex-wrap items-center gap-4 border-b border-slate-300 px-5 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">Mapa operativo</h3>
                                    <p className="text-xs text-slate-500">
                                        {reportes.length} incidencias disponibles · {rutasActivas.length} rutas activas
                                    </p>
                                </div>
                                <div className="ml-auto flex gap-3 text-xs text-slate-500">
                                    <span>● Pendiente</span>
                                    <span className="text-blue-600">━ Ruta por calles</span>
                                </div>
                            </div>

                            <MapContainer center={[-12.056, -75.221]} zoom={13} className="h-[650px] w-full">
                                <TileLayer
                                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                />
                                <AjustarMapa puntos={todosLosPuntos} />

                                <CircleMarker
                                    center={BASE_MUNICIPAL}
                                    radius={8}
                                    pathOptions={{ color: '#fff', weight: 2, fillColor: '#111827', fillOpacity: 1 }}
                                >
                                    <Popup>
                                        <strong>Base municipal</strong>
                                        <p>Punto de partida de las rutas.</p>
                                    </Popup>
                                </CircleMarker>

                                {rutasActivas.map((rutaItem, indice) => {
                                    const color = coloresRuta[indice % coloresRuta.length];
                                    const posiciones = posicionesDeRuta(rutaItem);

                                    return posiciones.length >= 2 ? (
                                        <Polyline
                                            key={rutaItem.id}
                                            positions={posiciones}
                                            pathOptions={{ color, weight: 5, opacity: 0.85 }}
                                        />
                                    ) : null;
                                })}

                                {reportes.map((reporte) => (
                                    <CircleMarker
                                        key={`disponible-${reporte.id}`}
                                        center={[reporte.latitud, reporte.longitud]}
                                        radius={form.data.reporte_ids.includes(reporte.id) ? 13 : 9}
                                        pathOptions={{
                                            color: form.data.reporte_ids.includes(reporte.id) ? '#111827' : '#fff',
                                            weight: 3,
                                            fillColor: coloresEstado[reporte.estado] || '#d97706',
                                            fillOpacity: 1,
                                        }}
                                        eventHandlers={{ click: () => alternarReporte(reporte.id) }}
                                    >
                                        <Popup>
                                            <strong>Incidencia #{reporte.id}</strong>
                                            <p>{reporte.descripcion}</p>
                                            <button type="button" onClick={() => alternarReporte(reporte.id)}>
                                                {form.data.reporte_ids.includes(reporte.id) ? 'Quitar selección' : 'Seleccionar'}
                                            </button>
                                        </Popup>
                                    </CircleMarker>
                                ))}

                                {rutasActivas.flatMap((rutaItem, rutaIndice) =>
                                    rutaItem.reportes.map((reporte) => (
                                        <CircleMarker
                                            key={`ruta-${rutaItem.id}-${reporte.id}`}
                                            center={[reporte.latitud, reporte.longitud]}
                                            radius={10}
                                            pathOptions={{
                                                color: '#fff',
                                                weight: 3,
                                                fillColor: coloresRuta[rutaIndice % coloresRuta.length],
                                                fillOpacity: 1,
                                            }}
                                        >
                                            <Popup>
                                                <strong>Parada {reporte.pivot.orden}</strong>
                                                <p>{rutaItem.camion.codigo} · {reporte.descripcion}</p>
                                            </Popup>
                                        </CircleMarker>
                                    )),
                                )}
                            </MapContainer>
                        </section>

                        <aside>
                            <form onSubmit={generar} className="sticky top-5 space-y-4 border border-slate-300 bg-white p-5 shadow-sm">
                                <div>
                                    <h3 className="font-bold text-slate-900">Nueva ruta</h3>
                                    <p className="text-xs text-slate-500">El trazado sigue las calles desde la base municipal.</p>
                                </div>

                                <label className="block text-xs font-semibold text-slate-600">
                                    Fecha de operación
                                    <input type="date" value={form.data.fecha} onChange={(event) => form.setData('fecha', event.target.value)} className="mt-1.5 w-full border-slate-300 text-sm" required />
                                </label>

                                <label className="block text-xs font-semibold text-slate-600">
                                    Vehículo y equipo
                                    <select value={form.data.camion_id} onChange={(event) => form.setData('camion_id', event.target.value)} className="mt-1.5 w-full border-slate-300 text-sm" required>
                                        <option value="">Seleccionar vehículo</option>
                                        {equipos.map((equipo) => (
                                            <option key={equipo.id} value={equipo.id} disabled={equipo.personal.length !== 4}>
                                                {equipo.codigo} · {equipo.placa} ({equipo.personal.length}/4)
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <div>
                                    <div className="mb-2 flex items-center justify-between">
                                        <p className="text-xs font-semibold text-slate-600">Incidencias</p>
                                        <span className="text-xs font-bold text-emerald-700">{form.data.reporte_ids.length} seleccionadas</span>
                                    </div>
                                    <div className="max-h-72 space-y-2 overflow-y-auto border border-slate-200 p-2">
                                        {reportes.map((reporte) => (
                                            <label key={reporte.id} className="flex cursor-pointer gap-2 border-b border-slate-100 p-2 text-xs last:border-0">
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.reporte_ids.includes(reporte.id)}
                                                    onChange={() => alternarReporte(reporte.id)}
                                                    className="mt-0.5 border-slate-300 text-emerald-600"
                                                />
                                                <span>
                                                    <span className="block font-semibold text-slate-800">#{reporte.id} · {reporte.tipo_incidencia || 'Sin clasificar'}</span>
                                                    <span className="line-clamp-2 text-slate-500">{reporte.descripcion}</span>
                                                </span>
                                            </label>
                                        ))}
                                        {!reportes.length && <p className="p-3 text-center text-xs text-slate-500">No hay incidencias disponibles.</p>}
                                    </div>
                                </div>

                                {Object.values(form.errors).map((error) => (
                                    <p key={error} className="text-xs font-medium text-red-600">
                                        {Array.isArray(error) ? error.join(' ') : error}
                                    </p>
                                ))}

                                <button
                                    type="submit"
                                    disabled={form.processing || !form.data.camion_id || !form.data.reporte_ids.length}
                                    className="w-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
                                >
                                    {form.processing ? 'Optimizando…' : 'Generar y asignar ruta'}
                                </button>
                            </form>
                        </aside>
                    </div>

                    <section id="rutas-generadas" className="scroll-mt-5 border border-slate-300 bg-white shadow-sm">
                        <div className="border-b border-slate-300 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Rutas generadas</h3>
                            <p className="text-xs text-slate-500">Historial reciente de asignaciones por vehículo.</p>
                        </div>
                        <div className="grid gap-px bg-slate-200 md:grid-cols-2 xl:grid-cols-3">
                            {rutas.map((rutaItem) => (
                                <article key={rutaItem.id} className="bg-white p-5">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="font-bold text-slate-900">{rutaItem.camion.codigo}</p>
                                            <p className="text-xs text-slate-500">{rutaItem.camion.placa} · {new Date(rutaItem.fecha).toLocaleDateString('es-PE')}</p>
                                        </div>
                                        <span className="border border-slate-300 px-2 py-1 text-[10px] font-bold">{rutaItem.estado}</span>
                                    </div>
                                    <dl className="mt-4 grid grid-cols-2 gap-3 text-xs">
                                        <div><dt className="text-slate-400">Paradas</dt><dd className="font-bold">{rutaItem.reportes.length}</dd></div>
                                        <div><dt className="text-slate-400">Distancia estimada</dt><dd className="font-bold">{rutaItem.distancia_estimada_km} km</dd></div>
                                        <div className="col-span-2">
                                            <dt className="text-slate-400">Horario</dt>
                                            <dd className="font-bold">{rutaItem.horario_resumen || 'Sin programar'}</dd>
                                        </div>
                                    </dl>
                                    <ol className="mt-4 space-y-1 text-xs text-slate-600">
                                        {rutaItem.reportes.slice(0, 5).map((reporte) => (
                                            <li key={reporte.id}>{reporte.pivot.orden}. Incidencia #{reporte.id}</li>
                                        ))}
                                    </ol>
                                    {rutaItem.estado === 'Planificada' && (
                                        <button type="button" onClick={() => cancelar(rutaItem.id)} className="mt-4 border border-red-300 px-3 py-2 text-xs font-semibold text-red-700">
                                            Cancelar ruta
                                        </button>
                                    )}
                                </article>
                            ))}
                        </div>
                        {!rutas.length && <p className="p-10 text-center text-sm text-slate-500">Aún no se generaron rutas.</p>}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
