import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, router } from '@inertiajs/react';

function fechaCorta(valor) {
    return valor ? new Date(valor).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

export default function EnCurso({ mantenimientos }) {
    const terminar = (mantenimiento) => {
        if (window.confirm(`¿Dar por terminado el mantenimiento de ${mantenimiento.camion.codigo}? El vehículo volverá a estar disponible para asignar equipo.`)) {
            router.patch(route('administracion.vehiculos.mantenimiento.finalizar', mantenimiento.id), {}, { preserveScroll: true });
        }
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Vehículos en mantenimiento" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Mantenimiento de vehículos</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Vehículos en mantenimiento</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Unidades que ingresaron a taller. Al terminar, regresan al inventario operativo.
                        </p>
                    </div>

                    <FlashMessage />

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Vehículo</th>
                                        <th className="px-5 py-4">Tipo</th>
                                        <th className="px-5 py-4">Motivo</th>
                                        <th className="px-5 py-4">Ingreso</th>
                                        <th className="px-5 py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mantenimientos.map((mantenimiento) => (
                                        <tr key={mantenimiento.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4">
                                                <p className="font-semibold text-slate-900">
                                                    {mantenimiento.camion.codigo} · {mantenimiento.camion.placa}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {[mantenimiento.camion.marca, mantenimiento.camion.modelo].filter(Boolean).join(' ') || 'Sin marca'}
                                                </p>
                                            </td>
                                            <td className="px-5 py-4 text-slate-700">{mantenimiento.tipo.nombre}</td>
                                            <td className="max-w-sm px-5 py-4 text-slate-600">
                                                <span className="line-clamp-2">{mantenimiento.motivo}</span>
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">{fechaCorta(mantenimiento.iniciado_at)}</td>
                                            <td className="px-5 py-4">
                                                <button
                                                    type="button"
                                                    onClick={() => terminar(mantenimiento)}
                                                    className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                >
                                                    Terminar mantenimiento
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!mantenimientos.length && (
                            <div className="py-14 text-center text-sm text-slate-500">
                                No hay vehículos en mantenimiento.
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
