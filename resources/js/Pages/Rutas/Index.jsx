import FlashMessage from '@/Components/FlashMessage';
import { navegacionRutas } from '@/horarioRuta';
import { BASE_MUNICIPAL, posicionesDeRuta } from '@/rutaMapa';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
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
    const [modo, setModo] = useState('especificas');
    const [propuestas, setPropuestas] = useState([]);
    const [propuestaActiva, setPropuestaActiva] = useState(null);
    const [generando, setGenerando] = useState(false);
    const [errorPropuesta, setErrorPropuesta] = useState('');
    const form = useForm({
        camion_id: '',
        fecha: new Date().toISOString().slice(0, 10),
        reporte_ids: [],
    });
    const equiposCompletos = equipos.filter((equipo) => equipo.personal.length === 4);
    const rutasActivas = rutas.filter((rutaItem) => ['Planificada', 'En curso'].includes(rutaItem.estado));
    const propuestaSeleccionada = propuestas.find((item) => item.clave === propuestaActiva) || null;
    const colorPorIncidencia = useMemo(() => {
        const mapa = {};
        propuestas.forEach((propuesta, indice) => {
            propuesta.reporte_ids.forEach((id) => {
                mapa[id] = coloresRuta[indice % coloresRuta.length];
            });
        });
        return mapa;
    }, [propuestas]);
    const todosLosPuntos = [
        BASE_MUNICIPAL,
        ...reportes.map((reporte) => [reporte.latitud, reporte.longitud]),
        ...rutasActivas.flatMap((rutaItem) => posicionesDeRuta(rutaItem)),
        ...propuestas.flatMap((propuesta) => posicionesDeRuta(propuesta, propuesta.paradas)),
    ];

    const cambiarModo = (siguiente) => {
        setModo(siguiente);
        setPropuestas([]);
        setPropuestaActiva(null);
        setErrorPropuesta('');
        form.setData('reporte_ids', []);
        form.setData('camion_id', '');
        form.clearErrors();
    };

    const alternarReporte = (id) => {
        if (modo !== 'especificas') {
            return;
        }

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
            onSuccess: () => {
                form.reset('camion_id', 'reporte_ids');
                if (propuestaSeleccionada) {
                    setPropuestas((actuales) => actuales.filter((item) => item.clave !== propuestaSeleccionada.clave));
                    setPropuestaActiva(null);
                }
            },
        });
    };

    const generarPropuestas = async () => {
        setGenerando(true);
        setErrorPropuesta('');
        setPropuestaActiva(null);

        try {
            const respuesta = await window.axios.post(route(routeNames.proponer));
            setPropuestas(respuesta.data.propuestas || []);
        } catch (error) {
            const errores = error.response?.data?.errors || {};
            const mensaje = Object.values(errores).flat()[0]
                || error.response?.data?.message
                || 'No se pudieron generar los recorridos.';
            setErrorPropuesta(mensaje);
            setPropuestas([]);
        } finally {
            setGenerando(false);
        }
    };

    const seleccionarPropuesta = (propuesta) => {
        setPropuestaActiva(propuesta.clave);
        form.setData('reporte_ids', propuesta.reporte_ids);
        form.clearErrors();
    };

    const descartarPropuestas = () => {
        setPropuestas([]);
        setPropuestaActiva(null);
        form.setData('reporte_ids', []);
        form.setData('camion_id', '');
        setErrorPropuesta('');
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
                            Asigna incidencias específicas o genera recorridos para todas las pendientes según la flota disponible.
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
                                        {propuestas.length ? ` · ${propuestas.length} recorridos propuestos` : ''}
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

                                {propuestas.map((propuesta, indice) => {
                                    const color = coloresRuta[indice % coloresRuta.length];
                                    const posiciones = posicionesDeRuta(propuesta, propuesta.paradas);
                                    const activa = propuesta.clave === propuestaActiva;

                                    return posiciones.length >= 2 ? (
                                        <Polyline
                                            key={propuesta.clave}
                                            positions={posiciones}
                                            pathOptions={{ color, weight: activa ? 7 : 4, opacity: activa ? 0.95 : 0.7, dashArray: activa ? null : '8 6' }}
                                        />
                                    ) : null;
                                })}

                                {reportes.map((reporte) => {
                                    const colorPropuesta = colorPorIncidencia[reporte.id];
                                    const seleccionada = form.data.reporte_ids.includes(reporte.id);

                                    return (
                                        <CircleMarker
                                            key={`disponible-${reporte.id}`}
                                            center={[reporte.latitud, reporte.longitud]}
                                            radius={seleccionada || colorPropuesta ? 13 : 9}
                                            pathOptions={{
                                                color: seleccionada ? '#111827' : '#fff',
                                                weight: 3,
                                                fillColor: colorPropuesta || coloresEstado[reporte.estado] || '#d97706',
                                                fillOpacity: 1,
                                            }}
                                            eventHandlers={{ click: () => alternarReporte(reporte.id) }}
                                        >
                                            <Popup>
                                                <strong>Incidencia #{reporte.id}</strong>
                                                <p>{reporte.descripcion}</p>
                                                {modo === 'especificas' && (
                                                    <button type="button" onClick={() => alternarReporte(reporte.id)}>
                                                        {seleccionada ? 'Quitar selección' : 'Seleccionar'}
                                                    </button>
                                                )}
                                            </Popup>
                                        </CircleMarker>
                                    );
                                })}

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
                                    <p className="text-xs text-slate-500">Elige cómo quieres armar los recorridos.</p>
                                </div>

                                <div className="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1">
                                    <button
                                        type="button"
                                        onClick={() => cambiarModo('especificas')}
                                        className={`rounded-md px-2 py-2 text-xs font-semibold ${modo === 'especificas' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'}`}
                                    >
                                        Incidencias específicas
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => cambiarModo('todas')}
                                        className={`rounded-md px-2 py-2 text-xs font-semibold ${modo === 'todas' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'}`}
                                    >
                                        Todas las incidencias
                                    </button>
                                </div>

                                <label className="block text-xs font-semibold text-slate-600">
                                    Fecha de operación
                                    <input type="date" value={form.data.fecha} onChange={(event) => form.setData('fecha', event.target.value)} className="mt-1.5 w-full border-slate-300 text-sm" required />
                                </label>

                                {modo === 'especificas' ? (
                                    <>
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
                                    </>
                                ) : (
                                    <>
                                        <div className="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                            <p>
                                                Se repartirán <strong>{reportes.length}</strong> incidencias entre{' '}
                                                <strong>{equiposCompletos.length}</strong> vehículos con equipo completo.
                                            </p>
                                            <p className="mt-1 text-slate-500">
                                                No se guarda nada hasta que asignes un recorrido a un vehículo.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={generarPropuestas}
                                            disabled={generando || !reportes.length || !equiposCompletos.length}
                                            className="w-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 disabled:opacity-40"
                                        >
                                            {generando ? 'Calculando recorridos…' : 'Generar recorridos'}
                                        </button>

                                        {errorPropuesta && <p className="text-xs font-medium text-red-600">{errorPropuesta}</p>}

                                        {propuestas.length > 0 && (
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <p className="text-xs font-semibold text-slate-600">Recorridos propuestos</p>
                                                    <button type="button" onClick={descartarPropuestas} className="text-[11px] font-semibold text-red-700">
                                                        Descartar
                                                    </button>
                                                </div>
                                                <div className="max-h-52 space-y-2 overflow-y-auto">
                                                    {propuestas.map((propuesta, indice) => (
                                                        <button
                                                            key={propuesta.clave}
                                                            type="button"
                                                            onClick={() => seleccionarPropuesta(propuesta)}
                                                            className={`w-full border p-3 text-left text-xs ${propuesta.clave === propuestaActiva ? 'border-slate-900 bg-slate-50' : 'border-slate-200'}`}
                                                        >
                                                            <span className="flex items-center justify-between font-semibold text-slate-800">
                                                                Recorrido {propuesta.indice}
                                                                <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: coloresRuta[indice % coloresRuta.length] }} />
                                                            </span>
                                                            <span className="mt-1 block text-slate-500">
                                                                {propuesta.paradas.length} paradas · {propuesta.distancia_km} km
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {propuestaSeleccionada && (
                                            <>
                                                <ol className="max-h-28 space-y-1 overflow-y-auto text-xs text-slate-600">
                                                    {propuestaSeleccionada.paradas.map((parada) => (
                                                        <li key={parada.id}>{parada.orden}. Incidencia #{parada.id}</li>
                                                    ))}
                                                </ol>
                                                <label className="block text-xs font-semibold text-slate-600">
                                                    Asignar a vehículo
                                                    <select value={form.data.camion_id} onChange={(event) => form.setData('camion_id', event.target.value)} className="mt-1.5 w-full border-slate-300 text-sm" required>
                                                        <option value="">Seleccionar vehículo</option>
                                                        {equipos.map((equipo) => (
                                                            <option key={equipo.id} value={equipo.id} disabled={equipo.personal.length !== 4}>
                                                                {equipo.codigo} · {equipo.placa} ({equipo.personal.length}/4)
                                                            </option>
                                                        ))}
                                                    </select>
                                                </label>
                                            </>
                                        )}
                                    </>
                                )}

                                {Object.values(form.errors).map((error) => (
                                    <p key={error} className="text-xs font-medium text-red-600">
                                        {Array.isArray(error) ? error.join(' ') : error}
                                    </p>
                                ))}

                                {(modo === 'especificas' || propuestaSeleccionada) && (
                                    <button
                                        type="submit"
                                        disabled={form.processing || !form.data.camion_id || !form.data.reporte_ids.length}
                                        className="w-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
                                    >
                                        {form.processing
                                            ? 'Optimizando…'
                                            : (modo === 'todas' ? 'Asignar recorrido al vehículo' : 'Generar y asignar ruta')}
                                    </button>
                                )}
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
