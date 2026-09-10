import { router } from '@inertiajs/react';

export default function RolMaestroCard({ rol, onEditar }) {
    const cambiarEstado = () => {
        router.patch(
            route('administracion.contratos.maestros.roles.estado', rol.id),
            { activo: !rol.activo },
            { preserveScroll: true },
        );
    };

    return (
        <tr className="border-t border-slate-100">
            <td className="px-5 py-3">
                <p className="font-semibold text-slate-800">{rol.nombre}</p>
                <p className="text-xs text-slate-500">{rol.descripcion || '—'}</p>
            </td>
            <td className="px-5 py-3">
                <code className="text-xs text-emerald-700">{rol.codigo}</code>
            </td>
            <td className="px-5 py-3">
                <div className="flex flex-wrap gap-1.5">
                    {rol.modulos.map((modulo) => (
                        <span key={modulo} className="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">{modulo}</span>
                    ))}
                </div>
            </td>
            <td className="px-5 py-3">
                <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold ${rol.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                    {rol.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td className="px-5 py-3">
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => onEditar(rol)} className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                        Editar
                    </button>
                    {!rol.protegido && (
                        <button type="button" onClick={cambiarEstado} className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                            {rol.activo ? 'Desactivar' : 'Activar'}
                        </button>
                    )}
                </div>
            </td>
        </tr>
    );
}
