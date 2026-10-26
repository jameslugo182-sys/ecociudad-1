import BarrasInforme from '@/Components/BarrasInforme';
import InformeCabecera from '@/Components/InformeCabecera';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionReportes } from '@/navegacionReportes';
import { Head, Link } from '@inertiajs/react';

export default function InformeIncidencias({ incidencias, porEstado, porTipo, estados, periodo, pdfUrl }) {
    const filas = incidencias.data ?? incidencias;

    return (
        <ModuleLayout moduleName="Reportes" items={navegacionReportes}>
            <Head title="Informe de incidencias" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <InformeCabecera
                        titulo="Incidencias"
                        descripcion="Listado generado desde los reportes ciudadanos y las alertas de contenedores IoT."
                        periodo={periodo}
                        rutaFiltro={route('administracion.reportes.incidencias')}
                        pdfUrl={pdfUrl}
                        estados={estados}
                    />

                    <div className="grid gap-5 lg:grid-cols-2">
                        <BarrasInforme titulo="Por estado" datos={porEstado} />
                        <BarrasInforme titulo="Por tipo" datos={porTipo} color="bg-blue-500" />
                    </div>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">N.º</th>
                                        <th className="px-5 py-3">Ciudadano</th>
                                        <th className="px-5 py-3">Origen</th>
                                        <th className="px-5 py-3">Tipo</th>
                                        <th className="px-5 py-3">Estado</th>
                                        <th className="px-5 py-3">Responsable</th>
                                        <th className="px-5 py-3">Registrado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filas.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-5 py-8 text-center text-slate-500">
                                                No hay incidencias en el periodo seleccionado.
                                            </td>
                                        </tr>
                                    )}
                                    {filas.map((fila) => (
                                        <tr key={fila.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold text-slate-900">{fila.codigo}</td>
                                            <td className="px-5 py-3">{fila.ciudadano}</td>
                                            <td className="px-5 py-3">{fila.origen}</td>
                                            <td className="px-5 py-3">{fila.tipo}</td>
                                            <td className="px-5 py-3">{fila.estado}</td>
                                            <td className="px-5 py-3">{fila.responsable || '—'}</td>
                                            <td className="px-5 py-3 text-slate-500">{fila.registrado}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {incidencias.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                                <span>{incidencias.from}–{incidencias.to} de {incidencias.total}</span>
                                <div className="flex gap-2">
                                    {incidencias.prev_page_url && <Link href={incidencias.prev_page_url} className="font-semibold text-emerald-700">Anterior</Link>}
                                    {incidencias.next_page_url && <Link href={incidencias.next_page_url} className="font-semibold text-emerald-700">Siguiente</Link>}
                                </div>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
