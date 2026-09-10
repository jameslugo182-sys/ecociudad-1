import { useForm } from '@inertiajs/react';

export default function CamionAdminCard({ camion, personalLimpieza }) {
    const conductor = camion.personal.find((persona) => persona.pivot.puesto === 'conductor');
    const recolectores = camion.personal.filter((persona) => persona.pivot.puesto === 'recolector');
    const equipo = useForm({
        conductor_id: conductor?.id || '',
        recolector_ids: [
            recolectores[0]?.id || '',
            recolectores[1]?.id || '',
            recolectores[2]?.id || '',
        ],
    });

    const guardarEquipo = (event) => {
        event.preventDefault();
        equipo.put(route('administracion.vehiculos.equipo', camion.id), {
            preserveScroll: true,
        });
    };

    const cambiarRecolector = (indice, valor) => {
        const seleccion = [...equipo.data.recolector_ids];
        seleccion[indice] = valor;
        equipo.setData('recolector_ids', seleccion);
    };

    const estaOcupado = (persona) => {
        const asignacion = persona.camiones?.[0];
        return asignacion && asignacion.id !== camion.id;
    };

    return (
        <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-slate-100 bg-slate-50 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-2xl">🚛</div>
                    <div>
                        <h2 className="text-lg font-bold text-slate-900">{camion.codigo}</h2>
                        <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">{camion.placa}</p>
                        <p className="mt-1 text-xs text-slate-500">
                            {[camion.marca, camion.modelo].filter(Boolean).join(' ') || 'Sin marca/modelo'}
                        </p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        {camion.estado}
                    </span>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                        camion.personal.length === 4
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                    }`}>
                        {camion.personal.length === 4 ? 'Completo' : `${camion.personal.length}/4`}
                    </span>
                </div>
            </div>

            <form onSubmit={guardarEquipo} className="space-y-4 p-5">
                <div>
                    <h3 className="font-bold text-slate-900">Equipo operativo</h3>
                    <p className="text-xs text-slate-500">1 conductor y 3 recolectores</p>
                </div>

                <div>
                    <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Conductor
                    </label>
                    <select
                        value={equipo.data.conductor_id}
                        onChange={(event) => equipo.setData('conductor_id', event.target.value)}
                        className="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">Seleccionar conductor</option>
                        {personalLimpieza.map((persona) => (
                            <option key={persona.id} value={persona.id} disabled={estaOcupado(persona)}>
                                {persona.name}{estaOcupado(persona) ? ` · ${persona.camiones[0].codigo}` : ''}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    {equipo.data.recolector_ids.map((recolectorId, indice) => (
                        <div key={indice}>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Recolector {indice + 1}
                            </label>
                            <select
                                value={recolectorId}
                                onChange={(event) => cambiarRecolector(indice, event.target.value)}
                                className="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Seleccionar</option>
                                {personalLimpieza.map((persona) => (
                                    <option key={persona.id} value={persona.id} disabled={estaOcupado(persona)}>
                                        {persona.name}{estaOcupado(persona) ? ` · ${persona.camiones[0].codigo}` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                    ))}
                </div>

                {Object.values(equipo.errors).map((error) => (
                    <p key={error} className="text-xs font-medium text-red-600">
                        {Array.isArray(error) ? error.join(' ') : error}
                    </p>
                ))}

                <button
                    type="submit"
                    disabled={equipo.processing}
                    className="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {equipo.processing ? 'Guardando equipo…' : 'Guardar equipo'}
                </button>
            </form>
        </article>
    );
}
