import {
    DIAS_CALENDARIO,
    agregarDias,
    claveFechaLocal,
    colorDeRuta,
    etiquetaTituloCalendario,
    eventosEnFecha,
    horaCorta,
    inicioSemana,
    minutosDeHora,
    navegacionRutas,
    parseClave,
    semanasDelMes,
} from '@/horarioRuta';
import ModuleLayout from '@/Layouts/ModuleLayout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const VISTAS = [
    { id: 'mes', label: 'Mes' },
    { id: 'semana', label: 'Semana' },
    { id: 'agenda', label: 'Agenda' },
    { id: 'dia', label: 'Día' },
];

function mesDeFecha(fecha) {
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
}

function rangoHoras(rutas) {
    const minutos = rutas.flatMap((ruta) => [minutosDeHora(ruta.hora_inicio), minutosDeHora(ruta.hora_fin)]);
    const minimo = minutos.length ? Math.min(...minutos) : 6 * 60;
    const maximo = minutos.length ? Math.max(...minutos) : 20 * 60;

    return {
        desde: Math.max(0, Math.floor(minimo / 60) - 1),
        hasta: Math.min(24, Math.ceil(maximo / 60) + 1),
    };
}

function estiloBloque(ruta, desde, hasta) {
    const total = Math.max((hasta - desde) * 60, 60);
    const inicio = Math.max(minutosDeHora(ruta.hora_inicio) - desde * 60, 0);
    const fin = Math.min(minutosDeHora(ruta.hora_fin) - desde * 60, total);
    const altura = Math.max(fin - inicio, 28);

    return {
        top: `${(inicio / total) * 100}%`,
        height: `${(altura / total) * 100}%`,
    };
}

function PastillaEvento({ ruta, onClick }) {
    return (
        <button
            type="button"
            className="cronograma-fc__event"
            style={{ backgroundColor: colorDeRuta(ruta.id) }}
            title={`${ruta.camion?.codigo} ${horaCorta(ruta.hora_inicio)}–${horaCorta(ruta.hora_fin)}`}
            onClick={(event) => {
                event.stopPropagation();
                onClick(ruta);
            }}
        >
            {horaCorta(ruta.hora_inicio)} {ruta.camion?.codigo}
        </button>
    );
}

function PopoverEvento({ ruta, fecha, onClose }) {
    return (
        <div className="cronograma-fc__popover">
            <div className="cronograma-fc__popover-bar" style={{ backgroundColor: colorDeRuta(ruta.id) }} />
            <button type="button" className="cronograma-fc__popover-close" onClick={onClose} aria-label="Cerrar">
                ×
            </button>
            <p className="cronograma-fc__popover-title">{ruta.camion?.codigo} · {ruta.camion?.placa}</p>
            <p>{fecha} · {horaCorta(ruta.hora_inicio)} – {horaCorta(ruta.hora_fin)}</p>
            <p>{ruta.horario_resumen}</p>
            <p>{ruta.reportes?.length || 0} paradas · {ruta.estado}</p>
            {ruta.horario_nota && <p>{ruta.horario_nota}</p>}
        </div>
    );
}

