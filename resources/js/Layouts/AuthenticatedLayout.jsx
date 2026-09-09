import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { broadcastLogout } from '@/sessionSync';
import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const { user, roleName } = usePage().props.auth;

    return (
        <div className="min-h-screen bg-slate-100">
            <nav className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link href={route('dashboard')} className="flex items-center gap-2.5">
                        <ApplicationLogo className="h-10 w-10" />
                        <div>
                            <span className="block text-lg font-extrabold leading-none text-slate-900">
                                EcoCiudad
                            </span>
                            <span className="mt-1 hidden text-[10px] font-semibold uppercase tracking-wider text-emerald-700 sm:block">
                                El Tambo
                            </span>
                        </div>
                    </Link>

                    <div className="flex items-center gap-3">
                        {!route().current('dashboard') && (
                            <Link
                                href={route('dashboard')}
                                className="hidden rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 sm:inline-flex"
                            >
                                ← Volver a módulos
                            </Link>
                        )}

                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    className="inline-flex items-center gap-3 rounded-xl border border-transparent px-2 py-1.5 text-left transition hover:border-slate-200 hover:bg-slate-50"
                                >
                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                                        {user.name.slice(0, 1).toUpperCase()}
                                    </span>
                                    <span className="hidden sm:block">
                                        <span className="block max-w-40 truncate text-sm font-semibold text-slate-800">
                                            {user.name}
                                        </span>
                                        <span className="block text-[10px] font-medium text-emerald-700">
                                            {roleName}
                                        </span>
                                    </span>
                                    <span className="text-xs text-slate-400">⌄</span>
                                </button>
                            </Dropdown.Trigger>

                            <Dropdown.Content>
                                <Dropdown.Link href={route('dashboard')}>
                                    Panel de módulos
                                </Dropdown.Link>
                                <Dropdown.Link href={route('profile.edit')}>
                                    Mi perfil
                                </Dropdown.Link>
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    onSuccess={broadcastLogout}
                                >
                                    Cerrar sesión
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-slate-200 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
