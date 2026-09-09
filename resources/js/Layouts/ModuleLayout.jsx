import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ModuleLayout({ moduleName, items, children }) {
    const { auth } = usePage().props;
    const [gruposAbiertos, setGruposAbiertos] = useState(() =>
        items
            .filter((item) => item.children?.some((child) => route().current(child.active)))
            .map((item) => item.label),
    );

    const alternarGrupo = (label) => {
        setGruposAbiertos((actuales) =>
            actuales.includes(label)
                ? actuales.filter((item) => item !== label)
                : [...actuales, label],
        );
    };

    return (
        <div className="min-h-screen bg-slate-100 lg:grid lg:grid-cols-[280px_1fr]">
            <aside className="flex flex-col border-b border-slate-800 bg-slate-900 text-white lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r">
                <div className="border-b border-white/10 px-6 py-6">
                    <Link href={route('dashboard')} className="flex items-center gap-3">
                        <ApplicationLogo className="h-11 w-11" />
                        <div>
                            <p className="text-xl font-extrabold leading-none">EcoCiudad</p>
                            <p className="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-400">
                                El Tambo
                            </p>
                        </div>
                    </Link>
                </div>

                <div className="px-4 py-5">
                    <p className="px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                        Módulo
                    </p>
                    <h1 className="mt-1 px-3 text-base font-bold text-white">{moduleName}</h1>
                </div>

                <nav className="flex gap-2 overflow-x-auto px-4 pb-5 lg:flex-1 lg:flex-col lg:overflow-x-visible">
                    {items.map((item) => {
                        if (item.children) {
                            const abierto = gruposAbiertos.includes(item.label);
                            const active = item.children.some((child) => route().current(child.active));

                            return (
                                <div key={item.label} className="shrink-0">
                                    <button
                                        type="button"
                                        onClick={() => alternarGrupo(item.label)}
                                        className={`flex w-full items-center justify-between px-3 py-3 text-left text-sm font-medium transition ${
                                            active ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                        }`}
                                    >
                                        <span>{item.label}</span>
                                        <span className={`text-xs transition ${abierto ? 'rotate-180' : ''}`}>⌄</span>
                                    </button>
                                    {abierto && (
                                        <div className="border-l border-slate-700 lg:ml-4">
                                            {item.children.map((child) => (
                                                <Link
                                                    key={child.label}
                                                    href={child.href}
                                                    className={`block px-4 py-2.5 text-xs transition ${
                                                        route().current(child.active)
                                                            ? 'bg-slate-800 font-semibold text-emerald-400'
                                                            : 'text-slate-400 hover:bg-white/5 hover:text-white'
                                                    }`}
                                                >
                                                    {child.label}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        const active = item.active
                            ? route().current(item.active)
                            : window.location.hash === item.hash;

                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`flex shrink-0 items-center px-3 py-3 text-sm font-medium transition ${
                                    active
                                        ? 'bg-emerald-600 text-white'
                                        : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="hidden border-t border-white/10 p-4 lg:block">
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button className="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-white/10">
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 font-bold">
                                    {auth.user.name.slice(0, 1).toUpperCase()}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-semibold">{auth.user.name}</span>
                                    <span className="block truncate text-[10px] text-slate-400">{auth.user.email}</span>
                                </span>
                                <span className="text-slate-500">⌃</span>
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content align="left" width="48" direction="up">
                            <Dropdown.Link href={route('dashboard')}>Panel de módulos</Dropdown.Link>
                            <Dropdown.Link href={route('profile.edit')}>Mi perfil</Dropdown.Link>
                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                Cerrar sesión
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </aside>

            <main className="min-w-0 bg-slate-50">{children}</main>
        </div>
    );
}
