import FlashMessage from '@/Components/FlashMessage';
import TipoMantenimientoRow from '@/Components/TipoMantenimientoRow';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, useForm } from '@inertiajs/react';

export default function Tipos({ tipos }) {
    const form = useForm({ nombre: '', descripcion: '' });

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.vehiculos.maestros.tipos.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Tipos de mantenimientos" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Maestros</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Tipos de mantenimientos</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Catálogo de intervenciones que se pueden solicitar al registrar un vehículo en taller.
                        </p>
                    </div>

                    <FlashMessage />

                    <div className="grid gap-6 xl:grid-cols-[340px_1fr]">
                        <form onSubmit={guardar} className="h-fit space-y-4 border border-slate-300 bg-white p-6 shadow-sm">
                            <h3 className="font-bold text-slate-900">Nuevo tipo</h3>
                            <input
                                value={form.data.nombre}
                                onChange={(event) => form.setData('nombre', event.target.value)}
                                placeholder="Nombre del mantenimiento"
                                className="w-full border-slate-300 text-sm"
                                required
                            />
                            <textarea
                                value={form.data.descripcion}
                                onChange={(event) => form.setData('descripcion', event.target.value)}
                                placeholder="Descripción de la intervención"
                                rows="4"
                                className="w-full border-slate-300 text-sm"
                            />
                            {Object.values(form.errors).map((error) => (
                                <p key={error} className="text-xs text-red-600">{error}</p>
                            ))}
                            <button className="w-full bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">
                                Agregar tipo
                            </button>
                        </form>

                        <section className="border border-slate-300 bg-white p-6 shadow-sm">
                            <div className="mb-5">
                                <h3 className="font-bold text-slate-900">Catálogo</h3>
                                <p className="text-xs text-slate-500">{tipos.length} registros</p>
                            </div>
                            <div className="grid gap-3 lg:grid-cols-2">
                                {tipos.map((tipo) => (
                                    <TipoMantenimientoRow key={tipo.id} tipo={tipo} />
                                ))}
                            </div>
                            {!tipos.length && (
                                <p className="py-10 text-center text-sm text-slate-500">Aún no hay tipos registrados.</p>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
