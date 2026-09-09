import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function CuentaColaboradorCard({ cuenta, roles }) {
    const [editando, setEditando] = useState(false);
    const [cambiandoEstado, setCambiandoEstado] = useState(false);
    const form = useForm({
        email: cuenta.email,
        rol: cuenta.rol,
        password: '',
        password_confirmation: '',
    });
    const contrato = cuenta.colaborador?.contratos?.[0];

    const guardar = (event) => {
        event.preventDefault();
        form.put(route('administracion.contratos.usuarios.update', cuenta.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('password', 'password_confirmation');
                setEditando(false);
            },
        });
    };

    const cambiarEstado = () => {
        router.patch(
            route('administracion.contratos.usuarios.estado', cuenta.id),
            { activo: !cuenta.activo },
            {
                preserveScroll: true,
                onStart: () => setCambiandoEstado(true),
                onFinish: () => setCambiandoEstado(false),
            },
        );
    };

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="font-bold text-slate-900">{cuenta.colaborador?.nombre_completo || cuenta.name}</h3>
                    <p className="text-sm text-slate-500">{cuenta.email}</p>
                    <p className="mt-2 text-xs text-slate-500">
                        {contrato?.cargo?.nombre || 'Sin cargo'} · {contrato?.numero || 'Sin contrato'}
                    </p>
                </div>
                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${cuenta.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'}`}>
                    {cuenta.activo ? 'Activa' : 'Inactiva'}
                </span>
            </div>

            {editando ? (
                <form onSubmit={guardar} className="mt-5 space-y-3 border-t border-slate-100 pt-4">
                    <input
                        type="email"
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.target.value)}
                        className="w-full rounded-xl border-slate-300 text-sm"
                        required
                    />
                    <select value={form.data.rol} onChange={(event) => form.setData('rol', event.target.value)} className="w-full rounded-xl border-slate-300 text-sm">
                        {roles.map((rol) => <option key={rol.id} value={rol.codigo}>{rol.nombre}</option>)}
                    </select>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} placeholder="Nueva contraseña" className="rounded-xl border-slate-300 text-sm" />
                        <input type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} placeholder="Confirmar" className="rounded-xl border-slate-300 text-sm" />
                    </div>
                    {Object.values(form.errors).map((error) => <p key={error} className="text-xs text-red-600">{error}</p>)}
                    <div className="flex gap-2">
                        <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
                        <button type="button" onClick={() => setEditando(false)} className="rounded-xl border px-4 py-2 text-sm">Cancelar</button>
                    </div>
                </form>
            ) : (
                <div className="mt-5 flex gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onClick={() => setEditando(true)} className="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">
                        Editar acceso
                    </button>
                    <button
                        type="button"
                        onClick={cambiarEstado}
                        disabled={cambiandoEstado}
                        className={`flex-1 rounded-xl px-3 py-2 text-sm font-semibold ${cuenta.activo ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`}
                    >
                        {cuenta.activo ? 'Desactivar' : 'Activar'}
                    </button>
                </div>
            )}
        </article>
    );
}
