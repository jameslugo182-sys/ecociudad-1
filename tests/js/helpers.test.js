import { describe, expect, it, vi } from 'vitest';
import {
    agregarDias,
    celdasCalendario,
    claveFechaLocal,
    colorDeRuta,
    COLORES_CRONOGRAMA,
    eventosEnFecha,
    etiquetaMes,
    etiquetaTituloCalendario,
    fechaRuta,
    horaCorta,
    inicioSemana,
    isoDiaSemana,
    mesDesplazado,
    minutosDeHora,
    navegacionRutas,
    numeroSemanaIso,
    parseClave,
    rutaCaeEnFecha,
    semanasDelMes,
} from '@/horarioRuta';
import { horasEntre, normalizarTurnos, totalHorasSemana, TURNOS_INICIALES } from '@/horarioTurnos';
import { navegacionContratos } from '@/navegacionContratos';
import { navegacionReportes } from '@/navegacionReportes';
import { navegacionTachos } from '@/navegacionTachos';
import { navegacionVehiculos } from '@/navegacionVehiculos';
import { BASE_MUNICIPAL, posicionesDeRuta } from '@/rutaMapa';
import { broadcastLogout, listenForLogout } from '@/sessionSync';

const MARTES = '2026-10-27';

describe('horarioRuta', () => {
    it('recorta la hora a HH:MM', () => {
        expect(horaCorta('08:30:00')).toBe('08:30');
        expect(horaCorta(null)).toBe('');
    });

    it('asigna un color estable a cada ruta', () => {
        expect(colorDeRuta(0)).toBe(COLORES_CRONOGRAMA[0]);
        expect(colorDeRuta(COLORES_CRONOGRAMA.length + 1)).toBe(COLORES_CRONOGRAMA[1]);
    });

    it('extrae la fecha de inicio de una ruta', () => {
        expect(fechaRuta({ fecha: '2026-10-27T00:00:00Z' })).toBe(MARTES);
        expect(fechaRuta({ fecha: 'sin fecha' })).toBe('');
        expect(fechaRuta(undefined)).toBe('');
    });

    it('convierte claves de fecha y calcula dias', () => {
        const fecha = parseClave(MARTES);

        expect(fecha.getFullYear()).toBe(2026);
        expect(fecha.getMonth()).toBe(9);
        expect(claveFechaLocal(agregarDias(fecha, 5))).toBe('2026-11-01');
        expect(claveFechaLocal(agregarDias(fecha, -27))).toBe('2026-09-30');
    });

    it('calcula el dia ISO y el inicio de semana', () => {
        expect(isoDiaSemana(parseClave('2026-10-25'))).toBe(7);
        expect(isoDiaSemana(parseClave(MARTES))).toBe(2);
        expect(claveFechaLocal(inicioSemana(parseClave(MARTES)))).toBe('2026-10-26');
    });

    it('calcula el numero de semana ISO', () => {
        expect(numeroSemanaIso(parseClave(MARTES))).toBe(44);
        expect(numeroSemanaIso(parseClave('2026-01-01'))).toBe(1);
    });

    it('convierte horas a minutos', () => {
        expect(minutosDeHora('02:15:00')).toBe(135);
        expect(minutosDeHora(null)).toBe(0);
    });

    it('detecta si una ruta cae en una fecha', () => {
        const ruta = { dias_recoleccion: ['2', 5], fecha: '2026-10-01' };

        expect(rutaCaeEnFecha(ruta, MARTES)).toBe(true);
        expect(rutaCaeEnFecha(ruta, '2026-10-28')).toBe(false);
        expect(rutaCaeEnFecha(ruta, '2026-09-29')).toBe(false);
        expect(rutaCaeEnFecha({ dias_recoleccion: [2] }, '2026-09-29')).toBe(true);
        expect(rutaCaeEnFecha({}, MARTES)).toBe(false);
    });

    it('filtra los eventos de una fecha', () => {
        const rutas = [
            { id: 1, dias_recoleccion: [2] },
            { id: 2, dias_recoleccion: [3] },
        ];

        expect(eventosEnFecha(rutas, MARTES).map((ruta) => ruta.id)).toEqual([1]);
    });

    it('genera 42 celdas por mes marcando los dias del mes', () => {
        const celdas = celdasCalendario('2026-10');

        expect(celdas).toHaveLength(42);
        expect(celdas[0].clave).toBe('2026-09-28');
        expect(celdas.filter((celda) => celda.delMes)).toHaveLength(31);
        expect(celdas.every((celda) => typeof celda.hoy === 'boolean')).toBe(true);
    });

    it('marca el dia de hoy', () => {
        const hoy = claveFechaLocal(new Date());
        const mes = hoy.slice(0, 7);

        expect(celdasCalendario(mes).filter((celda) => celda.hoy)).toHaveLength(1);
    });

    it('agrupa el mes en 6 semanas de 7 dias', () => {
        const semanas = semanasDelMes('2026-10');

        expect(semanas).toHaveLength(6);
        expect(semanas.every((semana) => semana.dias.length === 7)).toBe(true);
        expect(semanas[0].numero).toBe(40);
    });

    it('arma los titulos del calendario por vista', () => {
        const fecha = parseClave(MARTES);

        expect(etiquetaTituloCalendario('mes', fecha)).toBe('octubre 2026');
        expect(etiquetaTituloCalendario('semana', fecha)).toBe('26 octubre – 1 noviembre 2026');
        expect(etiquetaTituloCalendario('semana', parseClave('2026-10-14'))).toBe('12 – 18 octubre 2026');
        expect(etiquetaTituloCalendario('dia', fecha)).toContain('2026');
    });

    it('formatea y desplaza meses', () => {
        expect(etiquetaMes('2026-10')).toContain('2026');
        expect(mesDesplazado('2026-12', 1)).toBe('2027-01');
        expect(mesDesplazado('2026-01', -1)).toBe('2025-12');
    });

    it('arma la navegacion de rutas', () => {
        const items = navegacionRutas({ index: 'a', horarios: 'b', cronograma: 'c' });

        expect(items.map((item) => item.href)).toEqual(['/a', '/a#rutas-generadas', '/b', '/c']);
    });
});

