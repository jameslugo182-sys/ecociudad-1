import FlashMessage from '@/Components/FlashMessage';
import HorarioTurnosForm from '@/Components/HorarioTurnosForm';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionContratos } from '@/navegacionContratos';
import { TURNOS_INICIALES } from '@/horarioTurnos';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = {
    nombre: '',
    descripcion: '',
    turnos: TURNOS_INICIALES,
    dias: {},
};

export default function Plantillas({ plantillas }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const form = useForm(datosIniciales);

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.contratos.horarios.plantillas.store'), {
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacionContratos}>
            <Head title="Plantillas de horarios" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Horarios del personal</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Plantillas de horarios</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Define los turnos y pulsa + para bajarlos a cada día de la semana.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setMostrarFormulario(true)}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nueva plantilla
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal show={mostrarFormulario} maxWidth="7xl" closeable={!form.processing} onClose={cerrarFormulario}>
                        <form onSubmit={guardar} className="max-h-[calc(100vh-3rem)] overflow-y-auto bg-white">
                            <div className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">Nueva plantilla de horario</h3>
                                    <p className="text-xs text-slate-500">Define los turnos y cárgalos día por día con el botón +.</p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>

                            <div className="space-y-5 p-6">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <label className="text-xs font-semibold text-slate-600">
                                        Nombre de la plantilla *
                                        <input
                                            value={form.data.nombre}
                                            onChange={(event) => form.setData('nombre', event.target.value)}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                            required
                                        />
                                    </label>
                                    <label className="text-xs font-semibold text-slate-600">
                                        Descripción
                                        <input
                                            value={form.data.descripcion}
                                            onChange={(event) => form.setData('descripcion', event.target.value)}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                        />
                                    </label>
                                </div>

                                <HorarioTurnosForm
                                    turnos={form.data.turnos}
                                    dias={form.data.dias}
                                    onChangeTurnos={(turnos) => form.setData('turnos', turnos)}
                                    onChangeDias={(dias) => form.setData('dias', dias)}
                                />

                                {Object.values(form.errors).map((error) => (
                                    <p key={error} className="text-xs font-medium text-red-600">{error}</p>
                                ))}

                                <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                                    <button type="button" onClick={cerrarFormulario} className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">
                                        Cancelar
                                    </button>
                                    <button type="submit" disabled={form.processing} className="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                                        {form.processing ? 'Guardando…' : 'Guardar plantilla'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </Modal>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Plantillas creadas</h3>
                            <p className="text-xs text-slate-500">{plantillas.length} registros</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Nombre</th>
                                        <th className="px-5 py-3">Descripción</th>
                                        <th className="px-5 py-3">Horas</th>
                                        <th className="px-5 py-3">Creada por</th>
                                        <th className="px-5 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {plantillas.map((plantilla) => (
                                        <tr key={plantilla.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold text-slate-800">{plantilla.nombre}</td>
                                            <td className="px-5 py-3 text-slate-500">{plantilla.descripcion || '—'}</td>
                                            <td className="px-5 py-3">{plantilla.horas_semanales} h</td>
                                            <td className="px-5 py-3 text-slate-500">{plantilla.creador?.name || '—'}</td>
                                            <td className="px-5 py-3">
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        if (window.confirm('¿Eliminar esta plantilla?')) {
                                                            router.delete(route('administracion.contratos.horarios.plantillas.destroy', plantilla.id));
                                                        }
                                                    }}
                                                    className="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700"
                                                >
                                                    Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!plantillas.length && <p className="py-10 text-center text-sm text-slate-500">Aún no hay plantillas.</p>}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
