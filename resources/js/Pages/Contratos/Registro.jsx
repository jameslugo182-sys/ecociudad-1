import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionContratos } from '@/navegacionContratos';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = {
    documento: '',
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    sexo: '',
    estado_civil: '',
    nacionalidad: 'Peruana',
    fecha_nacimiento: '',
    telefono: '',
    email_personal: '',
    direccion: '',
    departamento: '',
    provincia: '',
    distrito: '',
    contacto_emergencia: '',
    telefono_emergencia: '',
    nivel_educativo: '',
    institucion_estudios: '',
    especialidad: '',
    grado_titulo: '',
    anio_egreso: '',
    numero: '',
    cargo_id: '',
    tipo: 'CAS',
    fecha_inicio: '',
    fecha_fin: '',
    remuneracion: '',
    jornada_horas: '48',
    area_id: '',
    sede_id: '',
    estado: 'Vigente',
    observaciones: '',
    archivo_contrato: null,
};

function Campo({ label, error, className = '', children }) {
    return (
        <label className={`text-xs font-semibold text-slate-600 ${className}`}>
            {label}
            {children}
            {error && <span className="mt-1 block font-medium text-red-600">{error}</span>}
        </label>
    );
}

const claseInput = 'mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500';

export default function Registro({
    contratos,
    cargos,
    areas = [],
    sedes = [],
    tipos,
    estados,
    sexos = [],
    estadosCiviles = [],
    nivelesEducativos = [],
    filters,
}) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const [descargandoFormato, setDescargandoFormato] = useState(false);
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
            sexo: contrato.colaborador.sexo || '',
            estado_civil: contrato.colaborador.estado_civil || '',
            nacionalidad: contrato.colaborador.nacionalidad || 'Peruana',
            fecha_nacimiento: contrato.colaborador.fecha_nacimiento?.slice(0, 10) || '',
            telefono: contrato.colaborador.telefono || '',
            email_personal: contrato.colaborador.email || '',
            direccion: contrato.colaborador.direccion || '',
            departamento: contrato.colaborador.departamento || '',
            provincia: contrato.colaborador.provincia || '',
            distrito: contrato.colaborador.distrito || '',
            contacto_emergencia: contrato.colaborador.contacto_emergencia || '',
            telefono_emergencia: contrato.colaborador.telefono_emergencia || '',
            nivel_educativo: contrato.colaborador.nivel_educativo || '',
            institucion_estudios: contrato.colaborador.institucion_estudios || '',
            especialidad: contrato.colaborador.especialidad || '',
            grado_titulo: contrato.colaborador.grado_titulo || '',
            anio_egreso: contrato.colaborador.anio_egreso || '',
            numero: contrato.numero,
            cargo_id: contrato.cargo_id,
            tipo: contrato.tipo,
            fecha_inicio: contrato.fecha_inicio.slice(0, 10),
            fecha_fin: contrato.fecha_fin.slice(0, 10),
            remuneracion: contrato.remuneracion || '',
            jornada_horas: contrato.jornada_horas || '',
            area_id: contrato.area_id || '',
            sede_id: contrato.sede_id || '',
            estado: contrato.estado,
            observaciones: contrato.observaciones || '',
            archivo_contrato: null,
        });
        setMostrarFormulario(true);
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

    const descargarFormato = async () => {
        form.clearErrors();
        setDescargandoFormato(true);

        const payload = { ...form.data };
        delete payload.archivo_contrato;

        try {
            const response = await window.axios.post(route('administracion.contratos.formato'), payload, {
                responseType: 'blob',
            });
            const url = window.URL.createObjectURL(response.data);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = `formato-contrato-${form.data.documento || 'colaborador'}.pdf`;
            document.body.appendChild(enlace);
            enlace.click();
            enlace.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            if (error.response?.data instanceof Blob) {
                const texto = await error.response.data.text();
                try {
                    const json = JSON.parse(texto);
                    if (json.errors) {
                        const errores = Object.fromEntries(
                            Object.entries(json.errors).map(([campo, mensajes]) => [campo, mensajes[0]]),
                        );
                        form.setError(errores);
                    }
                } catch {
                    form.setError('numero', 'Completa los datos del formulario para generar el formato.');
                }
            } else {
                form.setError('numero', 'No se pudo generar el formato. Revisa los campos obligatorios.');
            }
        } finally {
            setDescargandoFormato(false);
        }
    };

    const buscar = (event) => {
        event.preventDefault();
        filtro.get(route('administracion.contratos.index'), {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacionContratos}>
            <Head title="Registro de contratos" />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Contratos laborales</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Registro de colaboradores</h2>
                            <p className="mt-1 text-sm text-slate-500">Registra los datos personales, estudios y las condiciones contractuales.</p>
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

                    <Modal
                        show={mostrarFormulario}
                        maxWidth="7xl"
                        closeable={!form.processing && !descargandoFormato}
                        onClose={cerrarFormulario}
                    >
                        <form onSubmit={guardar} className="max-h-[calc(100vh-3rem)] overflow-y-auto bg-white">
                            <div className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">{editando ? 'Editar contrato' : 'Nuevo contrato'}</h3>
                                    <p className="text-xs text-slate-500">Los campos con * son obligatorios.</p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>

                            <div className="space-y-7 p-6">
                                <fieldset>
                                    <legend className="mb-4 text-sm font-bold uppercase tracking-wider text-emerald-700">
                                        Datos personales del colaborador
                                    </legend>
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <Campo label="DNI / documento *" error={form.errors.documento}>
                                            <input type="text" value={form.data.documento} onChange={(event) => form.setData('documento', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Nombres *" error={form.errors.nombres}>
                                            <input type="text" value={form.data.nombres} onChange={(event) => form.setData('nombres', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Apellido paterno *" error={form.errors.apellido_paterno}>
                                            <input type="text" value={form.data.apellido_paterno} onChange={(event) => form.setData('apellido_paterno', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Apellido materno" error={form.errors.apellido_materno}>
                                            <input type="text" value={form.data.apellido_materno} onChange={(event) => form.setData('apellido_materno', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Sexo" error={form.errors.sexo}>
                                            <select value={form.data.sexo} onChange={(event) => form.setData('sexo', event.target.value)} className={claseInput}>
                                                <option value="">Seleccionar</option>
                                                {sexos.map((sexo) => <option key={sexo}>{sexo}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Estado civil" error={form.errors.estado_civil}>
                                            <select value={form.data.estado_civil} onChange={(event) => form.setData('estado_civil', event.target.value)} className={claseInput}>
                                                <option value="">Seleccionar</option>
                                                {estadosCiviles.map((estado) => <option key={estado}>{estado}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Nacionalidad" error={form.errors.nacionalidad}>
                                            <input type="text" value={form.data.nacionalidad} onChange={(event) => form.setData('nacionalidad', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Fecha de nacimiento" error={form.errors.fecha_nacimiento}>
                                            <input type="date" value={form.data.fecha_nacimiento} onChange={(event) => form.setData('fecha_nacimiento', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Teléfono" error={form.errors.telefono}>
                                            <input type="tel" value={form.data.telefono} onChange={(event) => form.setData('telefono', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Correo personal" error={form.errors.email_personal}>
                                            <input type="email" value={form.data.email_personal} onChange={(event) => form.setData('email_personal', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Dirección" error={form.errors.direccion} className="xl:col-span-2">
                                            <input type="text" value={form.data.direccion} onChange={(event) => form.setData('direccion', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Departamento" error={form.errors.departamento}>
                                            <input type="text" value={form.data.departamento} onChange={(event) => form.setData('departamento', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Provincia" error={form.errors.provincia}>
                                            <input type="text" value={form.data.provincia} onChange={(event) => form.setData('provincia', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Distrito" error={form.errors.distrito}>
                                            <input type="text" value={form.data.distrito} onChange={(event) => form.setData('distrito', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Contacto de emergencia" error={form.errors.contacto_emergencia}>
                                            <input type="text" value={form.data.contacto_emergencia} onChange={(event) => form.setData('contacto_emergencia', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Teléfono de emergencia" error={form.errors.telefono_emergencia}>
                                            <input type="tel" value={form.data.telefono_emergencia} onChange={(event) => form.setData('telefono_emergencia', event.target.value)} className={claseInput} />
                                        </Campo>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend className="mb-4 text-sm font-bold uppercase tracking-wider text-emerald-700">
                                        Estudios
                                    </legend>
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <Campo label="Nivel educativo" error={form.errors.nivel_educativo}>
                                            <select value={form.data.nivel_educativo} onChange={(event) => form.setData('nivel_educativo', event.target.value)} className={claseInput}>
                                                <option value="">Seleccionar</option>
                                                {nivelesEducativos.map((nivel) => <option key={nivel}>{nivel}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Institución" error={form.errors.institucion_estudios}>
                                            <input type="text" value={form.data.institucion_estudios} onChange={(event) => form.setData('institucion_estudios', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Especialidad / carrera" error={form.errors.especialidad}>
                                            <input type="text" value={form.data.especialidad} onChange={(event) => form.setData('especialidad', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Grado o título" error={form.errors.grado_titulo}>
                                            <input type="text" value={form.data.grado_titulo} onChange={(event) => form.setData('grado_titulo', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Año de egreso" error={form.errors.anio_egreso}>
                                            <input type="number" min="1950" max={new Date().getFullYear() + 1} value={form.data.anio_egreso} onChange={(event) => form.setData('anio_egreso', event.target.value)} className={claseInput} />
                                        </Campo>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend className="mb-4 text-sm font-bold uppercase tracking-wider text-emerald-700">
                                        Condiciones del contrato
                                    </legend>
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <Campo label="Número de contrato *" error={form.errors.numero}>
                                            <input value={form.data.numero} onChange={(event) => form.setData('numero', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Cargo *" error={form.errors.cargo_id}>
                                            <select value={form.data.cargo_id} onChange={(event) => form.setData('cargo_id', event.target.value)} className={claseInput} required>
                                                <option value="">Seleccionar cargo</option>
                                                {cargos.map((cargo) => <option key={cargo.id} value={cargo.id}>{cargo.nombre}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Modalidad *" error={form.errors.tipo}>
                                            <select value={form.data.tipo} onChange={(event) => form.setData('tipo', event.target.value)} className={claseInput}>
                                                {tipos.map((tipo) => <option key={tipo}>{tipo}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Estado *" error={form.errors.estado}>
                                            <select value={form.data.estado} onChange={(event) => form.setData('estado', event.target.value)} className={claseInput}>
                                                {estados.map((estado) => <option key={estado}>{estado}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Inicio del contrato *" error={form.errors.fecha_inicio}>
                                            <input type="date" value={form.data.fecha_inicio} onChange={(event) => form.setData('fecha_inicio', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Fin del contrato *" error={form.errors.fecha_fin}>
                                            <input type="date" value={form.data.fecha_fin} onChange={(event) => form.setData('fecha_fin', event.target.value)} className={claseInput} required />
                                        </Campo>
                                        <Campo label="Remuneración mensual (S/)" error={form.errors.remuneracion}>
                                            <input type="number" step="0.01" value={form.data.remuneracion} onChange={(event) => form.setData('remuneracion', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Horas semanales" error={form.errors.jornada_horas}>
                                            <input type="number" value={form.data.jornada_horas} onChange={(event) => form.setData('jornada_horas', event.target.value)} className={claseInput} />
                                        </Campo>
                                        <Campo label="Área / unidad *" error={form.errors.area_id}>
                                            <select value={form.data.area_id} onChange={(event) => form.setData('area_id', event.target.value)} className={claseInput} required>
                                                <option value="">Seleccionar área</option>
                                                {areas.map((area) => <option key={area.id} value={area.id}>{area.nombre}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Sede de trabajo" error={form.errors.sede_id}>
                                            <select value={form.data.sede_id} onChange={(event) => form.setData('sede_id', event.target.value)} className={claseInput}>
                                                <option value="">Seleccionar sede</option>
                                                {sedes.map((sede) => <option key={sede.id} value={sede.id}>{sede.nombre}</option>)}
                                            </select>
                                        </Campo>
                                        <Campo label="Contrato firmado (PDF)" error={form.errors.archivo_contrato} className="md:col-span-2">
                                            <input
                                                type="file"
                                                accept="application/pdf"
                                                onChange={(event) => form.setData('archivo_contrato', event.target.files[0])}
                                                className="mt-1.5 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm"
                                            />
                                            <span className="mt-1 block font-normal text-slate-500">
                                                Descarga el formato, entrégalo para firma y sube aquí el PDF firmado.
                                            </span>
                                        </Campo>
                                        <Campo label="Observaciones" error={form.errors.observaciones} className="md:col-span-2">
                                            <textarea
                                                rows="3"
                                                value={form.data.observaciones}
                                                onChange={(event) => form.setData('observaciones', event.target.value)}
                                                className={claseInput}
                                            />
                                        </Campo>
                                    </div>
                                </fieldset>

                                {Object.entries(form.errors).filter(([campo]) => !['documento', 'nombres', 'apellido_paterno', 'apellido_materno', 'sexo', 'estado_civil', 'nacionalidad', 'fecha_nacimiento', 'telefono', 'email_personal', 'direccion', 'departamento', 'provincia', 'distrito', 'contacto_emergencia', 'telefono_emergencia', 'nivel_educativo', 'institucion_estudios', 'especialidad', 'grado_titulo', 'anio_egreso', 'numero', 'cargo_id', 'tipo', 'estado', 'fecha_inicio', 'fecha_fin', 'remuneracion', 'jornada_horas', 'area_id', 'sede_id', 'archivo_contrato', 'observaciones'].includes(campo)).map(([campo, error]) => (
                                    <p key={campo} className="text-xs font-medium text-red-600">{error}</p>
                                ))}

                                <div className="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
                                    <button type="button" onClick={cerrarFormulario} className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        onClick={descargarFormato}
                                        disabled={descargandoFormato || form.processing}
                                        className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {descargandoFormato ? 'Generando…' : 'Descargar formato'}
                                    </button>
                                    <button type="submit" disabled={form.processing || descargandoFormato} className="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                                        {form.processing ? 'Guardando…' : 'Guardar contrato'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </Modal>

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
