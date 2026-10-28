import { useForm } from '@inertiajs/react';

export default function InformeCabecera({
    titulo,
    descripcion,
    periodo,
    rutaFiltro,
    pdfUrl,
    estados = [],
}) {
    const filtro = useForm({
        fecha_desde: periodo.fecha_desde,
        fecha_hasta: periodo.fecha_hasta,
        estado: periodo.estado || '',
    });

    const aplicar = (event) => {
        event.preventDefault();
        filtro.get(rutaFiltro, { preserveScroll: true });
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p className="text-sm font-semibold text-emerald-700">Informe operativo</p>
                    <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">{titulo}</h2>
                    <p className="mt-1 text-sm text-slate-500">{descripcion} Periodo {periodo.etiqueta}.</p>
                </div>
                <a
                    href={pdfUrl}
                    className="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Descargar PDF
                </a>
            </div>

            <form
                onSubmit={aplicar}
                className={`grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ${estados.length ? 'md:grid-cols-[1fr_1fr_200px_auto]' : 'md:grid-cols-[1fr_1fr_auto]'}`}
            >
                <label className="text-xs font-semibold text-slate-600">
                    <span>Desde</span>
                    <input
                        type="date"
                        value={filtro.data.fecha_desde}
                        onChange={(event) => filtro.setData('fecha_desde', event.target.value)}
                        className="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </label>
                <label className="text-xs font-semibold text-slate-600">
                    <span>Hasta</span>
                    <input
                        type="date"
                        value={filtro.data.fecha_hasta}
                        onChange={(event) => filtro.setData('fecha_hasta', event.target.value)}
                        className="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </label>
                {estados.length > 0 && (
                    <label className="text-xs font-semibold text-slate-600">
                        <span>Estado</span>
                        <select
                            value={filtro.data.estado}
                            onChange={(event) => filtro.setData('estado', event.target.value)}
                            className="mt-1 w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Todos</option>
                            {estados.map((estado) => (
                                <option key={estado} value={estado}>{estado}</option>
                            ))}
                        </select>
                    </label>
                )}
                <button type="submit" className="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 md:self-end">
                    Filtrar
                </button>
            </form>
            {filtro.errors.fecha_hasta && (
                <p className="text-sm font-medium text-red-600">{filtro.errors.fecha_hasta}</p>
            )}
        </div>
    );
}

function Kpi({ label, value, nota }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-medium leading-5 text-slate-500">{label}</p>
            <p className="mt-2 text-2xl font-extrabold text-slate-900">{value}</p>
            {nota && <p className="mt-1 text-[11px] text-slate-400">{nota}</p>}
        </div>
    );
}

InformeCabecera.Kpi = Kpi;
