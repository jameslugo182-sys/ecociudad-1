import BarrasInforme from '@/Components/BarrasInforme';
import InformeCabecera from '@/Components/InformeCabecera';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionReportes } from '@/navegacionReportes';
import { Head } from '@inertiajs/react';

export default function InformeTachos({ resumenTachos, porEstado, tachos, periodo, pdfUrl }) {
    return (
        <ModuleLayout moduleName="Reportes" items={navegacionReportes}>
            <Head title="Informe de tachos IoT" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <InformeCabecera
                        titulo="Tachos IoT"
                        descripcion="Capacidad, alertas y lecturas enviadas por los ESP32 registrados en el sistema."
                        periodo={periodo}
                        rutaFiltro={route('administracion.reportes.tachos')}
                        pdfUrl={pdfUrl}
                    />

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <InformeCabecera.Kpi label="Tachos registrados" value={resumenTachos.total} nota="Estado actual" />
                        <InformeCabecera.Kpi label="En alerta" value={resumenTachos.alertas} nota="Estado actual" />
                        <InformeCabecera.Kpi label="Llenos" value={resumenTachos.llenos} nota="Estado actual" />
                        <InformeCabecera.Kpi label="Lecturas del periodo" value={resumenTachos.lecturasPeriodo} />
                        <InformeCabecera.Kpi label="Nivel promedio" value={`${resumenTachos.nivelPromedio}%`} nota="Estado actual" />
                    </section>

                    <BarrasInforme titulo="Tachos por estado" datos={porEstado} color="bg-orange-500" />

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Código</th>
                                        <th className="px-5 py-3">Nombre</th>
                                        <th className="px-5 py-3">Estado</th>
                                        <th className="px-5 py-3">Nivel</th>
                                        <th className="px-5 py-3">Lecturas del periodo</th>
                                        <th className="px-5 py-3">Última lectura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tachos.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-5 py-8 text-center text-slate-500">
                                                No hay tachos registrados.
                                            </td>
                                        </tr>
                                    )}
                                    {tachos.map((tacho) => (
                                        <tr key={tacho.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold">{tacho.codigo}</td>
                                            <td className="px-5 py-3">{tacho.nombre}</td>
                                            <td className="px-5 py-3">
                                                {tacho.estado}
                                                {tacho.alerta ? ' · alerta' : ''}
                                            </td>
                                            <td className="px-5 py-3">{tacho.nivel === null ? 'Sin lecturas' : `${tacho.nivel}%`}</td>
                                            <td className="px-5 py-3">{tacho.lecturas}</td>
                                            <td className="px-5 py-3 text-slate-500">{tacho.ultimaLectura || '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
