import BarrasInforme from '@/Components/BarrasInforme';
import InformeCabecera from '@/Components/InformeCabecera';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionReportes } from '@/navegacionReportes';
import { Head } from '@inertiajs/react';

export default function InformePersonal({
    resumenPersonal,
    porRol,
    usuarios,
    contratos,
    porEstadoContrato,
    periodo,
    pdfUrl,
}) {
    return (
        <ModuleLayout moduleName="Reportes" items={navegacionReportes}>
            <Head title="Informe de personal" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <InformeCabecera
                        titulo="Personal y contratos"
                        descripcion="Cuentas municipales y contratos registrados en gestión de personal. El filtro de fechas no altera este inventario."
                        periodo={periodo}
                        rutaFiltro={route('administracion.reportes.personal')}
                        pdfUrl={pdfUrl}
                    />

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <InformeCabecera.Kpi label="Cuentas municipales" value={resumenPersonal.cuentas} />
                        <InformeCabecera.Kpi label="Cuentas activas" value={resumenPersonal.activas} />
                        <InformeCabecera.Kpi label="Ciudadanos" value={resumenPersonal.ciudadanos} />
                        <InformeCabecera.Kpi label="Contratos vigentes" value={`${resumenPersonal.vigentes} / ${resumenPersonal.contratos}`} />
                    </section>

                    <div className="grid gap-5 lg:grid-cols-2">
                        <BarrasInforme titulo="Cuentas por rol" datos={porRol} color="bg-indigo-500" />
                        <BarrasInforme titulo="Contratos por estado" datos={porEstadoContrato} color="bg-teal-500" />
                    </div>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <h3 className="border-b border-slate-100 px-5 py-4 text-sm font-bold text-slate-900">Cuentas municipales</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Nombre</th>
                                        <th className="px-5 py-3">Correo</th>
                                        <th className="px-5 py-3">Rol</th>
                                        <th className="px-5 py-3">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {usuarios.map((usuario) => (
                                        <tr key={usuario.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold">{usuario.name}</td>
                                            <td className="px-5 py-3">{usuario.email}</td>
                                            <td className="px-5 py-3">{usuario.rol}</td>
                                            <td className="px-5 py-3">{usuario.activo ? 'Activo' : 'Inactivo'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <h3 className="border-b border-slate-100 px-5 py-4 text-sm font-bold text-slate-900">Contratos</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">N.º</th>
                                        <th className="px-5 py-3">Colaborador</th>
                                        <th className="px-5 py-3">Cargo</th>
                                        <th className="px-5 py-3">Tipo</th>
                                        <th className="px-5 py-3">Estado</th>
                                        <th className="px-5 py-3">Vigencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contratos.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-5 py-8 text-center text-slate-500">
                                                No hay contratos registrados.
                                            </td>
                                        </tr>
                                    )}
                                    {contratos.map((contrato) => (
                                        <tr key={contrato.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold">{contrato.numero}</td>
                                            <td className="px-5 py-3">{contrato.colaborador}</td>
                                            <td className="px-5 py-3">{contrato.cargo || '—'}</td>
                                            <td className="px-5 py-3">{contrato.tipo}</td>
                                            <td className="px-5 py-3">{contrato.estado}</td>
                                            <td className="px-5 py-3 text-slate-500">{contrato.inicio} – {contrato.fin}</td>
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
