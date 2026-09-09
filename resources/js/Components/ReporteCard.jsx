import EstadoBadge from '@/Components/EstadoBadge';

const prioridadTexto = {
    1: 'Alta',
    2: 'Media',
    3: 'Baja',
};

export default function ReporteCard({ reporte, children }) {
    return (
        <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            {reporte.foto_url && (
                <img
                    src={reporte.foto_url}
                    alt={`Incidencia ${reporte.id}`}
                    className="h-44 w-full object-cover"
                />
            )}

            <div className="space-y-4 p-5">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wider text-emerald-700">
                            Reporte #{reporte.id}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                            {new Date(reporte.created_at).toLocaleString('es-PE')}
                        </p>
                    </div>
                    <EstadoBadge estado={reporte.estado} />
                </div>

                <p className="text-sm leading-6 text-slate-700">{reporte.descripcion}</p>

                <dl className="grid grid-cols-2 gap-3 text-xs">
                    <div className="rounded-lg bg-slate-50 p-3">
                        <dt className="text-slate-500">Tipo</dt>
                        <dd className="mt-1 font-semibold text-slate-800">
                            {reporte.tipo_incidencia || 'Por clasificar'}
                        </dd>
                    </div>
                    <div className="rounded-lg bg-slate-50 p-3">
                        <dt className="text-slate-500">Prioridad</dt>
                        <dd className="mt-1 font-semibold text-slate-800">
                            {prioridadTexto[reporte.prioridad] || 'Por calcular'}
                        </dd>
                    </div>
                </dl>

                {reporte.responsable && (
                    <p className="text-xs text-slate-500">
                        Asignado a: <span className="font-semibold">{reporte.responsable.name}</span>
                    </p>
                )}

                {reporte.evidencia_url && (
                    <div>
                        <p className="mb-2 text-xs font-semibold text-emerald-700">Evidencia de atención</p>
                        <img
                            src={reporte.evidencia_url}
                            alt={`Evidencia del reporte ${reporte.id}`}
                            className="h-32 w-full rounded-xl object-cover"
                        />
                    </div>
                )}

                {children}
            </div>
        </article>
    );
}
