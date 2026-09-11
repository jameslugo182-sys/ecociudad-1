export const DIAS_RECOLECCION = [
    { valor: 1, corto: 'Lun', largo: 'Lunes' },
    { valor: 2, corto: 'Mar', largo: 'Martes' },
    { valor: 3, corto: 'Mié', largo: 'Miércoles' },
    { valor: 4, corto: 'Jue', largo: 'Jueves' },
    { valor: 5, corto: 'Vie', largo: 'Viernes' },
    { valor: 6, corto: 'Sáb', largo: 'Sábado' },
    { valor: 7, corto: 'Dom', largo: 'Domingo' },
];

export const DIAS_CALENDARIO = [
    { valor: 1, corto: 'lun.' },
    { valor: 2, corto: 'mar.' },
    { valor: 3, corto: 'mié.' },
    { valor: 4, corto: 'jue.' },
    { valor: 5, corto: 'vie.' },
    { valor: 6, corto: 'sáb.' },
    { valor: 7, corto: 'dom.' },
];

export const COLORES_CRONOGRAMA = ['#5cb85c', '#f0ad4e', '#d9534f', '#5bc0de', '#337ab7', '#9b59b6'];

const MESES_ES = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
];

export function horaCorta(hora) {
    return hora ? String(hora).slice(0, 5) : '';
}

export function colorDeRuta(rutaId) {
    return COLORES_CRONOGRAMA[rutaId % COLORES_CRONOGRAMA.length];
}

export function fechaRuta(ruta) {
    const coincidencia = String(ruta?.fecha || '').match(/^(\d{4}-\d{2}-\d{2})/);

    return coincidencia ? coincidencia[1] : '';
}

export function parseClave(clave) {
    const [anio, mes, dia] = clave.split('-').map(Number);

    return new Date(anio, mes - 1, dia);
}

export function agregarDias(fecha, delta) {
    const siguiente = new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate());
    siguiente.setDate(siguiente.getDate() + delta);

    return siguiente;
}

export function inicioSemana(fecha) {
    const inicio = new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate());
    inicio.setDate(inicio.getDate() - (isoDiaSemana(inicio) - 1));

    return inicio;
}

export function numeroSemanaIso(fecha) {
    const cursor = new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate());
    const dia = cursor.getDay() || 7;
    cursor.setDate(cursor.getDate() + 4 - dia);
    const inicioAnio = new Date(cursor.getFullYear(), 0, 1);

    return Math.ceil(((cursor - inicioAnio) / 86400000 + 1) / 7);
}

export function minutosDeHora(hora) {
    const [horas, minutos] = horaCorta(hora).split(':').map(Number);

    return (horas || 0) * 60 + (minutos || 0);
}

export function rutaCaeEnFecha(ruta, clave) {
    const dias = (ruta.dias_recoleccion || []).map(Number);
    const inicio = fechaRuta(ruta);

    return dias.includes(isoDiaSemana(parseClave(clave))) && (! inicio || inicio <= clave);
}

export function eventosEnFecha(rutas, clave) {
    return rutas.filter((ruta) => rutaCaeEnFecha(ruta, clave));
}

export function semanasDelMes(mes) {
    const celdas = celdasCalendario(mes);

    return Array.from({ length: 6 }, (_, indice) => {
        const dias = celdas.slice(indice * 7, indice * 7 + 7);

        return {
            numero: numeroSemanaIso(parseClave(dias[0].clave)),
            dias,
        };
    });
}

export function etiquetaTituloCalendario(vista, fecha) {
    if (vista === 'dia') {
        return fecha.toLocaleDateString('es-PE', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    if (vista === 'semana') {
        const inicio = inicioSemana(fecha);
        const fin = agregarDias(inicio, 6);

        if (inicio.getMonth() === fin.getMonth()) {
            return `${inicio.getDate()} – ${fin.getDate()} ${MESES_ES[inicio.getMonth()]} ${inicio.getFullYear()}`;
        }

        return `${inicio.getDate()} ${MESES_ES[inicio.getMonth()]} – ${fin.getDate()} ${MESES_ES[fin.getMonth()]} ${fin.getFullYear()}`;
    }

    return `${MESES_ES[fecha.getMonth()]} ${fecha.getFullYear()}`;
}

export function isoDiaSemana(fecha) {
    const dia = fecha.getDay();

    return dia === 0 ? 7 : dia;
}

export function claveFechaLocal(fecha) {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');

    return `${anio}-${mes}-${dia}`;
}

export function celdasCalendario(mes) {
    const [anio, mesNumero] = mes.split('-').map(Number);
    const primero = new Date(anio, mesNumero - 1, 1);
    const inicio = new Date(primero);
    inicio.setDate(primero.getDate() - (isoDiaSemana(primero) - 1));

    return Array.from({ length: 42 }, (_, indice) => {
        const fecha = new Date(inicio);
        fecha.setDate(inicio.getDate() + indice);

        return {
            clave: claveFechaLocal(fecha),
            dia: fecha.getDate(),
            iso: isoDiaSemana(fecha),
            delMes: fecha.getMonth() === mesNumero - 1,
            hoy: claveFechaLocal(fecha) === claveFechaLocal(new Date()),
        };
    });
}

export function etiquetaMes(mes) {
    const [anio, mesNumero] = mes.split('-').map(Number);

    return new Date(anio, mesNumero - 1, 1).toLocaleDateString('es-PE', {
        month: 'long',
        year: 'numeric',
    });
}

export function mesDesplazado(mes, delta) {
    const [anio, mesNumero] = mes.split('-').map(Number);
    const fecha = new Date(anio, mesNumero - 1 + delta, 1);

    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
}

export function navegacionRutas(routeNames) {
    return [
        { label: 'Planificación de rutas', href: route(routeNames.index), active: routeNames.index },
        { label: 'Rutas generadas', href: `${route(routeNames.index)}#rutas-generadas`, hash: '#rutas-generadas' },
        { label: 'Horarios de recolección', href: route(routeNames.horarios), active: routeNames.horarios },
        { label: 'Cronograma de recolección', href: route(routeNames.cronograma), active: routeNames.cronograma },
    ];
}
