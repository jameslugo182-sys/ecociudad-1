export const DIAS_RECOLECCION = [
    { valor: 1, corto: 'Lun', largo: 'Lunes' },
    { valor: 2, corto: 'Mar', largo: 'Martes' },
    { valor: 3, corto: 'Mié', largo: 'Miércoles' },
    { valor: 4, corto: 'Jue', largo: 'Jueves' },
    { valor: 5, corto: 'Vie', largo: 'Viernes' },
    { valor: 6, corto: 'Sáb', largo: 'Sábado' },
    { valor: 7, corto: 'Dom', largo: 'Domingo' },
];

export function horaCorta(hora) {
    return hora ? String(hora).slice(0, 5) : '';
}

export function navegacionRutas(routeNames) {
    return [
        { label: 'Planificación de rutas', href: route(routeNames.index), active: routeNames.index },
        { label: 'Rutas generadas', href: `${route(routeNames.index)}#rutas-generadas`, hash: '#rutas-generadas' },
        { label: 'Horarios de recolección', href: route(routeNames.horarios), active: routeNames.horarios },
    ];
}
