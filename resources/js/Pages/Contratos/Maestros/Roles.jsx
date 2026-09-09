import FlashMessage from '@/Components/FlashMessage';
import RolMaestroCard from '@/Components/RolMaestroCard';
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

export default function Roles({ roles, modulos }) {
    const form = useForm({ nombre: '', codigo: '', descripcion: '', modulos: [] });

    const alternarModulo = (modulo) => {
        form.setData(
            'modulos',
            form.data.modulos.includes(modulo)
                ? form.data.modulos.filter((item) => item !== modulo)
                : [...form.data.modulos, modulo],
        );
    };

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.contratos.maestros.roles.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacion}>
            <Head title="Maestro de roles" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Maestros</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Roles y permisos</h2>
                        <p className="mt-1 text-sm text-slate-500">Configura qué módulos puede utilizar cada tipo de usuario.</p>
                    </div>

                    <FlashMessage />

                    <div className="grid gap-6 xl:grid-cols-[360px_1fr]">
                        <form onSubmit={guardar} className="h-fit space-y-4 border border-slate-300 bg-white p-6 shadow-sm">
                            <h3 className="font-bold text-slate-900">Nuevo rol</h3>
                            <input value={form.data.nombre} onChange={(event) => form.setData('nombre', event.target.value)} placeholder="Nombre visible" className="w-full border-slate-300 text-sm" required />
                            <input value={form.data.codigo} onChange={(event) => form.setData('codigo', event.target.value.toLowerCase().replace(/\s+/g, '_'))} placeholder="codigo_interno" className="w-full border-slate-300 font-mono text-sm" required />
                            <textarea value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} placeholder="Descripción" rows="3" className="w-full border-slate-300 text-sm" />
                            <div className="space-y-2 border-t border-slate-200 pt-4">
                                <p className="text-xs font-semibold text-slate-600">Módulos permitidos</p>
                                {modulos.map((modulo) => (
                                    <label key={modulo.value} className="flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" checked={form.data.modulos.includes(modulo.value)} onChange={() => alternarModulo(modulo.value)} className="border-slate-300 text-emerald-600" />
                                        {modulo.label}
                                    </label>
                                ))}
                            </div>
                            {Object.values(form.errors).map((error) => <p key={error} className="text-xs text-red-600">{error}</p>)}
                            <button className="w-full bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Agregar rol</button>
                        </form>

                        <section className="border border-slate-300 bg-white p-6 shadow-sm">
                            <div className="mb-5">
                                <h3 className="font-bold text-slate-900">Roles configurados</h3>
                                <p className="text-xs text-slate-500">{roles.length} registros</p>
                            </div>
                            <div className="grid gap-3">
                                {roles.map((rol) => <RolMaestroCard key={rol.id} rol={rol} modulos={modulos} />)}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
