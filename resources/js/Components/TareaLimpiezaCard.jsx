import EstadoBadge from '@/Components/EstadoBadge';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TareaLimpiezaCard({ reporte }) {
    const evidencia = useForm({ evidencia: null });
    const [iniciando, setIniciando] = useState(false);
    const finalizado = reporte.estado === 'Atendido';

    const iniciar = () => {
        router.patch(route('limpieza.reportes.iniciar', reporte.id), {}, {
            preserveScroll: true,
            onStart: () => setIniciando(true),
            onFinish: () => setIniciando(false),
        });
    };

    const cerrar = (event) => {
        event.preventDefault();
        evidencia.post(route('limpieza.reportes.evidencia', reporte.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => evidencia.reset(),
        });
    };

    return (
        <article className={`overflow-hidden rounded-2xl border bg-white shadow-sm ${finalizado ? 'border-slate-200 opacity-80' : 'border-emerald-200'}`}>
            <div className="relative">
                {reporte.foto_url && (
                    <img
                        src={reporte.foto_url}
                        alt={`Incidencia ${reporte.id}`}
                        className="h-48 w-full object-cover"
                    />
                )}
                {reporte.ruta_orden && !finalizado && (
                    <span className="absolute left-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white shadow-lg">
                        {reporte.ruta_orden}
                    </span>
                )}
            </div>

            <div className="space-y-4 p-5">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                            Tarea #{reporte.id}
                        </p>
                        <h2 className="mt-1 font-bold text-slate-900">
                            {reporte.tipo_incidencia || 'Incidencia de residuos'}
                        </h2>
                    </div>
                    <EstadoBadge estado={reporte.estado} />
                </div>

                <p className="text-sm leading-6 text-slate-600">{reporte.descripcion}</p>

                <div className="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                    <p>Reportado por <span className="font-semibold">{reporte.user?.name}</span></p>
                    <p className="mt-1 font-mono">📍 {reporte.latitud}, {reporte.longitud}</p>
                </div>

                <a
                    href={`https://www.google.com/maps/dir/?api=1&destination=${reporte.latitud},${reporte.longitud}`}
                    target="_blank"
                    rel="noreferrer"
                    className="flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Abrir navegación GPS
                </a>

                {!finalizado && (
                    <>
                        {reporte.estado === 'Asignado' && (
                            <button
                                type="button"
                                onClick={iniciar}
                                disabled={iniciando}
                                className="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                Iniciar atención
                            </button>
                        )}

                        <form onSubmit={cerrar} className="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <label className="block text-sm font-semibold text-emerald-900">
                                Evidencia de limpieza
                            </label>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                capture="environment"
                                onChange={(event) => evidencia.setData('evidencia', event.target.files[0])}
                                className="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:font-semibold file:text-emerald-700"
                            />
                            {evidencia.errors.evidencia && (
                                <p className="text-xs font-medium text-red-600">{evidencia.errors.evidencia}</p>
                            )}
                            <button
                                type="submit"
                                disabled={evidencia.processing || !evidencia.data.evidencia}
                                className="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40"
                            >
                                {evidencia.processing ? 'Subiendo evidencia…' : 'Registrar evidencia y cerrar'}
                            </button>
                        </form>
                    </>
                )}

                {finalizado && reporte.evidencia_url && (
                    <div>
                        <p className="mb-2 text-xs font-semibold text-emerald-700">Evidencia registrada</p>
                        <img
                            src={reporte.evidencia_url}
                            alt={`Evidencia del reporte ${reporte.id}`}
                            className="h-36 w-full rounded-xl object-cover"
                        />
                    </div>
                )}
            </div>
        </article>
    );
}
