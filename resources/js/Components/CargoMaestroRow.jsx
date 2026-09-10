import { router } from '@inertiajs/react';

export default function CargoMaestroRow({ cargo, onEditar }) {
    const cambiarEstado = () => {
        router.patch(
            route('administracion.contratos.maestros.cargos.estado', cargo.id),
            { activo: !cargo.activo },
            { preserveScroll: true },
        );
    };

    return (
        <tr className="border-t border-slate-100">
            <td className="px-5 py-3 font-semibold text-slate-800">{cargo.nombre}</td>
            <td className="px-5 py-3 text-slate-500">{cargo.descripcion || '—'}</td>
            <td className="px-5 py-3 text-slate-600">{cargo.contratos_count}</td>
            <td className="px-5 py-3">
                <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold ${cargo.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                    {cargo.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td className="px-5 py-3">
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => onEditar(cargo)} className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                        Editar
                    </button>
                    <button type="button" onClick={cambiarEstado} className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                        {cargo.activo ? 'Desactivar' : 'Activar'}
                    </button>
                </div>
            </td>
        </tr>
    );
}
