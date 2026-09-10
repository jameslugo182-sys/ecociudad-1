import FlashMessage from '@/Components/FlashMessage';
import HorarioTurnosForm from '@/Components/HorarioTurnosForm';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionContratos } from '@/navegacionContratos';
import { TURNOS_INICIALES, normalizarTurnos } from '@/horarioTurnos';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = () => ({
    colaborador_id: '',
    plantilla_horario_id: '',
    vigencia_inicio: new Date().toISOString().slice(0, 10),
    vigencia_fin: '',
    turnos: TURNOS_INICIALES,
    dias: {},
});

export default function Registro({ horarios, colaboradores, plantillas }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const form = useForm(datosIniciales());

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        form.setData(datosIniciales());
        form.clearErrors();
    };

    const aplicarPlantilla = (plantillaId) => {
        const plantilla = plantillas.find((item) => String(item.id) === String(plantillaId));

        if (! plantilla) {
            form.setData('plantilla_horario_id', plantillaId);
            return;
        }

        form.setData({
            ...form.data,
            plantilla_horario_id: plantillaId,
            turnos: normalizarTurnos(plantilla.turnos),
            dias: plantilla.dias || {},
        });
    };

    const guardar = (event) => {
        event.preventDefault();
        form.post(route('administracion.contratos.horarios.registro.store'), {
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacionContratos}>
            <Head title="Registro de horario" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Horarios del personal</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Registro de horario</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Usa una plantilla o carga los turnos día por día con el botón +.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setMostrarFormulario(true)}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Registrar horario
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal show={mostrarFormulario} maxWidth="7xl" closeable={!form.processing} onClose={cerrarFormulario}>
                        <form onSubmit={guardar} className="max-h-[calc(100vh-3rem)] overflow-y-auto bg-white">
                            <div className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">Registrar horario</h3>
                                    <p className="text-xs text-slate-500">Aplica una plantilla o carga los turnos de forma manual.</p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>

                            <div className="space-y-5 p-6">
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <label className="text-xs font-semibold text-slate-600">
                                        Colaborador *
                                        <select
                                            value={form.data.colaborador_id}
                                            onChange={(event) => form.setData('colaborador_id', event.target.value)}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                            required
                                        >
                                            <option value="">Seleccionar</option>
                                            {colaboradores.map((colaborador) => (
                                                <option key={colaborador.id} value={colaborador.id}>
                                                    {colaborador.nombres} {colaborador.apellido_paterno} · {colaborador.documento}
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                    <label className="text-xs font-semibold text-slate-600">
                                        Usar plantilla
                                        <select
                                            value={form.data.plantilla_horario_id}
                                            onChange={(event) => aplicarPlantilla(event.target.value)}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                        >
                                            <option value="">Registro manual</option>
                                            {plantillas.map((plantilla) => (
                                                <option key={plantilla.id} value={plantilla.id}>
                                                    {plantilla.nombre} ({plantilla.horas_semanales} h)
                                                </option>
                                            ))}
                                        </select>
                                    </label>
                                    <label className="text-xs font-semibold text-slate-600">
                                        Vigencia desde *
                                        <input
                                            type="date"
                                            value={form.data.vigencia_inicio}
                                            onChange={(event) => form.setData('vigencia_inicio', event.target.value)}
                                            className="mt-1.5 w-full rounded-xl border-slate-300 text-sm"
                                            required
                                        />
                                    </label>
                                    <label className="text-xs font-semibold text-slate-600">
                                        Vigencia hasta
                                        <input
                                            type="date"
                                            value={form.data.vigencia_fin}
                                            onChange={(event) => form.setData('vigencia_fin', event.target.value)}
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
                                        {form.processing ? 'Guardando…' : 'Guardar horario'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </Modal>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Horarios registrados</h3>
                            <p className="text-xs text-slate-500">{horarios.length} registros</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Colaborador</th>
                                        <th className="px-5 py-3">Plantilla</th>
                                        <th className="px-5 py-3">Horas</th>
                                        <th className="px-5 py-3">Vigencia</th>
                                        <th className="px-5 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {horarios.map((horario) => (
                                        <tr key={horario.id} className="border-t border-slate-100">
                                            <td className="px-5 py-3 font-semibold text-slate-800">
                                                {horario.colaborador.nombre_completo}
                                            </td>
                                            <td className="px-5 py-3 text-slate-600">{horario.plantilla?.nombre || 'Manual'}</td>
                                            <td className="px-5 py-3">{horario.horas_semanales} h</td>
                                            <td className="px-5 py-3 text-xs text-slate-600">
                                                {new Date(horario.vigencia_inicio).toLocaleDateString('es-PE')}
                                                {horario.vigencia_fin ? ` – ${new Date(horario.vigencia_fin).toLocaleDateString('es-PE')}` : ''}
                                            </td>
                                            <td className="px-5 py-3">
                                                <a
                                                    href={route('administracion.contratos.horarios.registro.pdf', horario.id)}
                                                    title="Descargar horario en PDF"
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                                                >
                                                    <span className="sr-only">Descargar horario</span>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M7 3.75h7.5L19.5 9v11.25A1.5 1.5 0 0 1 18 21.75H7A1.5 1.5 0 0 1 5.5 20.25V5.25A1.5 1.5 0 0 1 7 3.75Z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M14.5 3.75V9h5" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M8.5 13h7M8.5 16.5h7" />
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!horarios.length && <p className="py-10 text-center text-sm text-slate-500">Aún no hay horarios asignados.</p>}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
