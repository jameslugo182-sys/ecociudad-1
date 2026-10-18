# Dominio y datos

Hechos que el código ya modela. Si un campo o estado no está aquí, mira el modelo y la migración antes de inventar otro.

## Diagrama resumido

```
User (rol, activo)
  ├── reportes (ciudadano)
  ├── reportesAsignados (assigned_to)
  ├── colaborador? ── Contratos, HorarioPersonal
  └── camiones (pivot puesto: conductor | recolector)

RolSistema (codigo = users.rol de colaboradores, modulos[])

Reporte ──< reporte_ruta >── RutaRecoleccion ── Camion
                                      │
                                      └── geometria, horario, estado

Tacho ── LecturaTacho
  └── reportes (tipo Contenedor IoT, tacho_id)

Camion ── MantenimientoVehiculo ── TipoMantenimiento
Contrato ── Cargo, AreaUnidad, SedeTrabajo
PlantillaHorario ── HorarioPersonal
```

## Modelos (`app/Models`)

| Modelo | Tabla | Notas |
| --- | --- | --- |
| `User` | `users` | `rol`, `activo`, `colaborador_id` |
| `RolSistema` | `roles_sistema` | `modulos` JSON, `protegido` |
| `Reporte` | `reportes` | GPS, foto, evidencia, prioridad |
| `Camion` | `camiones` | Equipo de 4; estado incluye `Mantenimiento` |
| `Colaborador` | `colaboradores` | Persona; `nombre_completo` |
| `Contrato` | `contratos` | Documento en disco público |
| `Cargo` / `AreaUnidad` / `SedeTrabajo` | maestros | `activo` |
| `RutaRecoleccion` | `rutas_recoleccion` | `geometria`, `dias_recoleccion` 1–7 |
| `Tacho` | `tachos` | Punto fijo, calibración ultrasónica, `token_hash` |
| `LecturaTacho` | `lecturas_tacho` | Distancia y % enviado por el ESP32 |
| `PlantillaHorario` / `HorarioPersonal` | horarios de personal | `turnos` JSON |
| `TipoMantenimiento` / `MantenimientoVehiculo` | flota | |

Pivotes: `reporte_ruta` (`orden`, distancia), `camion_user` (`puesto`).

## Estados que no hay que “mejorar” a ciegas

**Incidencia** (`AdminController::ESTADOS`):

`Pendiente` → `Asignado` → `En atención` → `Atendido`

Al quedar `Atendido` se notifica por mail al ciudadano (`ReporteAtendido`), si el mailer está configurado.

**Ruta de recolección:**

- Alta: `Planificada`
- Operario inicia paradas: puede pasar a `En curso`
- Cancelar: solo si sigue `Planificada`; las incidencias vuelven a `Pendiente`

El cronograma solo muestra `Planificada` o `En curso` **con horario completo** (`hora_inicio`, `hora_fin`, `dias_recoleccion` no vacío → `horario_resumen`).

**Tacho IoT** (`Operativo` → `Alerta` → `Lleno`; `Inactivo` si se desactiva):

Alerta al cruzar `umbral_alerta`; histéresis de 10 puntos para salir. ≥95% es `Lleno`. La incidencia asociada es `Contenedor IoT` y se puede colgar de una ruta existente.

**Vehículo / mantenimiento:**

- Mantenimiento `En curso` ⇒ el camión no está disponible para equipo ni para rutas nuevas.
- Equipo completo = exactamente 4 personas: 1 `conductor` + 3 `recolector`.

## Archivos

Disco `public` (`storage/app/public`), URL `/storage/…`.

| Carpeta | Qué |
| --- | --- |
| `reportes_fotos/` | Foto del ciudadano |
| `evidencias_limpieza/` | Cierre operativo |
| `vehiculos/` | Foto del camión |
| `contratos/` | PDF u archivo del contrato |

Límites típicos: imágenes 5–10 MB; contratos PDF 10 MB. Nginx/PHP Docker aceptan hasta 20 MB de body.

No uses el disco `local` (privado) para estas evidencias: la UI las muestra con `foto_url` / `documento_url`.

## Seeders

`DatabaseSeeder` = `AdminUserSeeder` + `DemoEcoCiudadSeeder`.

- Admin: exige `ADMIN_EMAIL` y `ADMIN_PASSWORD`.
- Demo: ciudadanos y operarios con clave `Demo2026!`. Solo para desarrollo o si `SEED_DEMO=true` en Docker.
- Cargos y roles base también nacen en **migraciones**, no solo en seeders.

## Base de datos

- Desarrollo/producción: PostgreSQL.
- Tests: SQLite `:memory:` (`phpunit.xml`).
- No escribas SQL específico de MySQL. Evita tipos que SQLite de tests no pueda migrar.

## Cola

`QUEUE_CONNECTION=database`. Hoy no hay jobs de dominio propios; el worker en Docker queda listo para notificaciones/jobs futuros. No asumas Redis.
