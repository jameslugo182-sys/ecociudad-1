import FlashMessage from '@/Components/FlashMessage';
import ReporteCard from '@/Components/ReporteCard';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ reportes }) {
    const navegacion = [
        { label: 'Mis incidencias', href: route('reportes.index'), active: 'reportes.index', icon: '01' },
        { label: 'Registrar incidencia', href: route('reportes.create'), active: 'reportes.create', icon: '02' },
    ];
    const pendientes = reportes.filter((reporte) => reporte.estado !== 'Atendido').length;
    const atendidos = reportes.filter((reporte) => reporte.estado === 'Atendido').length;

    return (
        <ModuleLayout moduleName="Incidencias registradas" items={navegacion}>
            <Head title="Mis reportes" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Participación ciudadana</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Mis reportes</h2>
                    </div>
                    <Link
                        href={route('reportes.create')}
                        className="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        + Registrar incidencia
                    </Link>
                    </div>
                    <FlashMessage />

                    <section className="grid gap-4 sm:grid-cols-3">
                        {[
                            ['Total registrados', reportes.length],
                            ['En proceso', pendientes],
                            ['Atendidos', atendidos],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p className="text-sm text-slate-500">{label}</p>
                                <p className="mt-2 text-3xl font-bold text-slate-900">{value}</p>
                            </div>
                        ))}
                    </section>

                    {reportes.length === 0 ? (
                        <section className="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl">
                                ♻
                            </div>
                            <h2 className="text-lg font-bold text-slate-900">Aún no tienes reportes</h2>
                            <p className="mt-2 text-sm text-slate-500">
                                Registra una acumulación de residuos con foto y ubicación GPS.
                            </p>
                            <Link
                                href={route('reportes.create')}
                                className="mt-6 inline-flex rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white"
                            >
                                Crear mi primer reporte
                            </Link>
                        </section>
                    ) : (
                        <section className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            {reportes.map((reporte) => (
                                <ReporteCard key={reporte.id} reporte={reporte} />
                            ))}
                        </section>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
