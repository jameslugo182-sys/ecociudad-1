export const navegacionContratos = [
    { label: 'Registro de contratos', href: route('administracion.contratos.index'), active: 'administracion.contratos.index' },
    { label: 'Creación de usuarios', href: route('administracion.contratos.usuarios.index'), active: 'administracion.contratos.usuarios.*' },
    {
        label: 'Horarios del personal',
        children: [
            { label: 'Plantillas de horarios', href: route('administracion.contratos.horarios.plantillas'), active: 'administracion.contratos.horarios.plantillas' },
            { label: 'Registro de horario', href: route('administracion.contratos.horarios.registro'), active: 'administracion.contratos.horarios.registro' },
        ],
    },
    {
        label: 'Maestros',
        children: [
            { label: 'Roles', href: route('administracion.contratos.maestros.roles.index'), active: 'administracion.contratos.maestros.roles.*' },
            { label: 'Cargos', href: route('administracion.contratos.maestros.cargos.index'), active: 'administracion.contratos.maestros.cargos.*' },
            { label: 'Áreas / unidades', href: route('administracion.contratos.maestros.areas.index'), active: 'administracion.contratos.maestros.areas.*' },
            { label: 'Sedes de trabajo', href: route('administracion.contratos.maestros.sedes.index'), active: 'administracion.contratos.maestros.sedes.*' },
        ],
    },
];
