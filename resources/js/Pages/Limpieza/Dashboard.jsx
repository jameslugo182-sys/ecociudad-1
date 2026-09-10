import FlashMessage from '@/Components/FlashMessage';
import RutaOperativaMapa from '@/Components/RutaOperativaMapa';
import TareaLimpiezaCard from '@/Components/TareaLimpiezaCard';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head } from '@inertiajs/react';

const navegacionLimpieza = [
    { label: 'Tareas asignadas', href: route('limpieza.dashboard'), active: 'limpieza.dashboard' },
    { label: 'Mi recorrido', href: route('limpieza.rutas.index'), active: 'limpieza.rutas.index' },
];

export default function Dashboard({ reportes, resumen, rutaAsignada }) {
    const activos = reportes.filter((reporte) => reporte.estado !== 'Atendido');
    const finalizados = reportes.filter((reporte) => reporte.estado === 'Atendido');

    return (
        <ModuleLayout moduleName="Operaciones de limpieza" items={navegacionLimpieza}>
            <Head title="Tareas de limpieza" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Área de limpieza y recolección</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Ruta y tareas asignadas</h2>
                    </div>
                    <FlashMessage />

                    <section className="grid gap-4 sm:grid-cols-3">
                        {[
                            ['Tareas pendientes', resumen.pendientes],
                            ['Puntos con ruta', resumen.conRuta],
                            ['Atendidos', resumen.atendidos],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p className="text-sm text-slate-500">{label}</p>
                                <p className="mt-2 text-3xl font-bold text-slate-900">{value}</p>
                            </div>
                        ))}
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                            <div>
                                <h3 className="font-bold text-slate-900">Ruta de tu flota</h3>
                                <p className="text-xs text-slate-500">
                                    {rutaAsignada
                                        ? `${rutaAsignada.camion.codigo} · ${rutaAsignada.camion.placa} · ${rutaAsignada.distancia_estimada_km} km${rutaAsignada.horario_resumen ? ` · ${rutaAsignada.horario_resumen}` : ''}`
                                        : 'Cuando te asignen un recorrido, aquí verás solo la ruta de tu vehículo.'}
                                </p>
                            </div>
                            {rutaAsignada && (
                                <a
                                    href={route('limpieza.rutas.index')}
                                    className="text-sm font-semibold text-emerald-700 hover:text-emerald-800"
                                >
                                    Ver recorrido completo
                                </a>
                            )}
                        </div>

                        {rutaAsignada ? (
                            <RutaOperativaMapa ruta={rutaAsignada} altura="h-[420px]" />
                        ) : (
                            <div className="px-5 py-16 text-center">
                                <p className="font-semibold text-slate-700">Aún no hay una ruta asignada a tu flota.</p>
                                <p className="mt-1 text-sm text-slate-500">
                                    Las incidencias individuales seguirán apareciendo en las tarjetas de abajo.
                                </p>
                            </div>
                        )}
                    </section>

                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">Recorrido pendiente</h2>
                            <p className="text-sm text-slate-500">
                                Los números indican el orden de las paradas sobre las calles.
                            </p>
                        </div>

                        {activos.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center">
                                <p className="font-semibold text-slate-700">No tienes tareas pendientes.</p>
                                <p className="mt-1 text-sm text-slate-500">Las nuevas asignaciones aparecerán aquí.</p>
                            </div>
                        ) : (
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {activos.map((reporte) => (
                                    <TareaLimpiezaCard key={reporte.id} reporte={reporte} />
                                ))}
                            </div>
                        )}
                    </section>

                    {finalizados.length > 0 && (
                        <section className="space-y-4 border-t border-slate-200 pt-7">
                            <h2 className="text-lg font-bold text-slate-900">Atenciones finalizadas</h2>
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {finalizados.map((reporte) => (
                                    <TareaLimpiezaCard key={reporte.id} reporte={reporte} />
                                ))}
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
