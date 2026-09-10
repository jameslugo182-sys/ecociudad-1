import FlashMessage from '@/Components/FlashMessage';
import { DIAS_RECOLECCION, horaCorta, navegacionRutas } from '@/horarioRuta';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, useForm } from '@inertiajs/react';

function HorarioRutaForm({ ruta, routeName }) {
    const form = useForm({
        hora_inicio: horaCorta(ruta.hora_inicio) || '07:00',
        hora_fin: horaCorta(ruta.hora_fin) || '12:00',
        dias_recoleccion: ruta.dias_recoleccion?.length ? ruta.dias_recoleccion : [1, 2, 3, 4, 5],
        horario_nota: ruta.horario_nota || '',
    });

    const alternarDia = (dia) => {
        form.setData(
            'dias_recoleccion',
            form.data.dias_recoleccion.includes(dia)
                ? form.data.dias_recoleccion.filter((actual) => actual !== dia)
                : [...form.data.dias_recoleccion, dia].sort((a, b) => a - b),
        );
    };

    const guardar = (event) => {
        event.preventDefault();
        form.patch(route(routeName, ruta.id), { preserveScroll: true });
    };

    return (
        <form onSubmit={guardar} className="space-y-4">
            <div>
                <p className="mb-2 text-xs font-semibold text-slate-600">Días de recolección</p>
                <div className="flex flex-wrap gap-2">
                    {DIAS_RECOLECCION.map((dia) => {
                        const activo = form.data.dias_recoleccion.includes(dia.valor);

                        return (
                            <button
                                key={dia.valor}
                                type="button"
                                onClick={() => alternarDia(dia.valor)}
                                className={`min-w-12 px-3 py-2 text-xs font-semibold ${
                                    activo
                                        ? 'bg-emerald-600 text-white'
                                        : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
                                }`}
                            >
                                {dia.corto}
                            </button>
                        );
                    })}
                </div>
                {form.errors.dias_recoleccion && (
                    <p className="mt-2 text-xs font-medium text-red-600">{form.errors.dias_recoleccion}</p>
                )}
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                <label className="block text-xs font-semibold text-slate-600">
                    Hora de salida
                    <input
                        type="time"
                        value={form.data.hora_inicio}
                        onChange={(event) => form.setData('hora_inicio', event.target.value)}
                        className="mt-1.5 w-full border-slate-300 text-sm"
                        required
                    />
                    {form.errors.hora_inicio && (
                        <span className="mt-1 block font-medium text-red-600">{form.errors.hora_inicio}</span>
                    )}
                </label>
                <label className="block text-xs font-semibold text-slate-600">
                    Hora de retorno
                    <input
                        type="time"
                        value={form.data.hora_fin}
                        onChange={(event) => form.setData('hora_fin', event.target.value)}
                        className="mt-1.5 w-full border-slate-300 text-sm"
                        required
                    />
                    {form.errors.hora_fin && (
                        <span className="mt-1 block font-medium text-red-600">{form.errors.hora_fin}</span>
                    )}
                </label>
            </div>

            <label className="block text-xs font-semibold text-slate-600">
                Nota operativa
                <input
                    type="text"
                    value={form.data.horario_nota}
                    onChange={(event) => form.setData('horario_nota', event.target.value)}
                    maxLength={160}
                    placeholder="Ej. Recolección de residuos domiciliarios"
                    className="mt-1.5 w-full border-slate-300 text-sm"
                />
                {form.errors.horario_nota && (
                    <span className="mt-1 block font-medium text-red-600">{form.errors.horario_nota}</span>
                )}
            </label>

            <button
                type="submit"
                disabled={form.processing || !form.data.dias_recoleccion.length}
                className="w-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40"
            >
                {form.processing ? 'Guardando…' : ruta.horario_resumen ? 'Actualizar horario' : 'Asignar horario'}
            </button>
        </form>
    );
}

export default function Horarios({ rutas, routeNames }) {
    return (
        <ModuleLayout moduleName="Gestión de rutas" items={navegacionRutas(routeNames)}>
            <Head title="Horarios de recolección" />

            <div className="min-h-screen bg-slate-50 px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Planificación operativa</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Horarios de recolección</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Programa los días y horas de salida de cada flota a partir de la ruta que ya tiene asignada.
                        </p>
                    </div>

                    <FlashMessage />

                    {!rutas.length ? (
                        <section className="border border-dashed border-slate-300 bg-white py-16 text-center">
                            <p className="font-semibold text-slate-700">No hay flotas con una ruta asignada.</p>
                            <p className="mt-1 text-sm text-slate-500">
                                Primero genera y asigna una ruta; después podrás definir su horario de recolección.
                            </p>
                            <a
                                href={route(routeNames.index)}
                                className="mt-5 inline-block bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                Ir a planificación de rutas
                            </a>
                        </section>
                    ) : (
                        <div className="grid gap-5 xl:grid-cols-2">
                            {rutas.map((ruta) => (
                                <article key={ruta.id} className="border border-slate-300 bg-white p-5 shadow-sm">
                                    <div className="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                                        <div>
                                            <p className="font-bold text-slate-900">
                                                {ruta.camion.codigo} · {ruta.camion.placa}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {ruta.estado} · {ruta.reportes.length} paradas · {ruta.distancia_estimada_km} km
                                            </p>
                                        </div>
                                        <span className={`px-2 py-1 text-[10px] font-bold ${
                                            ruta.horario_resumen
                                                ? 'bg-emerald-50 text-emerald-800'
                                                : 'border border-amber-300 text-amber-800'
                                        }`}>
                                            {ruta.horario_resumen || 'Sin horario'}
                                        </span>
                                    </div>

                                    <ul className="mt-4 space-y-1 text-xs text-slate-600">
                                        {ruta.camion.personal.map((persona) => (
                                            <li key={persona.id}>
                                                <span className="font-semibold text-slate-800">{persona.name}</span>
                                                <span className="ml-2 uppercase text-slate-400">{persona.pivot?.puesto}</span>
                                            </li>
                                        ))}
                                    </ul>

                                    <ol className="mt-4 space-y-1 border-y border-slate-100 py-3 text-xs text-slate-600">
                                        {ruta.reportes.slice(0, 4).map((reporte) => (
                                            <li key={reporte.id}>
                                                {reporte.pivot?.orden ?? reporte.ruta_orden}. Incidencia #{reporte.id}
                                            </li>
                                        ))}
                                    </ol>

                                    <div className="mt-4">
                                        <HorarioRutaForm ruta={ruta} routeName={routeNames.horario} />
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
