import CuentaColaboradorCard from '@/Components/CuentaColaboradorCard';
import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionContratos } from '@/navegacionContratos';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = (roles) => ({
    colaborador_id: '',
    email: '',
    rol: roles[0]?.codigo || '',
    password: '',
    password_confirmation: '',
});

export default function Usuarios({ disponibles, cuentas, roles }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const form = useForm(datosIniciales(roles));
    const seleccionado = disponibles.find((colaborador) => colaborador.id === Number(form.data.colaborador_id));
    const rolSeleccionado = roles.find((rol) => rol.codigo === form.data.rol);

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        setEditando(null);
        form.setData(datosIniciales(roles));
        form.clearErrors();
    };

    const abrirNuevo = () => {
        cerrarFormulario();
        setMostrarFormulario(true);
    };

    const abrirEditar = (cuenta) => {
        setEditando(cuenta.id);
        form.setData({
            colaborador_id: cuenta.colaborador_id || '',
            email: cuenta.email,
            rol: cuenta.rol,
            password: '',
            password_confirmation: '',
        });
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const seleccionarColaborador = (valor) => {
        const colaborador = disponibles.find((item) => item.id === Number(valor));
        form.setData({
            ...form.data,
            colaborador_id: valor,
            email: colaborador?.email || form.data.email,
        });
    };

    const guardar = (event) => {
        event.preventDefault();
        const destino = editando
            ? route('administracion.contratos.usuarios.update', editando)
            : route('administracion.contratos.usuarios.store');
        const enviar = editando ? form.put.bind(form) : form.post.bind(form);

        enviar(destino, {
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacionContratos}>
            <Head title="Creación de usuarios" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Acceso al sistema</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Creación de usuarios</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Solo aparecen colaboradores con un contrato registrado y sin cuenta de acceso.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={abrirNuevo}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nuevo usuario
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal show={mostrarFormulario} maxWidth="lg" closeable={!form.processing} onClose={cerrarFormulario}>
                        <form onSubmit={guardar} className="bg-white">
                            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">{editando ? 'Editar acceso' : 'Nuevo usuario'}</h3>
                                    <p className="text-xs text-slate-500">
                                        {editando ? 'Actualiza el correo, el rol o la contraseña.' : 'El colaborador debe tener un contrato antes de crear sus credenciales.'}
                                    </p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>

                            <div className="space-y-4 p-6">
                                {!editando && (
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
                                )}

                                {!editando && seleccionado && (
                                    <div className="rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                        <p className="font-semibold text-slate-800">{seleccionado.contratos[0]?.cargo?.nombre}</p>
                                        <p>{seleccionado.contratos[0]?.numero}</p>
                                        <p>Vence: {new Date(seleccionado.contratos[0]?.fecha_fin).toLocaleDateString('es-PE')}</p>
                                    </div>
                                )}

                                {!editando && !disponibles.length && (
                                    <p className="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        No hay colaboradores con contrato y sin cuenta para registrar.
                                    </p>
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
                                    {editando ? 'Nueva contraseña' : 'Contraseña temporal *'}
                                    <input
                                        type="password"
                                        value={form.data.password}
                                        onChange={(event) => form.setData('password', event.target.value)}
                                        className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                        required={!editando}
                                    />
                                </label>
                                <label className="block text-xs font-semibold text-slate-600">
                                    Confirmar contraseña {editando ? '' : '*'}
                                    <input
                                        type="password"
                                        value={form.data.password_confirmation}
                                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                        className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                        required={!editando}
                                    />
                                </label>

                                {Object.values(form.errors).map((error) => <p key={error} className="text-xs font-medium text-red-600">{error}</p>)}

                                <div className="flex justify-end gap-3 border-t border-slate-100 pt-4">
                                    <button type="button" onClick={cerrarFormulario} className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">
                                        Cancelar
                                    </button>
                                    <button type="submit" disabled={form.processing || (!editando && !disponibles.length)} className="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-40">
                                        {form.processing ? 'Guardando…' : (editando ? 'Guardar cambios' : 'Crear cuenta')}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </Modal>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Cuentas creadas</h3>
                            <p className="text-xs text-slate-500">{cuentas.length} colaboradores con acceso</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Colaborador</th>
                                        <th className="px-5 py-3">Correo</th>
                                        <th className="px-5 py-3">Rol</th>
                                        <th className="px-5 py-3">Contrato</th>
                                        <th className="px-5 py-3">Estado</th>
                                        <th className="px-5 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {cuentas.map((cuenta) => (
                                        <CuentaColaboradorCard key={cuenta.id} cuenta={cuenta} roles={roles} onEditar={abrirEditar} />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!cuentas.length && (
                            <p className="py-14 text-center text-sm text-slate-500">Aún no se crearon cuentas para colaboradores.</p>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
