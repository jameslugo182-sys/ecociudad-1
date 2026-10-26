import EstadoBadge from '@/Components/EstadoBadge';
import FlashMessage from '@/Components/FlashMessage';
import Modal from '@/Components/Modal';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionTachos } from '@/navegacionTachos';
import {
    CapturarClicMapa,
    CENTRO_TAMBO,
    InvalidarMapaModal,
} from '@/tachoMapa';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CircleMarker, MapContainer, TileLayer } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

const datosIniciales = {
    nombre: '',
    direccion: '',
    latitud: '',
    longitud: '',
    distancia_vacio_cm: 75,
    distancia_lleno_cm: 5,
    umbral_alerta: 80,
    activo: true,
};

function BarraCapacidad({ tacho }) {
    if (tacho.nivel_porcentaje === null || tacho.nivel_porcentaje === undefined) {
        return <span className="text-xs text-slate-400">Sin lecturas</span>;
    }

    const lleno = tacho.nivel_porcentaje;
    const resto = tacho.capacidad_restante ?? Math.max(0, 100 - lleno);
    const color = tacho.alerta_activa || lleno >= 95 ? 'bg-red-500' : lleno >= 50 ? 'bg-amber-500' : 'bg-emerald-500';

    return (
        <div className="min-w-36">
            <div className="mb-1 flex justify-between text-[11px] font-semibold text-slate-600">
                <span>Lleno {lleno}%</span>
                <span>Libre {resto}%</span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-200">
                <div className={`h-full ${color}`} style={{ width: `${lleno}%` }} />
            </div>
        </div>
    );
}