function VistaMes({ ancla, rutas, onSeleccionarDia, onSeleccionarEvento }) {
    const semanas = useMemo(() => semanasDelMes(mesDeFecha(ancla)), [ancla]);

    return (
        <div className="cronograma-fc__month">
            <div className="cronograma-fc__head">
                <div className="cronograma-fc__weeknum">Sm</div>
                {DIAS_CALENDARIO.map((dia) => (
                    <div key={dia.valor} className="cronograma-fc__dow">{dia.corto}</div>
                ))}
            </div>
            {semanas.map((semana) => (
                <div key={semana.dias[0].clave} className="cronograma-fc__week-row">
                    <div className="cronograma-fc__weeknum">{semana.numero}</div>
                    {semana.dias.map((celda) => {
                        const eventos = eventosEnFecha(rutas, celda.clave);

                        return (
                            <div
                                key={celda.clave}
                                className={`cronograma-fc__day${celda.delMes ? '' : ' is-other'}${celda.hoy ? ' is-today' : ''}`}
                                onClick={() => onSeleccionarDia(parseClave(celda.clave))}
                            >
                                <span className="cronograma-fc__day-number">{celda.dia}</span>
                                <div className="cronograma-fc__events">
                                    {eventos.slice(0, 4).map((ruta) => (
                                        <PastillaEvento
                                            key={ruta.id}
                                            ruta={ruta}
                                            onClick={(seleccionada) => onSeleccionarEvento(seleccionada, celda.clave)}
                                        />
                                    ))}
                                    {eventos.length > 4 && (
                                        <span className="cronograma-fc__more">+{eventos.length - 4} más</span>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            ))}
        </div>
    );
}

function VistaTiempo({ dias, rutas, onSeleccionarEvento }) {
    const { desde, hasta } = rangoHoras(rutas);
    const horas = Array.from({ length: hasta - desde }, (_, indice) => desde + indice);

    return (
        <div className="cronograma-fc__timegrid">
            <div
                className="cronograma-fc__timegrid-head"
                style={{ gridTemplateColumns: `56px repeat(${dias.length}, minmax(0, 1fr))` }}
            >
                <div className="cronograma-fc__weeknum" />
                {dias.map((dia) => (
                    <div key={dia.clave} className={`cronograma-fc__dow${dia.hoy ? ' is-today' : ''}`}>
                        {DIAS_CALENDARIO.find((item) => item.valor === dia.iso)?.corto} {dia.dia}
                    </div>
                ))}
            </div>
            <div
                className="cronograma-fc__timegrid-body"
                style={{ gridTemplateColumns: `56px repeat(${dias.length}, minmax(0, 1fr))` }}
            >
                <div className="cronograma-fc__hours">
                    {horas.map((hora) => (
                        <div key={hora} className="cronograma-fc__hour">{String(hora).padStart(2, '0')}:00</div>
                    ))}
                </div>
                {dias.map((dia) => {
                    const eventos = eventosEnFecha(rutas, dia.clave);

                    return (
                        <div key={dia.clave} className={`cronograma-fc__slot-col${dia.hoy ? ' is-today' : ''}`}>
                            {horas.map((hora) => (
                                <div key={hora} className="cronograma-fc__hour-line" />
                            ))}
                            {eventos.map((ruta, indice) => (
                                <button
                                    key={ruta.id}
                                    type="button"
                                    className="cronograma-fc__block"
                                    style={{
                                        ...estiloBloque(ruta, desde, hasta),
                                        backgroundColor: colorDeRuta(ruta.id),
                                        left: `${indice * 8}%`,
                                        width: `${Math.max(100 - eventos.length * 8, 55)}%`,
                                    }}
                                    onClick={() => onSeleccionarEvento(ruta, dia.clave)}
                                >
                                    <strong>{horaCorta(ruta.hora_inicio)} {ruta.camion?.codigo}</strong>
                                    <span>{horaCorta(ruta.hora_inicio)} – {horaCorta(ruta.hora_fin)}</span>
                                </button>
                            ))}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function VistaAgenda({ ancla, rutas, onSeleccionarEvento }) {
    const dias = useMemo(() => semanasDelMes(mesDeFecha(ancla)).flatMap((semana) => semana.dias).filter((celda) => celda.delMes), [ancla]);
    const diasConEventos = dias
        .map((celda) => ({ celda, eventos: eventosEnFecha(rutas, celda.clave) }))
        .filter((item) => item.eventos.length);

    if (! diasConEventos.length) {
        return <p className="cronograma-fc__empty">No hay recolección programada en este mes.</p>;
    }

    return (
        <div className="cronograma-fc__agenda">
            {diasConEventos.map(({ celda, eventos }) => (
                <section key={celda.clave} className="cronograma-fc__agenda-day">
                    <h4>
                        {parseClave(celda.clave).toLocaleDateString('es-PE', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                        })}
                    </h4>
                    <ul>
                        {eventos.map((ruta) => (
                            <li key={ruta.id}>
                                <button type="button" onClick={() => onSeleccionarEvento(ruta, celda.clave)}>
                                    <span className="cronograma-fc__agenda-dot" style={{ backgroundColor: colorDeRuta(ruta.id) }} />
                                    <strong>{horaCorta(ruta.hora_inicio)} – {horaCorta(ruta.hora_fin)}</strong>
                                    <span>{ruta.camion?.codigo} · {ruta.camion?.placa}</span>
                                    <em>{ruta.horario_resumen}</em>
                                </button>
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}

export default function Cronograma({ mes, rutas, routeNames }) {
    const [vista, setVista] = useState('mes');
    const [ancla, setAncla] = useState(() => {
        const [anio, mesNumero] = mes.split('-').map(Number);
        const hoy = new Date();

        if (hoy.getFullYear() === anio && hoy.getMonth() === mesNumero - 1) {
            return new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
        }

        return new Date(anio, mesNumero - 1, 1);
    });
    const [eventoActivo, setEventoActivo] = useState(null);

    const diasSemana = useMemo(() => {
        const inicio = inicioSemana(ancla);

        return Array.from({ length: 7 }, (_, indice) => {
            const fecha = agregarDias(inicio, indice);

            return {
                clave: claveFechaLocal(fecha),
                dia: fecha.getDate(),
                iso: indice + 1,
                hoy: claveFechaLocal(fecha) === claveFechaLocal(new Date()),
            };
        });
    }, [ancla]);

    const diaActual = useMemo(() => [{
        clave: claveFechaLocal(ancla),
        dia: ancla.getDate(),
        iso: ancla.getDay() === 0 ? 7 : ancla.getDay(),
        hoy: claveFechaLocal(ancla) === claveFechaLocal(new Date()),
    }], [ancla]);

    const avanzar = (sentido) => {
        setEventoActivo(null);
        if (vista === 'dia') {
            setAncla(agregarDias(ancla, sentido));
            return;
        }
        if (vista === 'semana') {
            setAncla(agregarDias(ancla, sentido * 7));
            return;
        }
        setAncla(new Date(ancla.getFullYear(), ancla.getMonth() + sentido, 1));
    };

    const irADia = (fecha) => {
        setEventoActivo(null);
        setAncla(fecha);
        setVista('dia');
    };

    const seleccionarEvento = (ruta, clave) => {
        setEventoActivo({ ruta, clave });
    };

    return (
        <ModuleLayout moduleName="Gestión de rutas" items={navegacionRutas(routeNames)}>
            <Head title="Cronograma de recolección" />

            <div className="min-h-screen bg-slate-50 px-5 py-7 sm:px-8 lg:px-10">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div>
                        <p className="text-sm font-semibold text-emerald-700">Planificación operativa</p>
                        <h2 className="text-3xl font-extrabold tracking-tight text-slate-900">Cronograma de recolección</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Calendario de las rutas que ya tienen horario asignado en Horarios de recolección.
                        </p>
                    </div>

                    {!rutas.length && (
                        <p className="border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600">
                            Aún no hay rutas con horario programado.{' '}
                            <Link href={route(routeNames.horarios)} className="font-semibold text-emerald-700">
                                Ir a horarios de recolección
                            </Link>
                        </p>
                    )}

                    <section className="cronograma-fc">
                        <div className="cronograma-fc__toolbar">
                            <div className="cronograma-fc__nav">
                                <button type="button" className="cronograma-fc__btn" onClick={() => avanzar(-1)} aria-label="Anterior">
                                    ‹
                                </button>
                                <button type="button" className="cronograma-fc__btn" onClick={() => avanzar(1)} aria-label="Siguiente">
                                    ›
                                </button>
                            </div>
                            <h3 className="cronograma-fc__title">{etiquetaTituloCalendario(vista, ancla)}</h3>
                            <div className="cronograma-fc__views">
                                {VISTAS.map((item) => (
                                    <button
                                        key={item.id}
                                        type="button"
                                        className={`cronograma-fc__view-btn${vista === item.id ? ' is-active' : ''}`}
                                        onClick={() => {
                                            setEventoActivo(null);
                                            setVista(item.id);
                                        }}
                                    >
                                        {item.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {eventoActivo && (
                            <PopoverEvento
                                ruta={eventoActivo.ruta}
                                fecha={parseClave(eventoActivo.clave).toLocaleDateString('es-PE', {
                                    weekday: 'long',
                                    day: 'numeric',
                                    month: 'long',
                                })}
                                onClose={() => setEventoActivo(null)}
                            />
                        )}

                        {vista === 'mes' && (
                            <VistaMes
                                ancla={ancla}
                                rutas={rutas}
                                onSeleccionarDia={irADia}
                                onSeleccionarEvento={seleccionarEvento}
                            />
                        )}
                        {vista === 'semana' && (
                            <VistaTiempo dias={diasSemana} rutas={rutas} onSeleccionarEvento={seleccionarEvento} />
                        )}
                        {vista === 'agenda' && (
                            <VistaAgenda ancla={ancla} rutas={rutas} onSeleccionarEvento={seleccionarEvento} />
                        )}
                        {vista === 'dia' && (
                            <VistaTiempo dias={diaActual} rutas={rutas} onSeleccionarEvento={seleccionarEvento} />
                        )}
                    </section>
                </div>
            </div>
        </ModuleLayout>
    );
}
