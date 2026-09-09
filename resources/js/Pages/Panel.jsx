import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Panel({ modulos }) {
    const { auth } = usePage().props;
    const navegacion = modulos.map((modulo) => ({
        label: modulo.nombre,
        href: modulo.url,
        target: '_blank',
        rel: 'noopener noreferrer',
    }));

    return (
        <ModuleLayout moduleName="Panel principal" items={navegacion}>
            <Head title="Panel de módulos" />

            <div className="min-h-screen px-5 py-8 sm:px-8 lg:px-12">
                <div className="mx-auto max-w-6xl">
                    <header className="border-b border-slate-300 pb-6">
                        <p className="text-sm font-semibold text-emerald-700">Municipalidad Distrital de El Tambo</p>
                        <h1 className="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">
                            Panel principal
                        </h1>
                        <p className="mt-2 text-sm text-slate-500">
                            Bienvenido, {auth.user.name}. Selecciona un módulo para comenzar.
                        </p>
                    </header>

                    <section className="mt-8 grid gap-4 md:grid-cols-2">
                        {modulos.map((modulo) => (
                            <Link
                                key={modulo.nombre}
                                href={modulo.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="group flex min-h-28 items-center border border-slate-300 bg-white px-5 py-4 shadow-sm transition hover:border-emerald-600 hover:bg-emerald-50"
                            >
                                <span className="min-w-0 flex-1">
                                    <span className="block text-base font-bold text-slate-900 group-hover:text-emerald-800">
                                        {modulo.nombre}
                                    </span>
                                    <span className="mt-1 block text-sm leading-5 text-slate-500">
                                        {modulo.descripcion}
                                    </span>
                                </span>
                                <span className="ml-4 text-xl text-slate-400 group-hover:text-emerald-700">→</span>
                            </Link>
                        ))}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
