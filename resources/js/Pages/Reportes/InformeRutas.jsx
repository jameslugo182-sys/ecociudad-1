import BarrasInforme from '@/Components/BarrasInforme';
import InformeCabecera from '@/Components/InformeCabecera';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionReportes } from '@/navegacionReportes';
import { Head } from '@inertiajs/react';

export default function InformeRutas({
    rutas,
    porEstado,
    resumenRutas,
    flota,
    mantenimientos,
    periodo,
    pdfUrl,
}) {
    return (
        <ModuleLayout moduleName="Reportes" items={navegacionReportes}>
            <Head title="Informe de rutas y flota" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <InformeCabecera
                        titulo="Rutas y flota"
                        descripcion="Recorridos, kilómetros estimados, vehículos y mantenimientos tomados de la operación diaria."
                        periodo={periodo}
                        rutaFiltro={route('administracion.reportes.rutas')}
                        pdfUrl={pdfUrl}
                    />

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <InformeCabecera.Kpi label="Rutas del periodo" value={resumenRutas.total} />
                        <InformeCabecera.Kpi label="Km estimados" value={resumenRutas.km} />
                        <InformeCabecera.Kpi label="Rutas activas ahora" value={resumenRutas.activas} nota="Estado actual" />
                        <InformeCabecera.Kpi label="Equipos completos" value={`${resumenRutas.equiposCompletos} / ${resumenRutas.vehiculos}`} nota="Estado actual" />
                    </section>

                    <BarrasInforme titulo="Rutas por estado" datos={porEstado} color="bg-sky-500" />

                    <Tabla titulo="Rutas del periodo" vacio="No hay rutas en el periodo seleccionado." columnas={['Fecha', 'Vehículo', 'Estado', 'Paradas', 'Km', 'Horario']}>
                        {rutas.map((ruta) => (
                            <tr key={ruta.id} className="border-t border-slate-100">
                                <td className="px-5 py-3">{ruta.fecha}</td>
                                <td className="px-5 py-3">{ruta.camion}</td>
                                <td className="px-5 py-3">{ruta.estado}</td>
                                <td className="px-5 py-3">{ruta.paradas}</td>
                                <td className="px-5 py-3">{ruta.km}</td>
                                <td className="px-5 py-3 text-slate-500">{ruta.horario || '—'}</td>
                            </tr>
                        ))}
                    </Tabla>

                    <Tabla titulo="Flota registrada" vacio="No hay vehículos registrados." columnas={['Código', 'Placa', 'Estado', 'Personal']}>
                        {flota.map((camion) => (
                            <tr key={camion.id} className="border-t border-slate-100">
                                <td className="px-5 py-3 font-semibold">{camion.codigo}</td>
                                <td className="px-5 py-3">{camion.placa}</td>
                                <td className="px-5 py-3">{camion.estado}</td>
                                <td className="px-5 py-3">{camion.personal}</td>
                            </tr>
                        ))}
                    </Tabla>

                    <Tabla titulo="Mantenimientos del periodo" vacio="No hay mantenimientos en el periodo." columnas={['Vehículo', 'Tipo', 'Estado', 'Inicio']}>
                        {mantenimientos.map((item) => (
                            <tr key={item.id} className="border-t border-slate-100">
                                <td className="px-5 py-3">{item.camion}</td>
                                <td className="px-5 py-3">{item.tipo}</td>
                                <td className="px-5 py-3">{item.estado}</td>
                                <td className="px-5 py-3 text-slate-500">{item.inicio}</td>
                            </tr>
                        ))}
                    </Tabla>
                </div>
            </div>
        </ModuleLayout>
    );
}

function Tabla({ titulo, columnas, vacio, children }) {
    const filas = Array.isArray(children) ? children : children ? [children] : [];
    const hayDatos = filas.filter(Boolean).length > 0;

    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <h3 className="border-b border-slate-100 px-5 py-4 text-sm font-bold text-slate-900">{titulo}</h3>
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            {columnas.map((columna) => (
                                <th key={columna} className="px-5 py-3">{columna}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {!hayDatos && (
                            <tr>
                                <td colSpan={columnas.length} className="px-5 py-8 text-center text-slate-500">{vacio}</td>
                            </tr>
                        )}
                        {hayDatos && children}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
