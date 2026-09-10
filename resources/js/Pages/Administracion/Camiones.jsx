import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const datosIniciales = {
    placa: '',
    marca: '',
    modelo: '',
    anio: '',
    capacidad_kg: '',
    estado: 'Disponible',
    foto: null,
};

export default function Camiones({ camiones, filters, estados, siguienteCodigo }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const [codigoVisible, setCodigoVisible] = useState(siguienteCodigo);
    const [fotoPreview, setFotoPreview] = useState(null);
    const form = useForm(datosIniciales);
    const filtro = useForm({
        buscar: filters.buscar || '',
        estado: filters.estado || '',
    });

    useEffect(() => {
        if (!mostrarFormulario || editando) {
            return undefined;
        }

        setCodigoVisible(siguienteCodigo);
        return undefined;
    }, [siguienteCodigo, mostrarFormulario, editando]);

    const cerrarFormulario = () => {
        if (form.processing) {
            return;
        }

        setMostrarFormulario(false);
        setEditando(null);
        setFotoPreview(null);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const abrirNuevo = () => {
        setEditando(null);
        setCodigoVisible(siguienteCodigo);
        setFotoPreview(null);
        form.setData(datosIniciales);
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const abrirEdicion = (camion) => {
        setEditando(camion.id);
        setCodigoVisible(camion.codigo);
        setFotoPreview(camion.foto_url);
        form.setData({
            placa: camion.placa,
            marca: camion.marca || '',
            modelo: camion.modelo || '',
            anio: camion.anio || '',
            capacidad_kg: camion.capacidad_kg || '',
            estado: camion.estado,
            foto: null,
        });
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const seleccionarFoto = (event) => {
        const archivo = event.target.files?.[0] || null;
        form.setData('foto', archivo);

        if (archivo) {
            setFotoPreview(URL.createObjectURL(archivo));
        }
    };

    const guardar = (event) => {
        event.preventDefault();

        if (editando) {
            form.post(route('administracion.vehiculos.update', editando), {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: cerrarFormulario,
            });
            return;
        }

        form.post(route('administracion.vehiculos.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    const buscar = (event) => {
        event.preventDefault();
        filtro.get(route('administracion.vehiculos.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const eliminar = (camion) => {
        if (window.confirm(`¿Eliminar el vehículo ${camion.codigo}?`)) {
            router.delete(route('administracion.vehiculos.destroy', camion.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <ModuleLayout moduleName="Vehículos" items={navegacionVehiculos}>
            <Head title="Registro de vehículos" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Flota municipal</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Registro de vehículos</h2>
                            <p className="mt-1 text-sm text-slate-500">Registra unidades y consulta el inventario de la flota.</p>
                        </div>
                        <button
                            type="button"
                            onClick={abrirNuevo}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            + Nuevo registro de vehículo
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal
                        show={mostrarFormulario}
                        maxWidth="5xl"
                        closeable={!form.processing}
                        onClose={cerrarFormulario}
                    >
                        <form onSubmit={guardar} className="bg-white">
                            <div className="border-b border-slate-200 px-6 py-4">
                                <h2 className="text-lg font-bold text-slate-900">
                                    {editando ? 'Editar vehículo' : 'Registro de vehículo'}
                                </h2>
                            </div>

                            <div className="grid gap-0 lg:grid-cols-[280px_1fr]">
                                <div className="border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
                                    <p className="mb-3 text-sm font-semibold text-slate-700">1. Subir foto del vehículo</p>
                                    <label className="flex aspect-[3/4] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-slate-300 bg-slate-100 transition hover:border-emerald-500 hover:bg-emerald-50">
                                        {fotoPreview ? (
                                            <img src={fotoPreview} alt="Vista previa del vehículo" className="h-full w-full object-cover" />
                                        ) : (
                                            <span className="px-4 text-center text-xs font-medium text-slate-500">
                                                Haz clic para seleccionar una imagen
                                            </span>
                                        )}
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/webp"
                                            onChange={seleccionarFoto}
                                            className="hidden"
                                            required={!editando}
                                        />
                                    </label>
                                    {form.errors.foto && <p className="mt-2 text-xs text-red-600">{form.errors.foto}</p>}
                                </div>

                                <div className="space-y-5 p-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <label className="text-xs font-semibold text-slate-600">
                                            2. Código
                                            <input
                                                value={codigoVisible}
                                                readOnly
                                                className="mt-1.5 w-full rounded-lg border-slate-300 bg-slate-100 text-sm font-bold text-slate-700"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            3. Placa *
                                            <input
                                                value={form.data.placa}
                                                onChange={(event) => form.setData('placa', event.target.value.toUpperCase())}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm uppercase focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            4. Marca
                                            <input
                                                value={form.data.marca}
                                                onChange={(event) => form.setData('marca', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            5. Modelo
                                            <input
                                                value={form.data.modelo}
                                                onChange={(event) => form.setData('modelo', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            6. Año
                                            <input
                                                type="number"
                                                value={form.data.anio}
                                                onChange={(event) => form.setData('anio', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            7. Capacidad
                                            <input
                                                type="number"
                                                value={form.data.capacidad_kg}
                                                onChange={(event) => form.setData('capacidad_kg', event.target.value)}
                                                placeholder="kg"
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600 sm:col-span-2">
                                            8. Estado
                                            <select
                                                value={form.data.estado}
                                                onChange={(event) => form.setData('estado', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                                {estados.map((estado) => <option key={estado}>{estado}</option>)}
                                            </select>
                                        </label>
                                    </div>

                                    {Object.entries(form.errors)
                                        .filter(([campo]) => campo !== 'foto')
                                        .map(([campo, error]) => (
                                            <p key={campo} className="text-xs font-medium text-red-600">{error}</p>
                                        ))}

                                    <div className="flex justify-end pt-2">
                                        <button
                                            type="submit"
                                            disabled={form.processing}
                                            className="min-w-40 rounded-lg bg-slate-300 px-8 py-3 text-sm font-bold uppercase tracking-wide text-slate-800 transition hover:bg-slate-400 disabled:opacity-50"
                                        >
                                            {form.processing ? 'Guardando…' : 'Guardar'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </Modal>

                    <form onSubmit={buscar} className="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_200px_auto]">
                        <input
                            type="search"
                            value={filtro.data.buscar}
                            onChange={(event) => filtro.setData('buscar', event.target.value)}
                            placeholder="Código, placa, marca o modelo"
                            className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <select
                            value={filtro.data.estado}
                            onChange={(event) => filtro.setData('estado', event.target.value)}
                            className="rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Todos los estados</option>
                            {estados.map((estado) => <option key={estado}>{estado}</option>)}
                        </select>
                        <button type="submit" className="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white">
                            Buscar
                        </button>
                    </form>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-4">Foto</th>
                                        <th className="px-5 py-4">Código</th>
                                        <th className="px-5 py-4">Placa</th>
                                        <th className="px-5 py-4">Marca / modelo</th>
                                        <th className="px-5 py-4">Año</th>
                                        <th className="px-5 py-4">Capacidad</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4">Equipo</th>
                                        <th className="px-5 py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {camiones.data.map((camion) => (
                                        <tr key={camion.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4">
                                                {camion.foto_url ? (
                                                    <img src={camion.foto_url} alt={camion.codigo} className="h-12 w-12 rounded object-cover" />
                                                ) : (
                                                    <span className="flex h-12 w-12 items-center justify-center rounded bg-slate-100 text-xs text-slate-400">Sin foto</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-4 font-semibold text-slate-900">{camion.codigo}</td>
                                            <td className="px-5 py-4 uppercase text-slate-700">{camion.placa}</td>
                                            <td className="px-5 py-4 text-slate-600">
                                                {[camion.marca, camion.modelo].filter(Boolean).join(' ') || '—'}
                                            </td>
                                            <td className="px-5 py-4 text-slate-600">{camion.anio || '—'}</td>
                                            <td className="px-5 py-4 text-slate-600">
                                                {camion.capacidad_kg ? `${camion.capacidad_kg} kg` : '—'}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                    {camion.estado}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4 text-xs font-semibold text-slate-600">
                                                {camion.personal_count === 4 ? 'Completo' : `${camion.personal_count}/4`}
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => abrirEdicion(camion)}
                                                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                                                    >
                                                        Editar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => eliminar(camion)}
                                                        className="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {camiones.data.length === 0 && (
                            <div className="py-14 text-center text-sm text-slate-500">No se encontraron vehículos.</div>
                        )}

                        {camiones.last_page > 1 && (
                            <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                {camiones.prev_page_url && (
                                    <Link href={camiones.prev_page_url} className="rounded-lg border px-3 py-2 text-sm">Anterior</Link>
                                )}
                                {camiones.next_page_url && (
                                    <Link href={camiones.next_page_url} className="rounded-lg border px-3 py-2 text-sm">Siguiente</Link>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
