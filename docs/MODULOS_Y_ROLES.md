# Módulos y roles

El acceso no es un menú “todo para todos”. Hay **roles fijos** en código y **roles de colaborador** configurables en `roles_sistema`.

## Módulos del catálogo

Definidos en `MaestroContratoController::MODULOS` y en `DashboardController::catalogoModulos`:

| Clave | Nombre en UI | Entrada típica |
| --- | --- | --- |
| `incidencias` | Incidencias Registradas | `/administracion/incidencias` |
| `contratos` | Gestión de contratos | `/administracion/contratos` |
| `vehiculos` | Vehículos | `/administracion/vehiculos` |
| `rutas` | Gestión de rutas | `/administracion/rutas` |
| `reportes` | Reportes (métricas) | `/administracion/reportes` |
| `tachos` | Tachos IoT | `/administracion/tachos` |
| `operaciones` | Operaciones de limpieza | `/limpieza` |

Middleware: `module:incidencias`, `module:contratos`, etc. (`EnsureUserCanAccessModule`).

## Roles

`users.rol` es un **código string**, no una FK.

| Código | Origen | Qué ve |
| --- | --- | --- |
| `ciudadano` | Default al registrarse | Solo `/reportes` (listar/crear los suyos) |
| `administrador` | `AdminUserSeeder` | Todos los módulos municipales del panel (no usa fila en `roles_sistema`) |
| `personal_municipal` | Semilla en migración | Módulos de su JSON (por defecto incidencias, rutas, reportes) + rutas `/admin/…` |
| `area_limpieza` | Semilla en migración | Módulo `operaciones` |
| otros | Maestro de roles | Los módulos marcados en `roles_sistema.modulos` |

Cuentas de personal se crean en Contratos → Usuarios (`CuentaColaboradorController`) y deben apuntar a un rol **activo** de `roles_sistema`. El código del rol solo admite `[a-z][a-z0-9_]*`.

`administrador` y `ciudadano` no se gestionan como filas de `roles_sistema`.

## Middleware

```
auth + verified
    role:ciudadano | role:administrador | role:personal_municipal
    module:<clave>
```

- `role`: usuario autenticado, `activo`, y `rol` en la lista.
- `module`: si es administrador, pasa; si no, el `RolSistema` activo debe incluir la clave.

Usuario `activo = false` no entra (también cubierto en pruebas).

## Qué hay dentro de cada módulo

### Ciudadano — incidencias propias

- Listado y alta con foto (hasta 10 MB), GPS y descripción.
- No ve incidencias de otros.

### Incidencias (municipio)

- Listado, filtro por estado, cambio de estado, asignación a personal.
- Mapa de puntos (`Admin/Mapa.jsx`).
- Prefijo nuevo: `/administracion/incidencias`.
- Prefijo viejo: `/admin` para `personal_municipal`.

Estados de reporte: `Pendiente` → `Asignado` → `En atención` → `Atendido`.

### Contratos

- Registro de colaborador + contrato (PDF del documento).
- Cuentas de usuario ligadas a colaborador.
- Plantillas y registro de horarios de personal (PDF).
- Maestros: cargos, roles (con módulos), áreas, sedes.

### Vehículos

- CRUD de camiones (foto).
- Equipos: **1 conductor + 3 recolectores** (`Camion::tieneEquipoCompleto()`).
- Mantenimiento: tipos, registro, en curso, historial. Un mantenimiento en curso saca el vehículo de operación.

### Rutas

- Mapa de incidencias pendientes, equipos completos, crear ruta o **proponer** agrupación (no persiste hasta confirmar).
- Horarios de recolección (`hora_inicio`, `hora_fin`, `dias_recoleccion` ISO 1–7).
- Cronograma calendario (mes / semana / agenda / día) de rutas `Planificada` o `En curso` que ya tienen horario.

Detalle en [RUTAS_MAPAS.md](RUTAS_MAPAS.md).

### Reportes (métricas)

- Indicadores por estado y operación (`ReporteMetricasController`). No confundir con `/reportes` del ciudadano.

### Tachos IoT

- Puntos fijos en el mapa, calibración del ultrasónico, capacidad restante y token del ESP32 (se muestra una vez).
- Alerta al administrador cuando el nivel cruza el umbral; opción de agregar el tacho a una ruta `Planificada` o `En curso`.
- Detalle en [IOT.md](IOT.md).

### Operaciones (limpieza)

- Tareas asignadas al usuario.
- Iniciar atención y subir evidencia fotográfica.
- Recorrido de la ruta del equipo.

## Páginas Inertia (mapa mental)

```
Pages/Welcome.jsx
Pages/Panel.jsx                          ← /dashboard
Pages/Reportes/{Index,Create,Metricas}
Pages/Admin/{Dashboard,Mapa}             ← incidencias
Pages/Contratos/{Registro,Usuarios,Horarios/*,Maestros/*}
Pages/Administracion/{Camiones,Equipos,Mantenimiento/*}
Pages/Rutas/{Index,Horarios,Cronograma}
Pages/Tachos/{Index,Mapa}
Pages/Limpieza/{Dashboard,Recorrido}
Pages/Auth/*  Pages/Profile/*
```

## Cómo añadir un módulo

1. Agrega la clave en `MaestroContratoController::MODULOS` y en `DashboardController::catalogoModulos`.
2. Crea el grupo de rutas con `middleware('module:tu_clave')`.
3. Crea páginas en `resources/js/Pages/…` y, si aplica, ítems de sidebar (`navegacionContratos.js`, `navegacionVehiculos.js`, `horarioRuta.js`).
4. Asigna el módulo a los roles que corresponda (maestro de roles o seeder/migración).
5. Cubre el 403 para un rol que no lo tenga (ver `AdministrationTest`).
