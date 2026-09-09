import CargoMaestroRow from '@/Components/CargoMaestroRow';
import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, useForm } from '@inertiajs/react';

const navegacion = [
    { label: 'Registro de contratos', href: route('administracion.contratos.index'), active: 'administracion.contratos.index' },
    { label: 'Creación de usuarios', href: route('administracion.contratos.usuarios.index'), active: 'administracion.contratos.usuarios.*' },
    {
        label: 'Maestros',
        children: [
            { label: 'Roles', href: route('administracion.contratos.maestros.roles.index'), active: 'administracion.contratos.maestros.roles.*' },
            { label: 'Cargos', href: route('administracion.contratos.maestros.cargos.index'), active: 'administracion.contratos.maestros.cargos.*' },
        ],
    },
];

export default function Cargos({ cargos }) {
    const form = useForm({ nombre: '', descripcion: '' });

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.contratos.maestros.cargos.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacion}>
            <Head title="Maestro de cargos" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Maestros</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Cargos</h2>
                        <p className="mt-1 text-sm text-slate-500">Valores disponibles en el registro de contratos.</p>
                    </div>

                    <FlashMessage />

                    <div className="grid gap-6 xl:grid-cols-[340px_1fr]">
                        <form onSubmit={guardar} className="h-fit space-y-4 border border-slate-300 bg-white p-6 shadow-sm">
                            <h3 className="font-bold text-slate-900">Nuevo cargo</h3>
                            <input value={form.data.nombre} onChange={(event) => form.setData('nombre', event.target.value)} placeholder="Nombre del cargo" className="w-full border-slate-300 text-sm" required />
                            <textarea value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} placeholder="Descripción y funciones" rows="4" className="w-full border-slate-300 text-sm" />
                            {Object.values(form.errors).map((error) => <p key={error} className="text-xs text-red-600">{error}</p>)}
                            <button className="w-full bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Agregar cargo</button>
                        </form>

                        <section className="border border-slate-300 bg-white p-6 shadow-sm">
                            <div className="mb-5">
                                <h3 className="font-bold text-slate-900">Catálogo de cargos</h3>
                                <p className="text-xs text-slate-500">{cargos.length} registros</p>
                            </div>
                            <div className="grid gap-3 lg:grid-cols-2">
                                {cargos.map((cargo) => <CargoMaestroRow key={cargo.id} cargo={cargo} />)}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
