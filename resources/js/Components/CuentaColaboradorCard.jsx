import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function CuentaColaboradorCard({ cuenta, roles, onEditar }) {
    const [cambiandoEstado, setCambiandoEstado] = useState(false);
    const contrato = cuenta.colaborador?.contratos?.[0];
    const rol = roles.find((item) => item.codigo === cuenta.rol);

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
        <tr className="border-t border-slate-100">
            <td className="px-5 py-3">
                <p className="font-semibold text-slate-800">{cuenta.colaborador?.nombre_completo || cuenta.name}</p>
                <p className="text-xs text-slate-500">{cuenta.colaborador?.documento || '—'}</p>
            </td>
            <td className="px-5 py-3 text-slate-600">{cuenta.email}</td>
            <td className="px-5 py-3 text-slate-700">{rol?.nombre || cuenta.rol}</td>
            <td className="px-5 py-3 text-xs text-slate-600">
                <p className="font-medium text-slate-700">{contrato?.cargo?.nombre || 'Sin cargo'}</p>
                <p>{contrato?.numero || 'Sin contrato'}</p>
            </td>
            <td className="px-5 py-3">
                <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold ${cuenta.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}`}>
                    {cuenta.activo ? 'Activa' : 'Inactiva'}
                </span>
            </td>
            <td className="px-5 py-3">
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => onEditar(cuenta)} className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                        Editar
                    </button>
                    <button
                        type="button"
                        onClick={cambiarEstado}
                        disabled={cambiandoEstado}
                        className={`rounded-lg px-3 py-2 text-xs font-semibold ${cuenta.activo ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`}
                    >
                        {cuenta.activo ? 'Desactivar' : 'Activar'}
                    </button>
                </div>
            </td>
        </tr>
    );
}
