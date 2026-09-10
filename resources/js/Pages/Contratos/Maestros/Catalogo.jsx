import CatalogoItemRow from '@/Components/CatalogoItemRow';
import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionContratos } from '@/navegacionContratos';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const datosIniciales = { nombre: '', descripcion: '' };

export default function Catalogo({ titulo, descripcion, placeholderNombre, items, conteoLabel, routeNames, botonNuevo = '+ Nuevo registro' }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const form = useForm(datosIniciales);

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        setEditando(null);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const abrirNuevo = () => {
        cerrarFormulario();
        setMostrarFormulario(true);
    };

    const abrirEditar = (item) => {
        setEditando(item.id);
        form.setData({ nombre: item.nombre, descripcion: item.descripcion || '' });
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const guardar = (event) => {
        event.preventDefault();
        const destino = editando
            ? route(routeNames.update, editando)
            : route(routeNames.store);
        const enviar = editando ? form.put.bind(form) : form.post.bind(form);

        enviar(destino, {
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    return (
        <ModuleLayout moduleName="Gestión de contratos" items={navegacionContratos}>
            <Head title={titulo} />

            <div className="min-h-screen px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Maestros</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">{titulo}</h2>
                            <p className="mt-1 text-sm text-slate-500">{descripcion}</p>
                        </div>
                        <button
                            type="button"
                            onClick={abrirNuevo}
                            className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            {botonNuevo}
                        </button>
                    </div>

                    <FlashMessage />

                    <Modal show={mostrarFormulario} maxWidth="lg" closeable={!form.processing} onClose={cerrarFormulario}>
                        <form onSubmit={guardar} className="bg-white">
                            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div>
                                    <h3 className="font-bold text-slate-900">{editando ? 'Editar registro' : 'Nuevo registro'}</h3>
                                    <p className="text-xs text-slate-500">Los campos con * son obligatorios.</p>
                                </div>
                                <button type="button" onClick={cerrarFormulario} className="text-xl text-slate-400 hover:text-slate-700">×</button>
                            </div>
                            <div className="space-y-4 p-6">
                                <label className="block text-xs font-semibold text-slate-600">
                                    Nombre *
                                    <input value={form.data.nombre} onChange={(event) => form.setData('nombre', event.target.value)} placeholder={placeholderNombre} className="mt-1.5 w-full rounded-xl border-slate-300 text-sm" required />
                                </label>
                                <label className="block text-xs font-semibold text-slate-600">
                                    Descripción
                                    <textarea value={form.data.descripcion} onChange={(event) => form.setData('descripcion', event.target.value)} rows="4" className="mt-1.5 w-full rounded-xl border-slate-300 text-sm" />
                                </label>
                                {Object.values(form.errors).map((error) => <p key={error} className="text-xs text-red-600">{error}</p>)}
                                <div className="flex justify-end gap-3 border-t border-slate-100 pt-4">
                                    <button type="button" onClick={cerrarFormulario} className="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Cancelar</button>
                                    <button type="submit" disabled={form.processing} className="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
                                        {form.processing ? 'Guardando…' : 'Guardar'}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </Modal>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="font-bold text-slate-900">Catálogo</h3>
                            <p className="text-xs text-slate-500">{items.length} registros</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Nombre</th>
                                        <th className="px-5 py-3">Descripción</th>
                                        <th className="px-5 py-3">{conteoLabel}</th>
                                        <th className="px-5 py-3">Estado</th>
                                        <th className="px-5 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => (
                                        <CatalogoItemRow
                                            key={item.id}
                                            item={item}
                                            routeNames={routeNames}
                                            onEditar={abrirEditar}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {!items.length && <p className="py-10 text-center text-sm text-slate-500">Aún no hay registros.</p>}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
