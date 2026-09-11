import { DIAS_SEMANA, horaCorta, horasEntre, totalHorasSemana } from '@/horarioTurnos';
import { useMemo, useState } from 'react';

const claseHora = 'h-9 w-[8.75rem] border border-slate-400 bg-white px-1.5 text-sm';
const claseHoraTabla = 'h-8 w-[8.25rem] border border-slate-300 bg-white px-1 text-sm';

function IconoBorrar({ className = 'h-4 w-4' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={className} aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 15.5 14 6a2.1 2.1 0 0 1 3 3l-9.5 9.5H4.5v-3Z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="m12.5 8.5 3 3" />
        </svg>
    );
}

export default function HorarioTurnosForm({ turnos, dias, onChangeTurnos, onChangeDias }) {
    const [diaSeleccionado, setDiaSeleccionado] = useState(1);
    const horas = useMemo(() => totalHorasSemana(dias).toFixed(1), [dias]);

    const actualizarTurno = (indice, campo, valor) => {
        const siguientes = turnos.map((turno, actual) => (
            actual === indice ? { ...turno, [campo]: valor } : turno
        ));
        onChangeTurnos(siguientes.map((turno) => ({
            ...turno,
            inicio: horaCorta(turno.inicio),
            fin: horaCorta(turno.fin),
        })));
    };

    const limpiarTurno = (indice) => {
        onChangeTurnos(turnos.map((turno, actual) => (
            actual === indice ? { ...turno, inicio: '', fin: '' } : turno
        )));
    };

    const aplicarAlDia = () => {
        const carga = {};
        turnos.forEach((turno) => {
            if (turno.inicio && turno.fin) {
                carga[turno.clave] = { inicio: horaCorta(turno.inicio), fin: horaCorta(turno.fin) };
            }
        });

        onChangeDias({
            ...dias,
            [diaSeleccionado]: carga,
        });

        const siguiente = DIAS_SEMANA.find((dia) => dia.id === diaSeleccionado + 1);
        if (siguiente) {
            setDiaSeleccionado(siguiente.id);
        }
    };

    const actualizarCelda = (diaId, clave, campo, valor) => {
        const actual = dias[diaId] || {};
        const turno = actual[clave] || { inicio: '', fin: '' };
        onChangeDias({
            ...dias,
            [diaId]: {
                ...actual,
                [clave]: { ...turno, [campo]: horaCorta(valor) },
            },
        });
    };

    const borrarFila = (diaId) => {
        const siguientes = { ...dias };
        delete siguientes[diaId];
        delete siguientes[String(diaId)];
        onChangeDias(siguientes);
    };

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap items-end gap-4">
                {turnos.map((turno, indice) => (
                    <div key={turno.clave} className="min-w-[15rem]">
                        <div className="mb-2 flex items-center justify-between gap-2">
                            <p className="text-xs font-semibold text-slate-700">
                                {indice + 1}. {turno.nombre}
                            </p>
                            <button
                                type="button"
                                onClick={() => limpiarTurno(indice)}
                                title={`Limpiar ${turno.nombre}`}
                                className="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-300 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                            >
                                <span className="sr-only">Limpiar {turno.nombre}</span>
                                <IconoBorrar />
                            </button>
                        </div>
                        <div className="flex items-center gap-2">
                            <input
                                type="time"
                                step="60"
                                value={turno.inicio}
                                onChange={(event) => actualizarTurno(indice, 'inicio', event.target.value)}
                                className={claseHora}
                            />
                            <span className="text-xs text-slate-500">Hasta</span>
                            <input
                                type="time"
                                step="60"
                                value={turno.fin}
                                onChange={(event) => actualizarTurno(indice, 'fin', event.target.value)}
                                className={claseHora}
                            />
                        </div>
                    </div>
                ))}

                <div className="flex items-end gap-2">
                    <label className="text-xs font-semibold text-slate-600">
                        Cargar en
                        <select
                            value={diaSeleccionado}
                            onChange={(event) => setDiaSeleccionado(Number(event.target.value))}
                            className="mt-1 block border border-slate-400 px-2 py-1.5 text-sm"
                        >
                            {DIAS_SEMANA.map((dia) => (
                                <option key={dia.id} value={dia.id}>{dia.nombre}</option>
                            ))}
                        </select>
                    </label>
                    <button
                        type="button"
                        onClick={aplicarAlDia}
                        className="flex h-10 w-10 items-center justify-center bg-slate-700 text-lg font-bold text-white hover:bg-slate-800"
                        title="Bajar turnos a la tabla"
                    >
                        +
                    </button>
                </div>

                <div className="ml-auto rounded border border-slate-300 bg-slate-50 px-4 py-2 text-right">
                    <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Horas del horario</p>
                    <p className="text-lg font-bold text-slate-900">{horas} h</p>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-sm">
                    <thead>
                        <tr className="bg-slate-950 text-white">
                            <th className="border border-slate-700 px-3 py-2 text-left font-semibold">Día</th>
                            {turnos.map((turno) => (
                                <th key={turno.clave} className="border border-slate-700 px-3 py-2 font-semibold">
                                    {turno.nombre}
                                </th>
                            ))}
                            <th className="border border-slate-700 px-3 py-2 font-semibold">Horas</th>
                            <th className="border border-slate-700 px-3 py-2 font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        {DIAS_SEMANA.map((dia) => {
                            const horasDia = turnos.reduce((suma, turno) => {
                                const celda = dias[dia.id]?.[turno.clave];
                                return suma + horasEntre(celda?.inicio, celda?.fin);
                            }, 0);

                            return (
                                <tr key={dia.id}>
                                    <td className="border border-slate-300 bg-slate-50 px-3 py-2 font-medium text-slate-800">
                                        {dia.nombre}
                                    </td>
                                    {turnos.map((turno) => {
                                        const celda = dias[dia.id]?.[turno.clave] || { inicio: '', fin: '' };

                                        return (
                                            <td key={turno.clave} className="border border-slate-300 px-2 py-2">
                                                <div className="flex items-center justify-center gap-1 whitespace-nowrap">
                                                    <input
                                                        type="time"
                                                        step="60"
                                                        value={celda.inicio || ''}
                                                        onChange={(event) => actualizarCelda(dia.id, turno.clave, 'inicio', event.target.value)}
                                                        className={claseHoraTabla}
                                                    />
                                                    <span className="text-[10px] text-slate-400">–</span>
                                                    <input
                                                        type="time"
                                                        step="60"
                                                        value={celda.fin || ''}
                                                        onChange={(event) => actualizarCelda(dia.id, turno.clave, 'fin', event.target.value)}
                                                        className={claseHoraTabla}
                                                    />
                                                </div>
                                            </td>
                                        );
                                    })}
                                    <td className="border border-slate-300 px-3 py-2 text-center font-semibold text-slate-700">
                                        {horasDia ? `${horasDia.toFixed(1)} h` : '—'}
                                    </td>
                                    <td className="border border-slate-300 px-2 py-2 text-center">
                                        <button
                                            type="button"
                                            onClick={() => borrarFila(dia.id)}
                                            disabled={!horasDia && !dias[dia.id]}
                                            title={`Borrar ${dia.nombre}`}
                                            className="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-300"
                                        >
                                            <span className="sr-only">Borrar {dia.nombre}</span>
                                            <IconoBorrar className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
