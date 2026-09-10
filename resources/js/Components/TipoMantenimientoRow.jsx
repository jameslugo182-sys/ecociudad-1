import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TipoMantenimientoRow({ tipo }) {
    const [editando, setEditando] = useState(false);
    const form = useForm({ nombre: tipo.nombre, descripcion: tipo.descripcion || '' });

    const guardar = (event) => {
        event.preventDefault();
        form.put(route('administracion.vehiculos.maestros.tipos.update', tipo.id), {
            preserveScroll: true,
            onSuccess: () => setEditando(false),
        });
    };

    const cambiarEstado = () => {
        router.patch(
            route('administracion.vehiculos.maestros.tipos.estado', tipo.id),
            { activo: !tipo.activo },
            { preserveScroll: true },
        );
    };

    return (
        <div className="rounded-xl border border-slate-200 p-4">
            {editando ? (
                <form onSubmit={guardar} className="space-y-3">
                    <input value={form.data.nombre} onChange={(event) => form.setData('nombre', event.target.value)} className="w-full rounded-lg border-slate-300 text-sm" required />
                    <textarea value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} className="w-full rounded-lg border-slate-300 text-sm" rows="2" />
                    <div className="flex gap-2">
                        <button className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Guardar</button>
                        <button type="button" onClick={() => setEditando(false)} className="rounded-lg border px-3 py-2 text-xs">Cancelar</button>
                    </div>
                </form>
            ) : (
                <>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-900">{tipo.nombre}</p>
                            <p className="mt-1 text-xs text-slate-500">{tipo.descripcion || 'Sin descripción'}</p>
                        </div>
                        <span className={`rounded-full px-2 py-1 text-[10px] font-bold ${tipo.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                            {tipo.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <p className="mt-3 text-xs text-slate-400">{tipo.mantenimientos_count} mantenimientos asociados</p>
                    <div className="mt-3 flex gap-2">
                        <button type="button" onClick={() => setEditando(true)} className="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-600">Editar</button>
                        <button type="button" onClick={cambiarEstado} className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                            {tipo.activo ? 'Desactivar' : 'Activar'}
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}