export default function Index({ tachos, filters, estados, siguienteCodigo, alertas, rutasActivas }) {
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState(null);
    const [codigoVisible, setCodigoVisible] = useState(siguienteCodigo);
    const [rutaPorTacho, setRutaPorTacho] = useState({});
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

    useEffect(() => {
        if (mostrarFormulario) {
            return undefined;
        }

        const actualizar = () => {
            if (document.hidden) {
                return;
            }

            router.reload({
                only: ['tachos', 'alertas', 'rutasActivas'],
                preserveState: true,
                preserveScroll: true,
                showProgress: false,
            });
        };

        const intervalo = window.setInterval(actualizar, 5000);

        return () => window.clearInterval(intervalo);
    }, [mostrarFormulario]);

    const puntoSeleccionado =
        form.data.latitud !== '' && form.data.longitud !== ''
            ? [Number(form.data.latitud), Number(form.data.longitud)]
            : null;

    const cerrarFormulario = () => {
        if (form.processing) {
            return;
        }

        setMostrarFormulario(false);
        setEditando(null);
        form.setData(datosIniciales);
        form.clearErrors();
    };

    const abrirNuevo = () => {
        setEditando(null);
        setCodigoVisible(siguienteCodigo);
        form.setData(datosIniciales);
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const abrirEdicion = (tacho) => {
        setEditando(tacho.id);
        setCodigoVisible(tacho.codigo);
        form.setData({
            nombre: tacho.nombre,
            direccion: tacho.direccion || '',
            latitud: tacho.latitud,
            longitud: tacho.longitud,
            distancia_vacio_cm: tacho.distancia_vacio_cm,
            distancia_lleno_cm: tacho.distancia_lleno_cm,
            umbral_alerta: tacho.umbral_alerta,
            activo: tacho.activo,
        });
        form.clearErrors();
        setMostrarFormulario(true);
    };

    const marcarMapa = (latitud, longitud) => {
        form.setData((valores) => ({
            ...valores,
            latitud,
            longitud,
        }));
    };

    const guardar = (event) => {
        event.preventDefault();

        if (editando) {
            form.put(route('administracion.tachos.update', editando), {
                preserveScroll: true,
                onSuccess: cerrarFormulario,
            });
            return;
        }

        form.post(route('administracion.tachos.store'), {
            preserveScroll: true,
            onSuccess: cerrarFormulario,
        });
    };

    const buscar = (event) => {
        event.preventDefault();
        filtro.get(route('administracion.tachos.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const regenerarToken = (tacho) => {
        if (window.confirm(`¿Generar un token nuevo para ${tacho.codigo}? El ESP32 dejará de autenticarse con el anterior.`)) {
            router.post(route('administracion.tachos.token', tacho.id), {}, { preserveScroll: true });
        }
    };

    const agregarARuta = (tacho) => {
        const rutaId = rutaPorTacho[tacho.id];
        router.post(
            route('administracion.tachos.ruta', tacho.id),
            { ruta_id: rutaId || '' },
            { preserveScroll: true },
        );
    };

    return (
        <ModuleLayout moduleName="Tachos IoT" items={navegacionTachos}>
            <Head title="Tachos IoT" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Puntos fijos municipales</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Tachos IoT</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Coloca contenedores en el mapa, supervisa la capacidad y agrega los llenos a una ruta de camión.
                                Las lecturas se actualizan solas.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={route('administracion.tachos.mapa')}
                                className="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Ver mapa
                            </Link>
                            <button
                                type="button"
                                onClick={abrirNuevo}
                                className="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                + Registrar tacho
                            </button>
                        </div>
                    </div>

                    {alertas > 0 && (
                        <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                            {alertas} tacho{alertas === 1 ? '' : 's'} superó el umbral. Puedes incorporarlos a una ruta ya planificada.
                        </div>
                    )}

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
                                    {editando ? 'Editar tacho' : 'Registrar tacho'}
                                </h2>
                                <p className="mt-1 text-xs text-slate-500">
                                    Haz clic en el mapa para fijar el punto. El código se asigna automáticamente.
                                </p>
                            </div>

                            <div className="grid gap-0 lg:grid-cols-[1.1fr_1fr]">
                                <div className="border-b border-slate-200 p-4 lg:border-b-0 lg:border-r">
                                    {mostrarFormulario && (
                                        <MapContainer
                                            key={editando ? `editar-${editando}` : 'nuevo'}
                                            center={puntoSeleccionado || CENTRO_TAMBO}
                                            zoom={puntoSeleccionado ? 16 : 13}
                                            scrollWheelZoom
                                            className="h-[360px] w-full rounded-xl"
                                        >
                                            <TileLayer
                                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                            />
                                            <InvalidarMapaModal />
                                            <CapturarClicMapa onSelect={marcarMapa} />
                                            {puntoSeleccionado && (
                                                <CircleMarker
                                                    center={puntoSeleccionado}
                                                    radius={12}
                                                    pathOptions={{ color: '#ffffff', weight: 3, fillColor: '#059669', fillOpacity: 1 }}
                                                />
                                            )}
                                        </MapContainer>
                                    )}
                                    {(form.errors.latitud || form.errors.longitud) && (
                                        <p className="mt-2 text-xs font-medium text-red-600">
                                            {form.errors.latitud || form.errors.longitud}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-4 p-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <label className="text-xs font-semibold text-slate-600">
                                            Código
                                            <input
                                                value={codigoVisible}
                                                readOnly
                                                className="mt-1.5 w-full rounded-lg border-slate-300 bg-slate-100 text-sm font-bold text-slate-700"
                                            />
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            Nombre *
                                            <input
                                                value={form.data.nombre}
                                                onChange={(event) => form.setData('nombre', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                                minLength={3}
                                            />
                                            {form.errors.nombre && <span className="mt-1 block font-medium text-red-600">{form.errors.nombre}</span>}
                                        </label>
                                    </div>
                                    <label className="block text-xs font-semibold text-slate-600">
                                        Dirección o referencia
                                        <input
                                            value={form.data.direccion}
                                            onChange={(event) => form.setData('direccion', event.target.value)}
                                            className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        />
                                    </label>
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <label className="text-xs font-semibold text-slate-600">
                                            Vacío (cm) *
                                            <input
                                                type="number"
                                                min="20"
                                                max="300"
                                                value={form.data.distancia_vacio_cm}
                                                onChange={(event) => form.setData('distancia_vacio_cm', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            />
                                            {form.errors.distancia_vacio_cm && (
                                                <span className="mt-1 block font-medium text-red-600">{form.errors.distancia_vacio_cm}</span>
                                            )}
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            Lleno (cm) *
                                            <input
                                                type="number"
                                                min="2"
                                                max="80"
                                                value={form.data.distancia_lleno_cm}
                                                onChange={(event) => form.setData('distancia_lleno_cm', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            />
                                            {form.errors.distancia_lleno_cm && (
                                                <span className="mt-1 block font-medium text-red-600">{form.errors.distancia_lleno_cm}</span>
                                            )}
                                        </label>
                                        <label className="text-xs font-semibold text-slate-600">
                                            Umbral % *
                                            <input
                                                type="number"
                                                min="50"
                                                max="100"
                                                value={form.data.umbral_alerta}
                                                onChange={(event) => form.setData('umbral_alerta', event.target.value)}
                                                className="mt-1.5 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            />
                                        </label>
                                    </div>
                                    <p className="text-[11px] text-slate-500">
                                        Tacho de 75 cm con el sensor en la tapa: vacío 75 cm (tapa al fondo) y lleno 5 cm (el ultrasónico ya no mide bien más cerca).
                                    </p>
                                    {editando && (
                                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                checked={form.data.activo}
                                                onChange={(event) => form.setData('activo', event.target.checked)}
                                                className="rounded border-slate-300 text-emerald-600"
                                            />
                                            Tacho operativo (acepta lecturas del ESP32)
                                        </label>
                                    )}
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
                            placeholder="Código, nombre o dirección"
                            className="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <select
                            value={filtro.data.estado}
                            onChange={(event) => filtro.setData('estado', event.target.value)}
                            className="rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Todos los estados</option>
                            {estados.map((estado) => (
                                <option key={estado}>{estado}</option>
                            ))}
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
                                        <th className="px-5 py-4">Código</th>
                                        <th className="px-5 py-4">Punto</th>
                                        <th className="px-5 py-4">Capacidad</th>
                                        <th className="px-5 py-4">Estado</th>
                                        <th className="px-5 py-4">Última lectura</th>
                                        <th className="px-5 py-4">Ruta</th>
                                        <th className="px-5 py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tachos.data.map((tacho) => (
                                        <tr key={tacho.id} className="border-t border-slate-100 text-sm">
                                            <td className="px-5 py-4 font-semibold text-slate-900">{tacho.codigo}</td>
                                            <td className="px-5 py-4">
                                                <p className="font-medium text-slate-800">{tacho.nombre}</p>
                                                <p className="text-xs text-slate-500">{tacho.direccion || `${tacho.latitud}, ${tacho.longitud}`}</p>
                                            </td>
                                            <td className="px-5 py-4">
                                                <BarraCapacidad tacho={tacho} />
                                            </td>
                                            <td className="px-5 py-4">
                                                <EstadoBadge estado={tacho.estado} />
                                            </td>
                                            <td className="px-5 py-4 text-xs text-slate-500">{tacho.ultima_lectura_at || '—'}</td>
                                            <td className="px-5 py-4">
                                                {tacho.en_ruta ? (
                                                    <span className="text-xs font-semibold text-emerald-700">En ruta activa</span>
                                                ) : tacho.alerta_activa ? (
                                                    <div className="flex min-w-52 flex-col gap-2">
                                                        <select
                                                            value={rutaPorTacho[tacho.id] || ''}
                                                            onChange={(event) =>
                                                                setRutaPorTacho((actual) => ({
                                                                    ...actual,
                                                                    [tacho.id]: event.target.value,
                                                                }))
                                                            }
                                                            className="rounded-lg border-slate-300 text-xs"
                                                        >
                                                            <option value="">Elegir ruta…</option>
                                                            {rutasActivas.map((ruta) => (
                                                                <option key={ruta.id} value={ruta.id}>
                                                                    {ruta.camion?.codigo || 'Camión'} · {ruta.fecha} · {ruta.estado}
                                                                </option>
                                                            ))}
                                                        </select>
                                                        <button
                                                            type="button"
                                                            onClick={() => agregarARuta(tacho)}
                                                            className="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white"
                                                        >
                                                            Agregar a ruta
                                                        </button>
                                                        {rutasActivas.length === 0 && (
                                                            <p className="text-[11px] text-slate-500">No hay rutas planificadas o en curso.</p>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-slate-400">Sin alerta</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => abrirEdicion(tacho)}
                                                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                                                    >
                                                        Editar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => regenerarToken(tacho)}
                                                        className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"
                                                    >
                                                        Nuevo token
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {tachos.data.length === 0 && (
                            <div className="py-14 text-center text-sm text-slate-500">No hay tachos registrados.</div>
                        )}

                        {tachos.last_page > 1 && (
                            <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                                {tachos.prev_page_url && (
                                    <Link href={tachos.prev_page_url} className="rounded-lg border px-3 py-2 text-sm">
                                        Anterior
                                    </Link>
                                )}
                                {tachos.next_page_url && (
                                    <Link href={tachos.next_page_url} className="rounded-lg border px-3 py-2 text-sm">
                                        Siguiente
                                    </Link>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
