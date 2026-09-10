import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, Link, useForm } from '@inertiajs/react';

function fechaCorta(valor) {
    return valor ? new Date(valor).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

export default function Historial({ mantenimientos, camiones, filters }) {
    const filtro = useForm({
        camion_id: filters.camion_id || '',
    });

    const buscar = (event) => {
        event.preventDefault();
        filtro.get(route('administracion.vehiculos.mantenimiento.historial'), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Historial de mantenimientos" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Mantenimiento de vehículos</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Historial de mantenimientos</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Consulta todos los ingresos a taller por los que ha pasado cada vehículo.
                        </p>
                    </div>

                    <FlashMessage />

                    <form onSubmit={buscar} className="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto]">
                        <select
                            value={filtro.data.camion_id}
                            onChange={(event) => filtro.setData('camion_id', event.target.value)}
                            className="rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Todos los vehículos</option>
                            {camiones.map((camion) => (
                                <option key={camion.id} value={camion.id}>
                                    {camion.codigo} · {camion.placa}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white">
                            Filtrar
                        </button>
                    </form>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Vehículo</th>
                                        <th className="px-5 py-4">Tipo</th>
                                        <th className="px-5 py-4">Motivo</th>
                                        <th className="px-5 py-4">Ingreso</th>
                                        <th className="px-5 py-4">Cierre</th>
                                        <th className="px-5 py-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mantenimientos.data.map((mantenimiento) => (
                                        <tr key={mantenimiento.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4 font-semibold text-slate-900">
                                                {mantenimiento.camion.codigo} · {mantenimiento.camion.placa}
                                            </td>
                                            <td className="px-5 py-4 text-slate-700">{mantenimiento.tipo.nombre}</td>
                                            <td className="max-w-xs px-5 py-4 text-slate-600">
                                                <span className="line-clamp-2">{mantenimiento.motivo}</span>
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">{fechaCorta(mantenimiento.iniciado_at)}</td>
                                            <td className="px-5 py-4 text-slate-600">
                                                {fechaCorta(mantenimiento.finalizado_at)}
                                                {mantenimiento.finalizador?.name ? (
                                                    <span className="mt-1 block text-xs text-slate-400">{mantenimiento.finalizador.name}</span>
                                                ) : null}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    mantenimiento.estado === 'En curso'
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-emerald-100 text-emerald-800'
                                                }`}>
                                                    {mantenimiento.estado}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {!mantenimientos.data.length && (
                            <div className="py-14 text-center text-sm text-slate-500">
                                No hay mantenimientos para el filtro seleccionado.
                            </div>
                        )}

                        {mantenimientos.last_page > 1 && (
                            <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                {mantenimientos.prev_page_url && (
                                    <Link href={mantenimientos.prev_page_url} className="rounded-lg border px-3 py-2 text-sm">Anterior</Link>
                                )}
                                {mantenimientos.next_page_url && (
                                    <Link href={mantenimientos.next_page_url} className="rounded-lg border px-3 py-2 text-sm">Siguiente</Link>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
