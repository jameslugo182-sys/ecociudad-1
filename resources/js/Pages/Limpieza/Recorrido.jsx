import FlashMessage from '@/Components/FlashMessage';
import RutaOperativaMapa from '@/Components/RutaOperativaMapa';
import { BASE_MUNICIPAL } from '@/rutaMapa';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head } from '@inertiajs/react';

const navegacionLimpieza = [
    { label: 'Tareas asignadas', href: route('limpieza.dashboard'), active: 'limpieza.dashboard' },
    { label: 'Mi recorrido', href: route('limpieza.rutas.index'), active: 'limpieza.rutas.index' },
];

function enlaceGoogleMaps(ruta) {
    const paradas = [...(ruta?.reportes ?? [])].sort(
        (a, b) => (a.pivot?.orden ?? a.ruta_orden ?? 0) - (b.pivot?.orden ?? b.ruta_orden ?? 0),
    );

    if (!paradas.length) {
        return null;
    }

    const origen = `${BASE_MUNICIPAL[0]},${BASE_MUNICIPAL[1]}`;
    const destino = `${paradas[paradas.length - 1].latitud},${paradas[paradas.length - 1].longitud}`;
    const intermedias = paradas
        .slice(0, -1)
        .map((parada) => `${parada.latitud},${parada.longitud}`)
        .join('|');
    const url = new URL('https://www.google.com/maps/dir/');

    url.searchParams.set('api', '1');
    url.searchParams.set('origin', origen);
    url.searchParams.set('destination', destino);
    url.searchParams.set('travelmode', 'driving');

    if (intermedias) {
        url.searchParams.set('waypoints', intermedias);
    }

    return url.toString();
}

export default function Recorrido({ rutaAsignada }) {
    const googleMaps = rutaAsignada ? enlaceGoogleMaps(rutaAsignada) : null;
    const paradas = [...(rutaAsignada?.reportes ?? [])].sort(
        (a, b) => (a.pivot?.orden ?? a.ruta_orden ?? 0) - (b.pivot?.orden ?? b.ruta_orden ?? 0),
    );

    return (
        <ModuleLayout moduleName="Operaciones de limpieza" items={navegacionLimpieza}>
            <Head title="Mi recorrido" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Área de limpieza y recolección</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Mi recorrido</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Solo ves la ruta asignada a la flota a la que perteneces, trazada por las calles.
                            </p>
                        </div>
                        {googleMaps && (
                            <a
                                href={googleMaps}
                                target="_blank"
                                rel="noreferrer"
                                className="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Abrir en Google Maps
                            </a>
                        )}
                    </div>

                    <FlashMessage />

                    {!rutaAsignada ? (
                        <section className="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                            <p className="font-semibold text-slate-700">Tu flota no tiene una ruta asignada.</p>
                            <p className="mt-1 text-sm text-slate-500">
                                Cuando administración genere el recorrido, aparecerá aquí.
                            </p>
                        </section>
                    ) : (
                        <div className="grid gap-5 xl:grid-cols-[1fr_320px]">
                            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div className="border-b border-slate-200 px-5 py-4">
                                    <h3 className="font-bold text-slate-900">
                                        {rutaAsignada.camion.codigo} · {rutaAsignada.camion.placa}
                                    </h3>
                                    <p className="text-xs text-slate-500">
                                        {rutaAsignada.estado} · {rutaAsignada.distancia_estimada_km} km estimados · {paradas.length} paradas
                                        {rutaAsignada.horario_resumen ? ` · ${rutaAsignada.horario_resumen}` : ''}
                                    </p>
                                </div>
                                <RutaOperativaMapa ruta={rutaAsignada} altura="h-[70vh] min-h-[420px]" />
                            </section>

                            <aside className="space-y-4">
                                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h3 className="font-bold text-slate-900">Horario de recolección</h3>
                                    {rutaAsignada.horario_resumen ? (
                                        <>
                                            <p className="mt-3 text-lg font-bold text-slate-900">{rutaAsignada.horario_resumen}</p>
                                            {rutaAsignada.horario_nota && (
                                                <p className="mt-2 text-sm text-slate-600">{rutaAsignada.horario_nota}</p>
                                            )}
                                        </>
                                    ) : (
                                        <p className="mt-3 text-sm text-slate-500">
                                            Administración aún no programó el horario de esta ruta.
                                        </p>
                                    )}
                                </section>

                                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h3 className="font-bold text-slate-900">Equipo</h3>
                                    <ul className="mt-3 space-y-2 text-sm text-slate-600">
                                        {rutaAsignada.camion.personal.map((persona) => (
                                            <li key={persona.id}>
                                                <span className="font-semibold text-slate-800">{persona.name}</span>
                                                <span className="ml-2 text-xs uppercase text-slate-400">
                                                    {persona.pivot?.puesto}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </section>

                                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h3 className="font-bold text-slate-900">Orden de paradas</h3>
                                    <ol className="mt-3 space-y-3">
                                        {paradas.map((reporte) => (
                                            <li key={reporte.id} className="flex gap-3 text-sm">
                                                <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">
                                                    {reporte.pivot?.orden ?? reporte.ruta_orden}
                                                </span>
                                                <span>
                                                    <span className="block font-semibold text-slate-800">
                                                        Incidencia #{reporte.id}
                                                    </span>
                                                    <span className="line-clamp-2 text-xs text-slate-500">
                                                        {reporte.descripcion}
                                                    </span>
                                                    <span className="mt-1 block text-[11px] font-medium uppercase text-slate-400">
                                                        {reporte.estado}
                                                    </span>
                                                </span>
                                            </li>
                                        ))}
                                    </ol>
                                </section>
                            </aside>
                        </div>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
