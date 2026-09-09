import AdminReporteRow from '@/Components/AdminReporteRow';
import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Dashboard({ reportes, areasLimpieza, filters, resumen, estados, routeNames }) {
    const navegacion = [
        { label: 'Listado de incidencias', href: route(routeNames.index), active: routeNames.index, icon: '01' },
        { label: 'Mapa de incidencias', href: route(routeNames.mapa), active: routeNames.mapa, icon: '02' },
    ];
    const filtros = useForm({
        estado: filters.estado || '',
        buscar: filters.buscar || '',
    });
    const filtrar = (event) => {
        event.preventDefault();
        filtros.get(route(routeNames.index), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <ModuleLayout moduleName="Incidencias registradas" items={navegacion}>
            <Head title="Incidencias registradas" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Municipalidad de El Tambo</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Incidencias registradas</h2>
                    </div>
                    <Link
                        href={route(routeNames.mapa)}
                        className="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                    >
                        Ver mapa de incidencias
                    </Link>
                    </div>
                    <FlashMessage />

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {[
                            ['Total', resumen.total, 'text-slate-900'],
                            ['Pendientes', resumen.pendientes, 'text-amber-600'],
                            ['En trabajo', resumen.asignados, 'text-blue-600'],
                            ['Atendidos', resumen.atendidos, 'text-emerald-600'],
                        ].map(([label, value, color]) => (
                            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p className="text-sm font-medium text-slate-500">{label}</p>
                                <p className={`mt-2 text-3xl font-bold ${color}`}>{value}</p>
                            </div>
                        ))}
                    </section>

                    <section>
                        <form
                            onSubmit={filtrar}
                            className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row"
                        >
                            <input
                                type="search"
                                value={filtros.data.buscar}
                                onChange={(event) => filtros.setData('buscar', event.target.value)}
                                placeholder="Buscar por ciudadano o descripción"
                                className="min-w-0 flex-1 rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            />
                            <select
                                value={filtros.data.estado}
                                onChange={(event) => filtros.setData('estado', event.target.value)}
                                className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Todos los estados</option>
                                {estados.map((estado) => (
                                    <option key={estado} value={estado}>{estado}</option>
                                ))}
                            </select>
                            <button
                                type="submit"
                                disabled={filtros.processing}
                                className="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                Filtrar
                            </button>
                        </form>

                    </section>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Incidencia</th>
                                        <th className="px-5 py-4">Ciudadano</th>
                                        <th className="px-5 py-4">Clasificación</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reportes.data.map((reporte) => (
                                        <AdminReporteRow
                                            key={reporte.id}
                                            reporte={reporte}
                                            areasLimpieza={areasLimpieza}
                                            routeNames={routeNames}
                                            mostrarAsignacion={false}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {reportes.data.length === 0 && (
                            <div className="px-6 py-14 text-center text-sm text-slate-500">
                                No se encontraron reportes con los filtros seleccionados.
                            </div>
                        )}

                        {reportes.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-sm">
                                <span className="text-slate-500">
                                    {reportes.from}–{reportes.to} de {reportes.total}
                                </span>
                                <div className="flex gap-2">
                                    {reportes.prev_page_url && (
                                        <Link
                                            href={reportes.prev_page_url}
                                            preserveScroll
                                            className="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700"
                                        >
                                            Anterior
                                        </Link>
                                    )}
                                    {reportes.next_page_url && (
                                        <Link
                                            href={reportes.next_page_url}
                                            preserveScroll
                                            className="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700"
                                        >
                                            Siguiente
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
