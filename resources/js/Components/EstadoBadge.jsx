const estilos = {
    Pendiente: 'bg-amber-100 text-amber-800 ring-amber-600/20',
    Asignado: 'bg-blue-100 text-blue-800 ring-blue-600/20',
    'En atención': 'bg-violet-100 text-violet-800 ring-violet-600/20',
    Atendido: 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
};

export default function EstadoBadge({ estado }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                estilos[estado] ?? 'bg-gray-100 text-gray-700 ring-gray-500/20'
            }`}
        >
            {estado}
        </span>
    );
}
