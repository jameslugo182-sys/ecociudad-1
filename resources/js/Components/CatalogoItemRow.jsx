import { router } from '@inertiajs/react';

export default function CatalogoItemRow({ item, routeNames, conteoCampo = 'contratos_count', onEditar }) {
    const cambiarEstado = () => {
        router.patch(route(routeNames.estado, item.id), { activo: !item.activo }, { preserveScroll: true });
    };

    return (
        <tr className="border-t border-slate-100">
            <td className="px-5 py-3 font-semibold text-slate-800">{item.nombre}</td>
            <td className="px-5 py-3 text-slate-500">{item.descripcion || '—'}</td>
            <td className="px-5 py-3 text-slate-600">{item[conteoCampo] ?? 0}</td>
            <td className="px-5 py-3">
                <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold ${item.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                    {item.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td className="px-5 py-3">
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => onEditar(item)} className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                        Editar
                    </button>
                    <button type="button" onClick={cambiarEstado} className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">
                        {item.activo ? 'Desactivar' : 'Activar'}
                    </button>
                </div>
            </td>
        </tr>
    );
}
