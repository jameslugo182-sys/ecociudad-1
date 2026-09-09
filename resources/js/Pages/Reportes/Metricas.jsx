import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head } from '@inertiajs/react';

function Barras({ titulo, datos, color = 'bg-emerald-500' }) {
    const maximo = Math.max(...datos.map((item) => item.total), 1);

    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="font-bold text-slate-900">{titulo}</h2>
            <div className="mt-6 space-y-4">
                {datos.map((item) => (
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
                ))}
            </div>
        </section>
    );
}

export default function Metricas({ resumen, porEstado, porTipo, porMes }) {
    const rutaActual = route().current();
    const navegacion = [
        { label: 'Resumen general', href: route(rutaActual), active: rutaActual, icon: '01' },
        { label: 'Por estado', href: `${route(rutaActual)}#estados`, hash: '#estados', icon: '02' },
        { label: 'Por categoría', href: `${route(rutaActual)}#categorias`, hash: '#categorias', icon: '03' },
    ];
    const indicadores = [
        ['Incidencias totales', resumen.total],
        ['Incidencias atendidas', resumen.atendidos],
        ['Tasa de atención', `${resumen.tasaAtencion}%`],
        ['Tiempo promedio', `${resumen.promedioHoras} h`],
        ['Personal activo', resumen.personalActivo],
        ['Vehículos disponibles', resumen.vehiculosDisponibles],
    ];

    return (
        <ModuleLayout moduleName="Reportes" items={navegacion}>
            <Head title="Reportes" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Análisis operativo</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Reportes y métricas</h2>
                    </div>
                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                        {indicadores.map(([label, value]) => (
                            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p className="text-xs font-medium leading-5 text-slate-500">{label}</p>
                                <p className="mt-2 text-2xl font-extrabold text-slate-900">{value}</p>
                            </div>
                        ))}
                    </section>

                    <div className="grid gap-5 lg:grid-cols-3">
                        <div id="estados" className="scroll-mt-6"><Barras titulo="Incidencias por estado" datos={porEstado} /></div>
                        <div id="categorias" className="scroll-mt-6"><Barras titulo="Tipos más reportados" datos={porTipo} color="bg-blue-500" /></div>
                        <Barras titulo="Registros de los últimos meses" datos={porMes} color="bg-violet-500" />
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
