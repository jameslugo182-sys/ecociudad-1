export const DIAS_SEMANA = [
    { id: 1, nombre: 'Lunes' },
    { id: 2, nombre: 'Martes' },
    { id: 3, nombre: 'Miércoles' },
    { id: 4, nombre: 'Jueves' },
    { id: 5, nombre: 'Viernes' },
    { id: 6, nombre: 'Sábado' },
    { id: 7, nombre: 'Domingo' },
];

export const TURNOS_INICIALES = [
    { clave: 'manana', nombre: 'Turno mañana', inicio: '07:00', fin: '13:00' },
    { clave: 'tarde', nombre: 'Turno tarde', inicio: '14:00', fin: '18:00' },
    { clave: 'noche', nombre: 'Turno noche', inicio: '19:00', fin: '23:00' },
];

export function horaCorta(hora) {
    return hora ? String(hora).slice(0, 5) : '';
}

export function horasEntre(inicio, fin) {
    const de = horaCorta(inicio);
    const a = horaCorta(fin);

    if (!de || !a) {
        return 0;
    }

    const [h1, m1] = de.split(':').map(Number);
    const [h2, m2] = a.split(':').map(Number);
    let minutos = h2 * 60 + m2 - (h1 * 60 + m1);

    if (Number.isNaN(minutos)) {
        return 0;
    }

    if (minutos < 0) {
        minutos += 24 * 60;
    }

    return Math.round((minutos / 60) * 100) / 100;
}

export function totalHorasSemana(dias) {
    return Object.values(dias || {}).reduce((acumulado, turnosDelDia) => {
        return acumulado + Object.values(turnosDelDia || {}).reduce((suma, turno) => (
            suma + horasEntre(turno?.inicio, turno?.fin)
        ), 0);
    }, 0);
}

export function normalizarTurnos(turnos) {
    return (turnos || TURNOS_INICIALES).map((turno) => ({
        clave: turno.clave,
        nombre: turno.nombre,
        inicio: horaCorta(turno.inicio),
        fin: horaCorta(turno.fin),
    }));
}