describe('horarioTurnos', () => {
    it('calcula horas entre dos horas, incluso cruzando la medianoche', () => {
        expect(horasEntre('07:00', '13:30')).toBe(6.5);
        expect(horasEntre('22:00', '06:00')).toBe(8);
        expect(horasEntre('', '06:00')).toBe(0);
        expect(horasEntre('xx:yy', '06:00')).toBe(0);
    });

    it('suma las horas de la semana', () => {
        const dias = {
            1: { manana: { inicio: '07:00', fin: '13:00' }, tarde: { inicio: '14:00', fin: '18:00' } },
            2: { manana: { inicio: '08:00', fin: '12:00' }, vacio: null },
        };

        expect(totalHorasSemana(dias)).toBe(14);
        expect(totalHorasSemana(null)).toBe(0);
        expect(totalHorasSemana({ 1: null })).toBe(0);
    });

    it('normaliza los turnos y usa los iniciales por defecto', () => {
        expect(normalizarTurnos()).toEqual(TURNOS_INICIALES);
        expect(normalizarTurnos([{ clave: 'x', nombre: 'X', inicio: '01:00:00', fin: '02:00:00' }])).toEqual([
            { clave: 'x', nombre: 'X', inicio: '01:00', fin: '02:00' },
        ]);
    });
});

describe('navegaciones', () => {
    const contar = (items) => items.flatMap((item) => item.children ?? [item]).length;

    it('exponen los enlaces de cada modulo', () => {
        expect(contar(navegacionContratos)).toBe(8);
        expect(contar(navegacionReportes)).toBe(5);
        expect(contar(navegacionTachos)).toBe(2);
        expect(contar(navegacionVehiculos)).toBe(6);
        expect(navegacionReportes[0].href).toBe('/administracion.reportes.index');
    });
});

describe('rutaMapa', () => {
    it('usa la geometria cuando tiene al menos dos puntos', () => {
        const ruta = { geometria: [['-12.1', '-75.2'], [-12.2, -75.3]] };

        expect(posicionesDeRuta(ruta)).toEqual([[-12.1, -75.2], [-12.2, -75.3]]);
    });

    it('recurre a las paradas de la ruta o a las recibidas', () => {
        const ruta = { reportes: [{ latitud: '-12.0', longitud: '-75.0' }, { latitud: null, longitud: '-75.1' }] };

        expect(posicionesDeRuta(ruta)).toEqual([[-12, -75]]);
        expect(posicionesDeRuta({ geometria: [[1, 2]] }, [{ latitud: 1, longitud: 2 }])).toEqual([[1, 2]]);
        expect(posicionesDeRuta(undefined)).toEqual([]);
        expect(BASE_MUNICIPAL).toHaveLength(2);
    });
});

describe('sessionSync', () => {
    it('avisa a otras pestanas al cerrar sesion', () => {
        const callback = vi.fn();
        const detener = listenForLogout(callback);

        broadcastLogout();
        window.dispatchEvent(new StorageEvent('storage', { key: 'ecociudad:logout', newValue: '1' }));
        window.dispatchEvent(new StorageEvent('storage', { key: 'otra', newValue: '1' }));
        window.dispatchEvent(new StorageEvent('storage', { key: 'ecociudad:logout', newValue: null }));

        expect(callback).toHaveBeenCalledTimes(1);
        expect(window.localStorage.getItem('ecociudad:logout')).not.toBeNull();

        detener();
        window.dispatchEvent(new StorageEvent('storage', { key: 'ecociudad:logout', newValue: '2' }));
        expect(callback).toHaveBeenCalledTimes(1);
    });
});
