export const navegacionVehiculos = [
    { label: 'Registro de vehículos', href: route('administracion.vehiculos.index'), active: 'administracion.vehiculos.index' },
    { label: 'Equipos operativos', href: route('administracion.vehiculos.equipos'), active: 'administracion.vehiculos.equipos' },
    {
        label: 'Mantenimiento de vehículos',
        children: [
            { label: 'Registro de vehículos para mantenimiento', href: route('administracion.vehiculos.mantenimiento.registro'), active: 'administracion.vehiculos.mantenimiento.registro' },
            { label: 'Vehículos en mantenimiento', href: route('administracion.vehiculos.mantenimiento.en-curso'), active: 'administracion.vehiculos.mantenimiento.en-curso' },
            { label: 'Historial de mantenimientos', href: route('administracion.vehiculos.mantenimiento.historial'), active: 'administracion.vehiculos.mantenimiento.historial' },
        ],
    },
    {
        label: 'Maestros',
        children: [
            { label: 'Tipos de mantenimientos', href: route('administracion.vehiculos.maestros.tipos.index'), active: 'administracion.vehiculos.maestros.tipos.*' },
        ],
    },
];
