import CuentaColaboradorCard from '@/Components/CuentaColaboradorCard';
import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, useForm } from '@inertiajs/react';

const navegacion = [
    { label: 'Registro de contratos', href: route('administracion.contratos.index'), active: 'administracion.contratos.index', icon: '01' },
    { label: 'Creación de usuarios', href: route('administracion.contratos.usuarios.index'), active: 'administracion.contratos.usuarios.*', icon: '02' },
    {
        label: 'Maestros',
        children: [
            { label: 'Roles', href: route('administracion.contratos.maestros.roles.index'), active: 'administracion.contratos.maestros.roles.*' },
            { label: 'Cargos', href: route('administracion.contratos.maestros.cargos.index'), active: 'administracion.contratos.maestros.cargos.*' },
        ],
    },
];

export default function Usuarios({ disponibles, cuentas, roles }) {
    const form = useForm({
        colaborador_id: '',
        email: '',
        rol: roles[0]?.codigo || '',
        password: '',
        password_confirmation: '',
    });
    const seleccionado = disponibles.find((colaborador) => colaborador.id === Number(form.data.colaborador_id));
    const rolSeleccionado = roles.find((rol) => rol.codigo === form.data.rol);

    const seleccionarColaborador = (valor) => {
        const colaborador = disponibles.find((item) => item.id === Number(valor));
        form.setData({
            ...form.data,
            colaborador_id: valor,
            email: colaborador?.email || '',
        });
    };

    const crear = (event) => {
        event.preventDefault();
        form.post(route('administracion.contratos.usuarios.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacion}>
            <Head title="Creación de usuarios" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Acceso al sistema</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Creación de usuarios</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Solo aparecen colaboradores con un contrato registrado y sin cuenta de acceso.
                        </p>
                    </div>

                    <FlashMessage />

                    <div className="grid gap-6 xl:grid-cols-[400px_1fr]">
                        <form onSubmit={crear} className="h-fit space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div>
                                <h3 className="font-bold text-slate-900">Nueva cuenta</h3>
                                <p className="mt-1 text-xs leading-5 text-slate-500">El colaborador debe tener un contrato antes de crear sus credenciales.</p>
                            </div>

                            <label className="block text-xs font-semibold text-slate-600">
                                Colaborador contratado *
                                <select
                                    value={form.data.colaborador_id}
                                    onChange={(event) => seleccionarColaborador(event.target.value)}
                                    className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    required
                                >
                                    <option value="">Seleccionar colaborador</option>
                                    {disponibles.map((colaborador) => (
                                        <option key={colaborador.id} value={colaborador.id}>
                                            {colaborador.nombre_completo} · {colaborador.documento}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            {seleccionado && (
                                <div className="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                    <p className="font-semibold text-slate-800">{seleccionado.contratos[0]?.cargo?.nombre}</p>
                                    <p>{seleccionado.contratos[0]?.numero}</p>
                                    <p>Vence: {new Date(seleccionado.contratos[0]?.fecha_fin).toLocaleDateString('es-PE')}</p>
                                </div>
                            )}

                            <label className="block text-xs font-semibold text-slate-600">
                                Correo de acceso *
                                <input
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                    className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                    required
                                />
                            </label>

                            <label className="block text-xs font-semibold text-slate-600">
                                Rol del sistema *
                                <select value={form.data.rol} onChange={(event) => form.setData('rol', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm" required>
                                    {roles.map((rol) => <option key={rol.id} value={rol.codigo}>{rol.nombre}</option>)}
                                </select>
                            </label>

                            {rolSeleccionado && (
                                <div className="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800">
                                    <p>{rolSeleccionado.descripcion}</p>
                                    <p className="mt-1 font-semibold">Módulos: {rolSeleccionado.modulos.join(', ')}</p>
                                </div>
                            )}

                            <label className="block text-xs font-semibold text-slate-600">
                                Contraseña temporal *
                                <input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm" required />
                            </label>
                            <label className="block text-xs font-semibold text-slate-600">
                                Confirmar contraseña *
                                <input type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm" required />
                            </label>

                            {Object.values(form.errors).map((error) => <p key={error} className="text-xs font-medium text-red-600">{error}</p>)}

                            <button type="submit" disabled={form.processing || !disponibles.length} className="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white disabled:opacity-40">
                                Crear cuenta de acceso
                            </button>
                        </form>

                        <section>
                            <div className="mb-4">
                                <h3 className="font-bold text-slate-900">Cuentas creadas</h3>
                                <p className="text-sm text-slate-500">{cuentas.length} colaboradores con acceso</p>
                            </div>
                            {cuentas.length ? (
                                <div className="grid gap-4 2xl:grid-cols-2">
                                    {cuentas.map((cuenta) => (
                                        <CuentaColaboradorCard key={cuenta.id} cuenta={cuenta} roles={roles} />
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center text-sm text-slate-500">
                                    Aún no se crearon cuentas para colaboradores.
                                </div>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
