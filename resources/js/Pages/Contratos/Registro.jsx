import FlashMessage from '@/Components/FlashMessage';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

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

const datosIniciales = {
    documento: '',
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    fecha_nacimiento: '',
    telefono: '',
    email_personal: '',
    direccion: '',
    numero: '',
    cargo_id: '',
    tipo: 'CAS',
    fecha_inicio: '',
    fecha_fin: '',
    remuneracion: '',
    jornada_horas: '48',
    area: '',
    sede: '',
    estado: 'Vigente',
    observaciones: '',
    archivo_contrato: null,
};

export default function Registro({ contratos, cargos, tipos, estados, filters }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const form = useForm(datosIniciales);
    const filtro = useForm({
        buscar: filters.buscar || '',
        cargo_id: filters.cargo_id || '',
        estado: filters.estado || '',
    });

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        setEditando(null);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const editar = (contrato) => {
        setEditando(contrato.id);
        form.setData({
            documento: contrato.colaborador.documento,
            nombres: contrato.colaborador.nombres,
            apellido_paterno: contrato.colaborador.apellido_paterno,
            apellido_materno: contrato.colaborador.apellido_materno || '',
            fecha_nacimiento: contrato.colaborador.fecha_nacimiento?.slice(0, 10) || '',
            telefono: contrato.colaborador.telefono || '',
            email_personal: contrato.colaborador.email || '',
            direccion: contrato.colaborador.direccion || '',
            numero: contrato.numero,
            cargo_id: contrato.cargo_id,
            tipo: contrato.tipo,
            fecha_inicio: contrato.fecha_inicio.slice(0, 10),
            fecha_fin: contrato.fecha_fin.slice(0, 10),
            remuneracion: contrato.remuneracion || '',
            jornada_horas: contrato.jornada_horas || '',
            area: contrato.area,
            sede: contrato.sede || '',
            estado: contrato.estado,
            observaciones: contrato.observaciones || '',
            archivo_contrato: null,
        });
        setMostrarFormulario(true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const guardar = (event) => {
        event.preventDefault();
        const destino = editando
            ? route('administracion.contratos.update', editando)
            : route('administracion.contratos.store');

        form.post(destino, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    const buscar = (event) => {
        event.preventDefault();
        filtro.get(route('administracion.contratos.index'), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacion}>
            <Head title="Registro de contratos" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Contratos laborales</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Registro de colaboradores</h2>
                            <p className="mt-1 text-sm text-slate-500">Registra los datos personales y las condiciones contractuales.</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                cerrarFormulario();
                                setMostrarFormulario(true);
                            }}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nuevo contrato
                        </button>
                    </div>

                    <FlashMessage />

                    {mostrarFormulario && (
                        <form onSubmit={guardar} className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">{editando ? 'Editar contrato' : 'Nuevo contrato'}</h3>
                                    <p className="text-xs text-slate-500">Los campos con * son obligatorios.</p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>

                            <div className="space-y-7 p-6">
                                <fieldset>
                                    <legend className="mb-4 text-sm font-bold uppercase tracking-wider text-emerald-700">
                                        Datos del colaborador
                                    </legend>
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        {[
                                            ['documento', 'DNI / documento *', 'text'],
                                            ['nombres', 'Nombres *', 'text'],
                                            ['apellido_paterno', 'Apellido paterno *', 'text'],
                                            ['apellido_materno', 'Apellido materno', 'text'],
                                            ['fecha_nacimiento', 'Fecha de nacimiento', 'date'],
                                            ['telefono', 'Teléfono', 'tel'],
                                            ['email_personal', 'Correo personal', 'email'],
                                            ['direccion', 'Dirección', 'text'],
                                        ].map(([campo, label, tipo]) => (
                                            <label key={campo} className="text-xs font-semibold text-slate-600">
                                                {label}
                                                <input
                                                    type={tipo}
                                                    value={form.data[campo]}
                                                    onChange={(event) => form.setData(campo, event.target.value)}
                                                    className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                    required={label.includes('*')}
                                                />
                                            </label>
                                        ))}
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend className="mb-4 text-sm font-bold uppercase tracking-wider text-emerald-700">
                                        Condiciones del contrato
                                    </legend>
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <label className="text-xs font-semibold text-slate-600">
                                            Número de contrato *
                                            <input
                                                value={form.data.numero}
                                                onChange={(event) => form.setData('numero', event.target.value)}
                                                className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            Cargo *
                                            <select
                                                value={form.data.cargo_id}
                                                onChange={(event) => form.setData('cargo_id', event.target.value)}
                                                className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            >
                                                <option value="">Seleccionar cargo</option>
                                                {cargos.map((cargo) => <option key={cargo.id} value={cargo.id}>{cargo.nombre}</option>)}
                                            </select>
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            Modalidad *
                                            <select
                                                value={form.data.tipo}
                                                onChange={(event) => form.setData('tipo', event.target.value)}
                                                className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                                {tipos.map((tipo) => <option key={tipo}>{tipo}</option>)}
                                            </select>
                                        </label>
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
                                        {[
                                            ['fecha_inicio', 'Inicio del contrato *', 'date'],
                                            ['fecha_fin', 'Fin del contrato *', 'date'],
                                            ['remuneracion', 'Remuneración mensual (S/)', 'number'],
                                            ['jornada_horas', 'Horas semanales', 'number'],
                                            ['area', 'Área / unidad *', 'text'],
                                            ['sede', 'Sede de trabajo', 'text'],
                                        ].map(([campo, label, tipo]) => (
                                            <label key={campo} className="text-xs font-semibold text-slate-600">
                                                {label}
                                                <input
                                                    type={tipo}
                                                    step={campo === 'remuneracion' ? '0.01' : undefined}
                                                    value={form.data[campo]}
                                                    onChange={(event) => form.setData(campo, event.target.value)}
                                                    className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                    required={label.includes('*')}
                                                />
                                            </label>
                                        ))}
                                        <label className="text-xs font-semibold text-slate-600 md:col-span-2">
                                            Contrato firmado (PDF)
                                            <input
                                                type="file"
                                                accept="application/pdf"
                                                onChange={(event) => form.setData('archivo_contrato', event.target.files[0])}
                                                className="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600 md:col-span-2">
                                            Observaciones
                                            <textarea
                                                rows="3"
                                                value={form.data.observaciones}
                                                onChange={(event) => form.setData('observaciones', event.target.value)}
                                                className="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </label>
                                    </div>
                                </fieldset>

                                {Object.entries(form.errors).map(([campo, error]) => (
                                    <p key={campo} className="text-xs font-medium text-red-600">{error}</p>
                                ))}

                                <div className="flex justify-end gap-3 border-t border-slate-100 pt-5">
                                    <button type="button" onClick={cerrarFormulario} className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">
                                        Cancelar
                                    </button>
                                    <button type="submit" disabled={form.processing} className="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                                        {form.processing ? 'Guardando…' : 'Guardar contrato'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    )}

                    <form onSubmit={buscar} className="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_220px_180px_auto]">
                        <input
                            type="search"
                            value={filtro.data.buscar}
                            onChange={(event) => filtro.setData('buscar', event.target.value)}
                            placeholder="Nombre, DNI o número de contrato"
                            className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <select value={filtro.data.cargo_id} onChange={(event) => filtro.setData('cargo_id', event.target.value)} className="rounded-xl border-slate-300 text-sm">
                            <option value="">Todos los cargos</option>
                            {cargos.map((cargo) => <option key={cargo.id} value={cargo.id}>{cargo.nombre}</option>)}
                        </select>
                        <select value={filtro.data.estado} onChange={(event) => filtro.setData('estado', event.target.value)} className="rounded-xl border-slate-300 text-sm">
                            <option value="">Todos los estados</option>
                            {estados.map((estado) => <option key={estado}>{estado}</option>)}
                        </select>
                        <button type="submit" className="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white">Buscar</button>
                    </form>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Colaborador</th>
                                        <th className="px-5 py-4">Contrato</th>
                                        <th className="px-5 py-4">Cargo / área</th>
                                        <th className="px-5 py-4">Vigencia</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contratos.data.map((contrato) => (
                                        <tr key={contrato.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4">
                                                <p className="font-semibold text-slate-900">{contrato.colaborador.nombre_completo}</p>
                                                <p className="text-xs text-slate-500">{contrato.colaborador.documento}</p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <p className="font-semibold text-slate-700">{contrato.numero}</p>
                                                <p className="text-xs text-slate-500">{contrato.tipo}</p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-slate-700">{contrato.cargo.nombre}</p>
                                                <p className="text-xs text-slate-500">{contrato.area}</p>
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                                {new Date(contrato.fecha_inicio).toLocaleDateString('es-PE')} –<br />
                                                {new Date(contrato.fecha_fin).toLocaleDateString('es-PE')}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{contrato.estado}</span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex gap-2">
                                                    <button type="button" onClick={() => editar(contrato)} className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                                        Editar
                                                    </button>
                                                    {contrato.documento_url && (
                                                        <a href={contrato.documento_url} target="_blank" rel="noreferrer" className="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">
                                                            PDF
                                                        </a>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {contratos.data.length === 0 && (
                            <div className="py-14 text-center text-sm text-slate-500">No se encontraron contratos.</div>
                        )}
                        {contratos.last_page > 1 && (
                            <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                {contratos.prev_page_url && <Link href={contratos.prev_page_url} className="rounded-lg border px-3 py-2 text-sm">Anterior</Link>}
                                {contratos.next_page_url && <Link href={contratos.next_page_url} className="rounded-lg border px-3 py-2 text-sm">Siguiente</Link>}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
