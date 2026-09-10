import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = {
    camion_id: '',
    tipo_mantenimiento_id: '',
    motivo: '',
};

export default function Registro({ camiones, tipos, registros }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const form = useForm(datosIniciales);

    const cerrar = () => {
        if (form.processing) {
            return;
        }

        setMostrarFormulario(false);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.vehiculos.mantenimiento.store'), {
            preserveScroll: true,
            onSuccess: cerrar,
        });
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Registro de mantenimiento" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Mantenimiento de vehículos</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Registro de vehículos para mantenimiento</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Solicita el ingreso a taller de un vehículo ya registrado. Mientras esté en proceso no podrá recibir equipo.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setMostrarFormulario(true)}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nuevo mantenimiento
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal show={mostrarFormulario} maxWidth="lg" closeable={!form.processing} onClose={cerrar}>
                        <form onSubmit={guardar} className="bg-white p-6">
                            <h3 className="text-lg font-bold text-slate-900">Nuevo mantenimiento</h3>
                            <p className="mt-1 text-sm text-slate-500">Indica el vehículo, el tipo de intervención y el motivo.</p>

                            <div className="mt-5 space-y-4">
                                <label className="block text-xs font-semibold text-slate-600">
                                    Vehículo registrado
                                    <select
                                        value={form.data.camion_id}
                                        onChange={(event) => form.setData('camion_id', event.target.value)}
                                        className="mt-1.5 w-full rounded-lg border-slate-300 text-sm"
                                        required
                                    >
                                        <option value="">Seleccionar vehículo</option>
                                        {camiones.map((camion) => (
                                            <option key={camion.id} value={camion.id}>
                                                {camion.codigo} · {camion.placa} {camion.marca ? `· ${camion.marca}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label className="block text-xs font-semibold text-slate-600">
                                    Tipo de mantenimiento
                                    <select
                                        value={form.data.tipo_mantenimiento_id}
                                        onChange={(event) => form.setData('tipo_mantenimiento_id', event.target.value)}
                                        className="mt-1.5 w-full rounded-lg border-slate-300 text-sm"
                                        required
                                        disabled={!tipos.length}
                                    >
                                        <option value="">{tipos.length ? 'Seleccionar tipo' : 'Crea un tipo en Maestros'}</option>
                                        {tipos.map((tipo) => (
                                            <option key={tipo.id} value={tipo.id}>{tipo.nombre}</option>
                                        ))}
                                    </select>
                                </label>

                                <label className="block text-xs font-semibold text-slate-600">
                                    Motivo
                                    <textarea
                                        value={form.data.motivo}
                                        onChange={(event) => form.setData('motivo', event.target.value)}
                                        rows="4"
                                        placeholder="Describe por qué el vehículo ingresa a mantenimiento"
                                        className="mt-1.5 w-full rounded-lg border-slate-300 text-sm"
                                        required
                                    />
                                </label>

                                {!tipos.length && (
                                    <p className="text-xs text-amber-700">
                                        Primero registra un tipo en{' '}
                                        <Link href={route('administracion.vehiculos.maestros.tipos.index')} className="font-semibold underline">
                                            Tipos de mantenimientos
                                        </Link>.
                                    </p>
                                )}

                                {Object.values(form.errors).map((error) => (
                                    <p key={error} className="text-xs font-medium text-red-600">{error}</p>
                                ))}
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <button type="button" onClick={cerrar} className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={form.processing || !tipos.length || !camiones.length}
                                    className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-40"
                                >
                                    {form.processing ? 'Registrando…' : 'Registrar mantenimiento'}
                                </button>
                            </div>
                        </form>
                    </Modal>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Solicitudes recientes</h3>
                            <p className="text-xs text-slate-500">Los vehículos en proceso se gestionan en Vehículos en mantenimiento.</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Vehículo</th>
                                        <th className="px-5 py-4">Tipo</th>
                                        <th className="px-5 py-4">Motivo</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4">Registrado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {registros.map((registro) => (
                                        <tr key={registro.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4 font-semibold text-slate-900">
                                                {registro.camion.codigo} · {registro.camion.placa}
                                            </td>
                                            <td className="px-5 py-4 text-slate-700">{registro.tipo.nombre}</td>
                                            <td className="max-w-xs px-5 py-4 text-slate-600">
                                                <span className="line-clamp-2">{registro.motivo}</span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    registro.estado === 'En curso'
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-slate-100 text-slate-700'
                                                }`}>
                                                    {registro.estado}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">{registro.solicitante?.name || '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!registros.length && (
                            <div className="py-14 text-center text-sm text-slate-500">Aún no se registraron mantenimientos.</div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
