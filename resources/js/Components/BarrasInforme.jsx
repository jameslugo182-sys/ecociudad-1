export default function BarrasInforme({ titulo, datos = [], color = 'bg-emerald-500' }) {
    const maximo = Math.max(...datos.map((item) => item.total), 1);

    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="font-bold text-slate-900">{titulo}</h2>
            <div className="mt-6 space-y-4">
                {datos.length === 0 ? (
                    <p className="text-sm text-slate-500">Sin datos en el periodo seleccionado.</p>
                ) : (
                    datos.map((item) => (
                        <div key={item.label}>
                            <div className="mb-1.5 flex items-center justify-between gap-4 text-xs">
                                <span className="truncate font-medium text-slate-600">{item.label}</span>
                                <span className="font-bold text-slate-900">{item.total}</span>
                            </div>
                            <div className="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full rounded-full ${color}`}
                                    style={{ width: `${(item.total / maximo) * 100}%` }}
                                />
                            </div>
                        </div>
                    ))
                )}
            </div>
        </section>
    );
}
