import CamionAdminCard from '@/Components/CamionAdminCard';
import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, Link } from '@inertiajs/react';

export default function Equipos({ camiones, personalLimpieza }) {
    const disponibles = personalLimpieza.filter((persona) => !persona.camiones?.length).length;
    const completos = camiones.filter((camion) => camion.personal.length === 4).length;

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Equipos operativos" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Asignación de personal</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Equipos operativos</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Asigna un conductor y tres recolectores a cada vehículo registrado.
                            </p>
                        </div>
                        <div className="text-right text-xs text-slate-500">
                            <p>{completos}/{camiones.length} equipos completos</p>
                            <p>Personal disponible: {disponibles}</p>
                        </div>
                    </div>

                    <FlashMessage />

                    {camiones.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center">
                            <p className="text-sm text-slate-500">Aún no hay vehículos registrados.</p>
                            <Link
                                href={route('administracion.vehiculos.index')}
                                className="mt-4 inline-flex rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white"
                            >
                                Ir a registro de vehículos
                            </Link>
                        </div>
                    ) : (
                        <div className="grid gap-5">
                            {camiones.map((camion) => (
                                <CamionAdminCard
                                    key={camion.id}
                                    camion={camion}
                                    personalLimpieza={personalLimpieza}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
