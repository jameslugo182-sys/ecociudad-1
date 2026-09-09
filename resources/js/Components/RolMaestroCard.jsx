import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function RolMaestroCard({ rol, modulos }) {
    const [editando, setEditando] = useState(false);
    const form = useForm({
        nombre: rol.nombre,
        codigo: rol.codigo,
        descripcion: rol.descripcion || '',
        modulos: rol.modulos || [],
    });

    const alternarModulo = (modulo) => {
        form.setData(
            'modulos',
            form.data.modulos.includes(modulo)
                ? form.data.modulos.filter((item) => item !== modulo)
                : [...form.data.modulos, modulo],
        );
    };

    const guardar = (event) => {
        event.preventDefault();
        form.put(route('administracion.contratos.maestros.roles.update', rol.id), {
            preserveScroll: true,
            onSuccess: () => setEditando(false),
        });
    };

    const cambiarEstado = () => {
        router.patch(
            route('administracion.contratos.maestros.roles.estado', rol.id),
            { activo: !rol.activo },
            { preserveScroll: true },
        );
    };

    return (
        <article className="rounded-xl border border-slate-200 p-4">
            {editando ? (
                <form onSubmit={guardar} className="space-y-3">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <input value={form.data.nombre} onChange={(event) => form.setData('nombre', event.target.value)} className="rounded-lg border-slate-300 text-sm" required />
                        <input value={form.data.codigo} onChange={(event) => form.setData('codigo', event.target.value)} disabled={rol.protegido} className="rounded-lg border-slate-300 text-sm disabled:bg-slate-100" required />
                    </div>
                    <textarea value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} className="w-full rounded-lg border-slate-300 text-sm" rows="2" />
                    <div className="grid gap-2 sm:grid-cols-2">
                        {modulos.map((modulo) => (
                            <label key={modulo.value} className="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" checked={form.data.modulos.includes(modulo.value)} onChange={() => alternarModulo(modulo.value)} className="rounded border-slate-300 text-emerald-600" />
                                {modulo.label}
                            </label>
                        ))}
                    </div>
                    {Object.values(form.errors).map((error) => <p key={error} className="text-xs text-red-600">{error}</p>)}
                    <div className="flex gap-2">
                        <button className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Guardar</button>
                        <button type="button" onClick={() => setEditando(false)} className="rounded-lg border px-3 py-2 text-xs">Cancelar</button>
                    </div>
                </form>
            ) : (
                <>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-900">{rol.nombre}</p>
                            <code className="text-xs text-emerald-700">{rol.codigo}</code>
                        </div>
                        <span className={`rounded-full px-2 py-1 text-[10px] font-bold ${rol.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                            {rol.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <p className="mt-2 text-xs text-slate-500">{rol.descripcion}</p>
                    <div className="mt-3 flex flex-wrap gap-1.5">
                        {rol.modulos.map((modulo) => (
                            <span key={modulo} className="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">{modulo}</span>
                        ))}
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button type="button" onClick={() => setEditando(true)} className="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-600">Editar</button>
                        {!rol.protegido && (
                            <button type="button" onClick={cambiarEstado} className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                {rol.activo ? 'Desactivar' : 'Activar'}
                            </button>
                        )}
                    </div>
                </>
            )}
        </article>
    );
}
