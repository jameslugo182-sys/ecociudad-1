import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function CamionAdminCard({ camion, personalLimpieza, estados }) {
    const [editando, setEditando] = useState(false);
    const conductor = camion.personal.find((persona) => persona.pivot.puesto === 'conductor');
    const recolectores = camion.personal.filter((persona) => persona.pivot.puesto === 'recolector');
    const datos = useForm({
        codigo: camion.codigo,
        placa: camion.placa,
        marca: camion.marca || '',
        modelo: camion.modelo || '',
        anio: camion.anio || '',
        capacidad_kg: camion.capacidad_kg || '',
        estado: camion.estado,
    });
    const equipo = useForm({
        conductor_id: conductor?.id || '',
        recolector_ids: [
            recolectores[0]?.id || '',
            recolectores[1]?.id || '',
            recolectores[2]?.id || '',
        ],
    });

    const guardarDatos = (event) => {
        event.preventDefault();
        datos.put(route('administracion.vehiculos.update', camion.id), {
            preserveScroll: true,
            onSuccess: () => setEditando(false),
        });
    };

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

    const eliminar = () => {
        if (window.confirm(`¿Eliminar el camión ${camion.codigo}?`)) {
            router.delete(route('administracion.vehiculos.destroy', camion.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-4 border-b border-slate-100 bg-slate-50 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-2xl">🚛</div>
                    <div>
                        <h2 className="text-lg font-bold text-slate-900">{camion.codigo}</h2>
                        <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">{camion.placa}</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                        {camion.estado}
                    </span>
                    <button
                        type="button"
                        onClick={() => setEditando((valor) => !valor)}
                        className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700"
                    >
                        {editando ? 'Cerrar' : 'Editar'}
                    </button>
                </div>
            </div>

            {editando && (
                <form onSubmit={guardarDatos} className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-2">
                    {[
                        ['codigo', 'Código interno'],
                        ['placa', 'Placa'],
                        ['marca', 'Marca'],
                        ['modelo', 'Modelo'],
                        ['anio', 'Año'],
                        ['capacidad_kg', 'Capacidad (kg)'],
                    ].map(([campo, placeholder]) => (
                        <input
                            key={campo}
                            type={['anio', 'capacidad_kg'].includes(campo) ? 'number' : 'text'}
                            value={datos.data[campo]}
                            onChange={(event) => datos.setData(campo, event.target.value)}
                            placeholder={placeholder}
                            className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    ))}
                    <select
                        value={datos.data.estado}
                        onChange={(event) => datos.setData('estado', event.target.value)}
                        className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        {estados.map((estado) => <option key={estado}>{estado}</option>)}
                    </select>
                    <div className="flex gap-2">
                        <button
                            type="submit"
                            disabled={datos.processing}
                            className="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Guardar
                        </button>
                        <button
                            type="button"
                            onClick={eliminar}
                            className="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700"
                        >
                            Eliminar
                        </button>
                    </div>
                    {Object.values(datos.errors).map((error) => (
                        <p key={error} className="text-xs text-red-600 sm:col-span-2">{error}</p>
                    ))}
                </form>
            )}

            <form onSubmit={guardarEquipo} className="space-y-4 p-5">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="font-bold text-slate-900">Equipo operativo</h3>
                        <p className="text-xs text-slate-500">1 conductor y 3 recolectores</p>
                    </div>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                        camion.personal.length === 4
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                    }`}>
                        {camion.personal.length === 4 ? 'Completo' : `${camion.personal.length}/4`}
                    </span>
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
