import CamionAdminCard from '@/Components/CamionAdminCard';
import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Camiones({ camiones, personalLimpieza, estados }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const navegacion = [
        { label: 'Registro de vehículos', href: route('administracion.vehiculos.index'), active: 'administracion.vehiculos.index', icon: '01' },
        { label: 'Equipos operativos', href: `${route('administracion.vehiculos.index')}#equipos`, hash: '#equipos', icon: '02' },
    ];
    const form = useForm({
        codigo: '',
        placa: '',
        marca: '',
        modelo: '',
        anio: '',
        capacidad_kg: '',
        estado: 'Disponible',
    });

    const crear = (event) => {
        event.preventDefault();
        form.post(route('administracion.vehiculos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setMostrarFormulario(false);
            },
        });
    };

    const cerrarFormulario = () => {
        if (!form.processing) {
            setMostrarFormulario(false);
            form.reset();
            form.clearErrors();
        }
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacion}>
            <Head title="Vehículos" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Flota municipal</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Vehículos y equipos</h2>
                        </div>
                        <button
                            type="button"
                            onClick={() => setMostrarFormulario(true)}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nuevo registro de vehículo
                        </button>
                    </div>
                    <FlashMessage />

                    <Modal
                        show={mostrarFormulario}
                        maxWidth="4xl"
                        closeable={!form.processing}
                        onClose={cerrarFormulario}
                    >
                        <form onSubmit={crear} className="bg-white">
                            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">Nuevo registro de vehículo</h2>
                                    <p className="text-sm text-slate-500">Añade una unidad a la flota municipal.</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={cerrarFormulario}
                                    className="text-xl text-slate-400 hover:text-slate-700"
                                >
                                    ×
                                </button>
                            </div>

                            <div className="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                                {[
                                    ['codigo', 'Código interno *', 'text'],
                                    ['placa', 'Placa *', 'text'],
                                    ['marca', 'Marca', 'text'],
                                    ['modelo', 'Modelo', 'text'],
                                    ['anio', 'Año', 'number'],
                                    ['capacidad_kg', 'Capacidad en kg', 'number'],
                                ].map(([campo, label, tipo]) => (
                                    <label key={campo} className="text-xs font-semibold text-slate-600">
                                        {label}
                                        <input
                                            type={tipo}
                                            value={form.data[campo]}
                                            onChange={(event) => form.setData(campo, event.target.value)}
                                            required={label.includes('*')}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        />
                                    </label>
                                ))}
                                <label className="text-xs font-semibold text-slate-600">
                                    Estado *
                                    <select
                                        value={form.data.estado}
                                        onChange={(event) => form.setData('estado', event.target.value)}
                                        className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        {estados.map((estado) => <option key={estado}>{estado}</option>)}
                                    </select>
                                </label>

                                <div className="sm:col-span-2 lg:col-span-3">
                                    {Object.values(form.errors).map((error) => (
                                        <p key={error} className="mt-1 text-xs font-medium text-red-600">{error}</p>
                                    ))}
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                                <button
                                    type="button"
                                    onClick={cerrarFormulario}
                                    className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    {form.processing ? 'Registrando…' : 'Registrar vehículo'}
                                </button>
                            </div>
                        </form>
                    </Modal>

                    <div id="equipos" className="flex scroll-mt-6 flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">Vehículos registrados</h2>
                            <p className="text-sm text-slate-500">{camiones.length} unidades en la flota</p>
                        </div>
                        <p className="text-xs text-slate-500">
                            Personal de limpieza disponible: {personalLimpieza.filter((persona) => !persona.camiones?.length).length}
                        </p>
                    </div>

                    {camiones.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center text-sm text-slate-500">
                            Aún no hay camiones registrados.
                        </div>
                    ) : (
                        <div className="grid gap-5">
                            {camiones.map((camion) => (
                                <CamionAdminCard
                                    key={camion.id}
                                    camion={camion}
                                    personalLimpieza={personalLimpieza}
                                    estados={estados}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </ModuleLayout>
    );
}
