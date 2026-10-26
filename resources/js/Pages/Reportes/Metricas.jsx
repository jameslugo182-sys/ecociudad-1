import BarrasInforme from '@/Components/BarrasInforme';
import InformeCabecera from '@/Components/InformeCabecera';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionReportes } from '@/navegacionReportes';
import { Head } from '@inertiajs/react';

export default function Metricas({ resumen, porEstado, porTipo, porMes, porOrigen = [], periodo, pdfUrl }) {
    const kpisPeriodo = [
        ['Incidencias del periodo', resumen.total],
        ['Atendidas', resumen.atendidos],
        ['Tasa de atención', `${resumen.tasaAtencion}%`],
        ['Tiempo promedio', `${resumen.promedioHoras} h`],
        ['Rutas generadas', resumen.rutasPeriodo ?? 0],
        ['Km estimados', resumen.kmEstimados ?? 0],
        ['Lecturas IoT', resumen.lecturasPeriodo ?? 0],
    ];
    const kpisActual = [
        ['Personal activo', resumen.personalActivo, 'Estado actual'],
        ['Vehículos disponibles', resumen.vehiculosDisponibles, 'Estado actual'],
        ['Tachos en alerta', resumen.tachosAlerta ?? 0, 'Estado actual'],
        ['Contratos vigentes', resumen.contratosVigentes ?? 0, 'Estado actual'],
    ];

    return (
        <ModuleLayout moduleName="Reportes" items={navegacionReportes}>
            <Head title="Reportes" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <InformeCabecera
                        titulo="Resumen operativo"
                        descripcion="Cifras tomadas de incidencias, rutas, flota, tachos y contratos registrados en EcoCiudad."
                        periodo={periodo}
                        rutaFiltro={route('administracion.reportes.index')}
                        pdfUrl={pdfUrl}
                    />

                    <section>
                        <h3 className="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">En el periodo</h3>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {kpisPeriodo.map(([label, value]) => (
                                <InformeCabecera.Kpi key={label} label={label} value={value} />
                            ))}
                        </div>
                    </section>

                    <section>
                        <h3 className="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Estado actual del sistema</h3>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {kpisActual.map(([label, value, nota]) => (
                                <InformeCabecera.Kpi key={label} label={label} value={value} nota={nota} />
                            ))}
                        </div>
                    </section>

                    <div className="grid gap-5 lg:grid-cols-2">
                        <BarrasInforme titulo="Incidencias por estado" datos={porEstado} />
                        <BarrasInforme titulo="Tipos más reportados" datos={porTipo} color="bg-blue-500" />
                        <BarrasInforme titulo="Origen de las incidencias" datos={porOrigen} color="bg-amber-500" />
                        <BarrasInforme titulo="Registros de los últimos meses" datos={porMes} color="bg-violet-500" />
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
