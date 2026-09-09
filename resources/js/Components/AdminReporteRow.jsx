import EstadoBadge from '@/Components/EstadoBadge';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const prioridades = {
    1: { texto: 'Alta', clase: 'text-red-700 bg-red-50' },
    2: { texto: 'Media', clase: 'text-amber-700 bg-amber-50' },
    3: { texto: 'Baja', clase: 'text-sky-700 bg-sky-50' },
};

export default function AdminReporteRow({
    reporte,
    areasLimpieza,
    routeNames,
    mostrarAsignacion = true,
}) {
    const asignacion = useForm({
        assigned_to: reporte.assigned_to || '',
    });
    const [actualizandoEstado, setActualizandoEstado] = useState(false);
    const prioridad = prioridades[reporte.prioridad];

    const asignar = (event) => {
        event.preventDefault();
        asignacion.patch(route(routeNames.asignar, reporte.id), {
            preserveScroll: true,
        });
    };

    const actualizarEstado = (nuevoEstado) => {
        router.patch(route(routeNames.estado, reporte.id), { estado: nuevoEstado }, {
            preserveScroll: true,
            onStart: () => setActualizandoEstado(true),
            onFinish: () => setActualizandoEstado(false),
        });
    };

    return (
        <tr className="border-b border-slate-100 align-top last:border-0">
            <td className="px-5 py-4">
                <div className="flex min-w-64 gap-3">
                    {reporte.foto_url && (
                        <img
                            src={reporte.foto_url}
                            alt=""
                            className="h-16 w-16 shrink-0 rounded-xl object-cover"
                        />
                    )}
                    <div>
                        <p className="font-bold text-slate-900">#{reporte.id}</p>
                        <p className="mt-1 line-clamp-2 text-sm text-slate-600">{reporte.descripcion}</p>
                        <p className="mt-1 text-xs text-slate-400">
                            {new Date(reporte.created_at).toLocaleString('es-PE')}
                        </p>
                    </div>
                </div>
            </td>
            <td className="px-5 py-4 text-sm">
                <p className="font-semibold text-slate-800">{reporte.user?.name}</p>
                <p className="text-xs text-slate-500">{reporte.user?.email}</p>
            </td>
            <td className="px-5 py-4">
                <p className="text-sm text-slate-700">{reporte.tipo_incidencia || 'Por clasificar'}</p>
                <span
                    className={`mt-2 inline-flex rounded-lg px-2 py-1 text-xs font-semibold ${
                        prioridad?.clase || 'bg-slate-100 text-slate-500'
                    }`}
                >
                    {prioridad?.texto || 'Sin prioridad'}
                </span>
            </td>
            <td className="px-5 py-4">
                <EstadoBadge estado={reporte.estado} />
            </td>
            {mostrarAsignacion && (
                <td className="px-5 py-4">
                    <form onSubmit={asignar} className="flex min-w-56 gap-2">
                        <select
                            value={asignacion.data.assigned_to}
                            onChange={(event) => asignacion.setData('assigned_to', event.target.value)}
                            className="w-full rounded-lg border-slate-300 py-2 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                            aria-label={`Responsable del reporte ${reporte.id}`}
                        >
                            <option value="">Seleccionar responsable</option>
                            {areasLimpieza.map((area) => (
                                <option key={area.id} value={area.id}>
                                    {area.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            disabled={asignacion.processing || !asignacion.data.assigned_to}
                            className="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white disabled:opacity-40"
                        >
                            Asignar
                        </button>
                    </form>
                    {asignacion.errors.assigned_to && (
                        <p className="mt-1 text-xs text-red-600">{asignacion.errors.assigned_to}</p>
                    )}
                </td>
            )}
            <td className="px-5 py-4">
                <div className="flex min-w-32 flex-col gap-2">
                    {reporte.estado !== 'Atendido' && (
                        <button
                            type="button"
                            onClick={() => actualizarEstado('Atendido')}
                            disabled={actualizandoEstado}
                            className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-40"
                        >
                            Marcar atendido
                        </button>
                    )}
                    <a
                        href={`https://www.openstreetmap.org/?mlat=${reporte.latitud}&mlon=${reporte.longitud}#map=18/${reporte.latitud}/${reporte.longitud}`}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Ver ubicación
                    </a>
                </div>
            </td>
        </tr>
    );
}
