import EstadoBadge from '@/Components/EstadoBadge';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { navegacionTachos } from '@/navegacionTachos';
import { AjustarVistaTachos, CENTRO_TAMBO, MarcadorTacho } from '@/tachoMapa';
import { Head, Link } from '@inertiajs/react';
import { MapContainer, Popup, TileLayer } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

export default function Mapa({ tachos, alertas }) {
    return (
        <ModuleLayout moduleName="Tachos IoT" items={navegacionTachos}>
            <Head title="Mapa de tachos IoT" />

            <div className="min-h-screen bg-slate-50 py-8">
                <div className="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-700">Puntos fijos municipales</p>
                            <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Mapa de tachos</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Verde = holgado, ámbar = a media carga, rojo = alerta o lleno.
                            </p>
                        </div>
                        <Link
                            href={route('administracion.tachos.index')}
                            className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Ver listado
                        </Link>
                    </div>

                    <div className="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-2 text-xs font-medium text-slate-600">
                            <span className="h-3 w-3 rounded-full bg-emerald-600" />
                            Operativo
                        </div>
                        <div className="flex items-center gap-2 text-xs font-medium text-slate-600">
                            <span className="h-3 w-3 rounded-full bg-amber-600" />
                            Media carga
                        </div>
                        <div className="flex items-center gap-2 text-xs font-medium text-slate-600">
                            <span className="h-3 w-3 rounded-full bg-red-600" />
                            Alerta / lleno
                        </div>
                        <span className="ml-auto text-xs text-slate-500">
                            {tachos.length} puntos · {alertas} en alerta
                        </span>
                    </div>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {tachos.length === 0 ? (
                            <div className="flex h-[65vh] items-center justify-center text-sm text-slate-500">
                                Todavía no hay tachos georreferenciados.
                            </div>
                        ) : (
                            <MapContainer
                                center={CENTRO_TAMBO}
                                zoom={13}
                                scrollWheelZoom
                                className="h-[65vh] min-h-[480px] w-full"
                            >
                                <TileLayer
                                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                />
                                <AjustarVistaTachos tachos={tachos} />
                                {tachos.map((tacho) => (
                                    <MarcadorTacho key={tacho.id} tacho={tacho}>
                                        <Popup minWidth={240}>
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between gap-3">
                                                    <strong>{tacho.codigo}</strong>
                                                    <EstadoBadge estado={tacho.estado} />
                                                </div>
                                                <p className="text-sm text-slate-700">{tacho.nombre}</p>
                                                <p className="text-xs text-slate-500">
                                                    {tacho.nivel_porcentaje === null
                                                        ? 'Sin lecturas del ESP32'
                                                        : `Lleno ${tacho.nivel_porcentaje}% · Libre ${tacho.capacidad_restante}%`}
                                                </p>
                                                {tacho.ultima_lectura_at && (
                                                    <p className="text-xs text-slate-400">Última lectura {tacho.ultima_lectura_at}</p>
                                                )}
                                            </div>
                                        </Popup>
                                    </MarcadorTacho>
                                ))}
                            </MapContainer>
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
